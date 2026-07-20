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

        // Seed the platform currency so admins can switch it from the Settings
        // page (enter USD or CAD). Defaults to USD — nothing changes until flipped.
        if (! DB::table('settings')->where('key', 'currency')->exists()) {
            DB::table('settings')->insert([
                'key' => 'currency',
                'value' => 'USD',
                'group' => 'localization',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'currency')->delete();
        }
    }
};
