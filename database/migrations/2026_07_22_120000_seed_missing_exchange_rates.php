<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        // Seed an editable exchange-rate setting for any currency added since the
        // last run (e.g. Mexico / MXN). Existing rates are left untouched.
        foreach ((array) config('currency.profiles', []) as $code => $profile) {
            if ($code === 'USD') {
                continue;
            }

            $key = 'exchange_rate_' . strtolower((string) $code);

            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => (string) ($profile['rate'] ?? 1),
                    'group' => 'localization',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Leave seeded rates in place on rollback (they may have been edited).
    }
};
