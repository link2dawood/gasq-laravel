<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->unique()->constrained('bids')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->string('status', 32)->default('draft'); // draft, submitted
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->json('responses')->nullable();
            $table->boolean('is_responsive')->nullable();
            $table->json('responsive_failures')->nullable();
            $table->boolean('is_responsible')->nullable();
            $table->json('responsible_failures')->nullable();
            $table->string('buyer_review_token', 64)->nullable()->unique();
            $table->timestamp('buyer_review_expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('vendor_questionnaire_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_questionnaire_id')
                ->constrained('vendor_questionnaires')
                ->cascadeOnDelete();
            $table->foreignId('file_upload_id')->constrained('file_uploads')->cascadeOnDelete();
            $table->string('document_type', 64); // state_security_license, coi, w9, capability_statement, workers_comp, general_liability, business_license
            $table->boolean('prefilled_from_profile')->default(false);
            $table->timestamps();
            $table->unique(['vendor_questionnaire_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_questionnaire_documents');
        Schema::dropIfExists('vendor_questionnaires');
    }
};
