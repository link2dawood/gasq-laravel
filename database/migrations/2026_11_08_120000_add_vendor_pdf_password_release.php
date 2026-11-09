<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_estimate_deliveries', function (Blueprint $table) {
            $table->string('password_hash')->nullable();
            $table->text('released_password')->nullable();
            $table->timestamp('password_released_at')->nullable();
            $table->foreignId('password_released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('release_appointment_id')->nullable()->constrained('vendor_review_appointments')->nullOnDelete();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('access_revoked_at')->nullable();
            $table->string('external_review_reference')->nullable();
            $table->dateTime('external_review_started_at')->nullable();
            $table->dateTime('external_review_ended_at')->nullable();
        });
        Schema::table('vendor_review_appointments', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('vendor_pdf_access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_lead_estimate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_estimate_delivery_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 50);
            $table->string('pdf_sha256', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['vendor_estimate_delivery_id', 'created_at'], 'vendor_pdf_event_delivery_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_pdf_access_events');
        Schema::table('vendor_review_appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn('reviewed_at');
        });
        Schema::table('vendor_estimate_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('password_released_by');
            $table->dropConstrainedForeignId('release_appointment_id');
            $table->dropColumn(['password_hash', 'released_password', 'password_released_at', 'access_expires_at', 'access_revoked_at', 'external_review_reference', 'external_review_started_at', 'external_review_ended_at']);
        });
    }
};
