<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bids')) {
            return;
        }

        Schema::table('bids', function (Blueprint $table) {
            if (! Schema::hasColumn('bids', 'vendor_response_status')) {
                $table->string('vendor_response_status', 50)
                    ->default('pending')
                    ->after('status');
            }

            if (! Schema::hasColumn('bids', 'vendor_responded_at')) {
                $table->timestamp('vendor_responded_at')
                    ->nullable()
                    ->after('responded_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bids')) {
            return;
        }

        Schema::table('bids', function (Blueprint $table) {
            if (Schema::hasColumn('bids', 'vendor_response_status')) {
                $table->dropColumn('vendor_response_status');
            }

            if (Schema::hasColumn('bids', 'vendor_responded_at')) {
                $table->dropColumn('vendor_responded_at');
            }
        });
    }
};
