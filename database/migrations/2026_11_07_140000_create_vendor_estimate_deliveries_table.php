<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactional. Recover only the known, empty table
        // left by an interrupted first creation; never discard delivery records.
        if (Schema::hasTable('vendor_estimate_deliveries')) {
            if (! Schema::hasColumns('vendor_estimate_deliveries', ['request_key', 'snapshot', 'scheduling_token_hash', 'pdf_sha256']) || DB::table('vendor_estimate_deliveries')->exists()) {
                throw new RuntimeException('An existing vendor delivery table requires manual migration review; no records were changed.');
            }
            Schema::drop('vendor_estimate_deliveries');
        }
        Schema::create('vendor_estimate_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_lead_estimate_id')->constrained()->cascadeOnDelete();
            $table->uuid('request_key')->unique();
            $table->json('snapshot');
            $table->string('recipient');
            $table->text('scheduling_token');
            $table->string('scheduling_token_hash', 64)->unique();
            $table->text('scheduling_target');
            $table->timestamp('scheduling_expires_at');
            $table->timestamp('scheduling_opened_at')->nullable();
            $table->string('pdf_path');
            $table->string('pdf_sha256', 64);
            $table->string('status', 20)->default('queued');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->timestamps();
            $table->index(['vendor_lead_estimate_id', 'created_at'], 'vendor_est_delivery_estimate_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_estimate_deliveries');
    }
};
