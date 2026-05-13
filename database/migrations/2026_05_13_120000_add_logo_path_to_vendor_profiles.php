<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_profiles')) {
            return;
        }
        Schema::table('vendor_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_profiles', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendor_profiles') || ! Schema::hasColumn('vendor_profiles', 'logo_path')) {
            return;
        }
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
