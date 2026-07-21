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

        // USD→CAD exchange rate, editable from Admin → Settings without a deploy.
        // App\Support\Currency::rate() reads this, falling back to config/env.
        if (! DB::table('settings')->where('key', 'exchange_rate_cad')->exists()) {
            DB::table('settings')->insert([
                'key' => 'exchange_rate_cad',
                'value' => '1.41',
                'group' => 'localization',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'exchange_rate_cad')->delete();
        }
    }
};
