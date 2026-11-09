<?php

namespace Tests\Feature;

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VendorMigrationMysqlCompatibilityTest extends TestCase
{
    public function test_workflow_mysql_schema_identifiers_fit_the_server_limit_without_connecting(): void
    {
        $original = Schema::getFacadeRoot();
        $connection = new class(fn () => throw new \RuntimeException('SQL generation must not connect to a database.'), 'schema_test', '', ['name' => 'mysql']) extends MySqlConnection
        {
            public function isMaria()
            {
                return false;
            }

            public function getServerVersion(): string
            {
                return '8.0.36';
            }
        };
        Schema::swap($connection->getSchemaBuilder());
        try {
            $queries = $connection->pretend(function () {
                foreach ([
                    '2026_11_07_120000_create_vendor_buyer_leads_tables.php',
                    '2026_11_07_130000_create_vendor_lead_estimates_table.php',
                    '2026_11_07_140000_create_vendor_estimate_deliveries_table.php',
                    '2026_11_07_150000_create_vendor_scheduling_tables.php',
                    '2026_11_08_120000_add_vendor_pdf_password_release.php',
                ] as $file) {
                    (require database_path('migrations/'.$file))->up();
                }
            });
            $this->assertNotEmpty($queries);
            foreach ($queries as $query) {
                preg_match_all('/`([^`]+)`/', $query['query'], $identifiers);
                foreach ($identifiers[1] as $identifier) {
                    $this->assertLessThanOrEqual(64, strlen($identifier), 'MySQL identifier exceeds 64 characters: '.$identifier);
                }
            }
        } finally {
            Schema::swap($original);
        }
    }

    public function test_empty_partial_table_is_rebuilt_with_all_constraints(): void
    {
        $this->withRecoveryDatabase(function () {
            (require database_path('migrations/2026_11_07_140000_create_vendor_estimate_deliveries_table.php'))->up();
            $this->assertTrue(Schema::hasColumns('vendor_estimate_deliveries', ['vendor_lead_estimate_id', 'recipient', 'status']));
            $this->assertTrue(Schema::hasIndex('vendor_estimate_deliveries', 'vendor_est_delivery_estimate_time'));
            $this->assertTrue(Schema::hasIndex('vendor_estimate_deliveries', ['request_key'], 'unique'));
            $this->assertTrue(Schema::hasIndex('vendor_estimate_deliveries', ['scheduling_token_hash'], 'unique'));
        });
    }

    public function test_recovery_refuses_to_discard_a_table_with_delivery_records(): void
    {
        $this->withRecoveryDatabase(function () {
            DB::table('vendor_estimate_deliveries')->insert(['request_key' => 'keep-me', 'snapshot' => '{}', 'scheduling_token_hash' => 'hash', 'pdf_sha256' => 'checksum']);
            try {
                (require database_path('migrations/2026_11_07_140000_create_vendor_estimate_deliveries_table.php'))->up();
                $this->fail('Nonempty delivery table must not be dropped.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('manual migration review', $exception->getMessage());
            }
            $this->assertSame(1, DB::table('vendor_estimate_deliveries')->where('request_key', 'keep-me')->count());
        });
    }

    private function withRecoveryDatabase(callable $test): void
    {
        $originalSchema = Schema::getFacadeRoot();
        $originalConnection = DB::getDefaultConnection();
        config(['database.connections.migration_recovery_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => false]]);
        DB::setDefaultConnection('migration_recovery_test');
        Schema::swap(DB::connection()->getSchemaBuilder());
        try {
            Schema::create('vendor_estimate_deliveries', function (Blueprint $table) {
                $table->id();
                $table->string('request_key');
                $table->text('snapshot');
                $table->string('scheduling_token_hash');
                $table->string('pdf_sha256');
            });
            $test();
        } finally {
            DB::setDefaultConnection($originalConnection);
            Schema::swap($originalSchema);
            DB::purge('migration_recovery_test');
        }
    }
}
