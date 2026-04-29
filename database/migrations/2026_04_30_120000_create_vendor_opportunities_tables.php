<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->string('lead_tier', 20);
            $table->string('status', 50)->default('ready');
            $table->boolean('decision_maker_verified')->default(false);
            $table->boolean('budget_confirmed')->default(false);
            $table->boolean('scope_completed')->default(false);
            $table->boolean('timeline_ready')->default(false);
            $table->boolean('move_forward_confirmed')->default(false);
            $table->decimal('estimated_annual_contract_value', 12, 2)->nullable();
            $table->unsignedInteger('vendor_target_count')->default(0);
            $table->unsignedInteger('max_accepts')->default(5);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique('job_posting_id');
            $table->index(['lead_tier', 'status']);
        });

        Schema::create('vendor_opportunity_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_opportunity_id')->constrained('vendor_opportunities')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('invite_key', 64)->unique();
            $table->string('status', 50)->default('new');
            $table->unsignedInteger('credits_to_unlock')->default(0);
            $table->foreignId('credits_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->decimal('match_score', 6, 2)->nullable();
            $table->json('match_reasons')->nullable();
            $table->string('decline_reason', 100)->nullable();
            $table->text('decline_reason_other')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('bid_submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('first_reminder_sent_at')->nullable();
            $table->timestamp('final_notice_sent_at')->nullable();
            $table->timestamp('accepted_bid_reminder_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['vendor_opportunity_id', 'vendor_id']);
            $table->index(['status', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_opportunity_invitations');
        Schema::dropIfExists('vendor_opportunities');
    }
};
