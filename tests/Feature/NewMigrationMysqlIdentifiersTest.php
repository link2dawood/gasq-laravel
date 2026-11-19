<?php

namespace Tests\Feature;

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * MySQL refuses any identifier over 64 characters, and Laravel builds index and
 * foreign key names from the table name plus every column in them. Long table
 * names here run over that limit, which aborts `migrate --force` on deploy.
 */
class NewMigrationMysqlIdentifiersTest extends TestCase
{
    public function test_estimate_attachment_migrations_fit_the_mysql_identifier_limit(): void
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
                    '2026_11_18_120000_create_vendor_estimate_attachments_table.php',
                    '2026_11_18_130000_add_staff_required_to_vendor_lead_estimates.php',
                    '2026_11_18_140000_add_buyer_sharing_to_estimate_attachments.php',
                ] as $file) {
                    (require database_path('migrations/'.$file))->up();
                }
            });
            $this->assertNotEmpty($queries);
            foreach ($queries as $query) {
                preg_match_all('/`([^`]+)`/', $query['query'], $identifiers);
                foreach ($identifiers[1] as $identifier) {
                    $this->assertLessThanOrEqual(64, strlen($identifier), 'MySQL identifier exceeds 64 characters: '.$identifier.' ('.strlen($identifier).')');
                }
            }
        } finally {
            Schema::swap($original);
        }
    }

    public function test_an_empty_table_left_by_an_interrupted_deploy_is_rebuilt(): void
    {
        $this->withRecoveryDatabase(function () {
            // The shell a rejected index leaves behind: table created, index
            // missing, migration never recorded.
            Schema::create('vendor_estimate_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_lead_estimate_id');
                $table->string('original_name');
                $table->string('path');
                $table->string('sha256');
            });

            (require database_path('migrations/2026_11_18_120000_create_vendor_estimate_attachments_table.php'))->up();

            $this->assertTrue(Schema::hasColumns('vendor_estimate_attachments', ['mime_type', 'size_bytes', 'uploaded_by']));
            $this->assertTrue(Schema::hasIndex('vendor_estimate_attachments', 'vendor_est_attach_estimate_time'));
        });
    }

    public function test_a_table_holding_files_is_never_discarded(): void
    {
        $this->withRecoveryDatabase(function () {
            Schema::create('vendor_estimate_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_lead_estimate_id');
                $table->string('original_name');
                $table->string('path');
                $table->string('sha256');
            });
            DB::table('vendor_estimate_attachments')->insert([
                'vendor_lead_estimate_id' => 1, 'original_name' => 'contract.pdf', 'path' => 'x', 'sha256' => 'y',
            ]);

            $this->expectException(\RuntimeException::class);
            (require database_path('migrations/2026_11_18_120000_create_vendor_estimate_attachments_table.php'))->up();
        });
    }

    public function test_the_column_migrations_can_be_rerun(): void
    {
        $this->withRecoveryDatabase(function () {
            Schema::create('vendor_lead_estimates', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('weeks_per_year');
            });

            $migration = require database_path('migrations/2026_11_18_130000_add_staff_required_to_vendor_lead_estimates.php');
            $migration->up();
            // Rerunning must not throw "column already exists".
            $migration->up();

            $this->assertTrue(Schema::hasColumn('vendor_lead_estimates', 'staff_required'));
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
            $test();
        } finally {
            DB::setDefaultConnection($originalConnection);
            Schema::swap($originalSchema);
            DB::purge('migration_recovery_test');
        }
    }
}
