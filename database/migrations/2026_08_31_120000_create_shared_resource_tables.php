<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verified Shared Resource Rate(tm) — data model.
 *
 * The governing rule (spec RULE 7/8): 1,000 verified active weekly billable hours earns a
 * vendor the right to *apply* for Shared Resource pricing. It does not approve the rate.
 * Approval additionally requires a complete line-item bill-rate breakdown that reconciles
 * mathematically, validated shared allocations, vendor certification and GASQ approval.
 *
 * Three tables rather than columns on `users` so that both audit history (spec §44) and
 * reverification (§45) fall out naturally — each submission is a row, and the vendor's
 * current standing is derived from the latest one.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Evidence of operating scale. One row per submission, so expiry and
        // re-verification keep their history instead of overwriting it.
        Schema::create('vendor_operating_volumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('weekly_billable_hours', 10, 2)->default(0);
            $table->unsignedInteger('account_count')->nullable();

            // not_verified | pending | not_eligible | eligible_for_review | rejected | expired
            $table->string('status', 40)->default('pending');

            $table->text('evidence_notes')->nullable();
            $table->json('evidence_documents')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();
            $table->index(['vendor_id', 'status']);
        });

        // A submitted bill-rate structure. Versioned per vendor so a correction after a
        // failed reconciliation is a new version, not a silent edit of the rejected one.
        Schema::create('bill_rate_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_posting_id')->nullable()->constrained('job_postings')->cascadeOnDelete();

            $table->unsignedInteger('version')->default(1);

            // standard | shared_resource
            $table->string('pricing_model', 32)->default('standard');

            // The rate the vendor proposes. Line items must sum to this (spec §38).
            $table->decimal('proposed_bill_rate', 10, 2)->default(0);
            $table->decimal('line_items_total', 10, 2)->default(0);
            $table->boolean('reconciles')->default(false);

            // draft | submitted | financial_review_pending | approved | rejected | expired
            $table->string('status', 40)->default('draft');

            // Spec §40 — material recurring/conditional charges must be surfaced up front.
            $table->json('additional_charges')->nullable();

            // Spec §41 — vendor certification of accuracy.
            $table->boolean('certified')->default(false);
            $table->timestamp('certified_at')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();
            $table->index(['vendor_id', 'status']);
            $table->unique(['vendor_id', 'job_posting_id', 'version'], 'brb_vendor_job_version_unique');
        });

        // Individual cost lines. Spec §35: every line declares what kind of resource it is,
        // which is what makes "shared" auditable rather than merely asserted.
        Schema::create('bill_rate_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_rate_breakdown_id')
                ->constrained('bill_rate_breakdowns')
                ->cascadeOnDelete();

            // direct_labor | employer_costs | workforce_maintenance | operating |
            // supervision | administrative | insurance | vehicle | profit
            $table->string('category', 40);
            $table->string('label');

            // Hourly amount included in the customer bill rate.
            $table->decimal('amount', 10, 4)->default(0);

            // direct_labor | dedicated | shared | account_specific
            $table->string('resource_classification', 32)->default('dedicated');

            // Required when classification = shared (spec §36): what is shared, how it was
            // allocated, and across how much volume.
            $table->text('allocation_method')->nullable();
            $table->unsignedInteger('shared_across_accounts')->nullable();
            $table->decimal('unallocated_amount', 10, 4)->nullable();

            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->index(['bill_rate_breakdown_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_rate_line_items');
        Schema::dropIfExists('bill_rate_breakdowns');
        Schema::dropIfExists('vendor_operating_volumes');
    }
};
