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

        // One editable exchange-rate setting per non-USD currency, so each market's
        // USD→local rate can be updated from Admin → Settings without a deploy.
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
