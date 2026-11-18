<?php

namespace Tests\Feature;

use Illuminate\Database\MySqlConnection;
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
}
