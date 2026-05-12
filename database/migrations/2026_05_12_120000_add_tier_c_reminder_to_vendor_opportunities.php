<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_opportunities')) {
            return;
        }
        Schema::table('vendor_opportunities', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_opportunities', 'tier_c_reminder_sent_at')) {
                $table->timestamp('tier_c_reminder_sent_at')->nullable()->after('vendor_target_count');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendor_opportunities') || ! Schema::hasColumn('vendor_opportunities', 'tier_c_reminder_sent_at')) {
            return;
        }
        Schema::table('vendor_opportunities', function (Blueprint $table) {
            $table->dropColumn('tier_c_reminder_sent_at');
        });
    }
};
