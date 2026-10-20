<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appraisal_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('calculator_state_id')->constrained('calculator_states');
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('report_number')->unique();
            $table->timestamp('prepared_at')->nullable();
            $table->json('findings')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('pdf_sha256', 64)->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
        Schema::create('appraisal_appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appraisal_review_id')->constrained('appraisal_reviews');
            $table->string('invitee_uri')->unique();
            $table->string('event_uri');
            $table->string('status')->default('scheduled');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone', 64);
            $table->text('meeting_details');
            $table->text('reschedule_url');
            $table->text('cancel_url');
            $table->timestamp('source_created_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'starts_at']);
        });
        Schema::create('appraisal_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('appraisal_review_id')->constrained('appraisal_reviews');
            $table->foreignUuid('appraisal_appointment_id')->constrained('appraisal_appointments');
            $table->string('delivery_key')->unique();
            $table->string('kind');
            $table->timestamp('appointment_starts_at');
            $table->string('status')->default('pending')->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisal_deliveries');
        Schema::dropIfExists('appraisal_appointments');
        Schema::dropIfExists('appraisal_reviews');
    }
};
