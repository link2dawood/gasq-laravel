<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GASQ Private Estimate by Invitation — schema (spec sections 60-67).
 *
 * The flow this supports: vendor invites → buyer verifies by email OTP → buyer
 * defines scope → vendor reviews → scope confirmed → GASQ prices it → buyer
 * views online → PDF stays locked until the vendor releases the password.
 *
 * Two rules drive the shape:
 *   - Nothing is overwritten. Scope and pricing are versioned rows, and every
 *     state change lands in estimate_activity. Spec 17 and 44.
 *   - The buyer needs no account. Access is an invitation token plus an email
 *     OTP, so these tables carry the buyer's identity themselves rather than
 *     leaning on users. buyer_id is set only if the buyer happens to have one.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── The estimate itself ─────────────────────────────────────────────
        if (! Schema::hasTable('private_estimates')) {
            Schema::create('private_estimates', function (Blueprint $table) {
                $table->id();
                // Public-facing number, e.g. PE-260919-0048. Never expose the id.
                $table->string('public_id', 32)->unique();
                $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 48)->default('draft')->index();

                // Buyer identity captured at invitation, confirmed at intake.
                $table->string('company_name')->nullable();
                $table->string('buyer_name')->nullable();
                $table->string('buyer_title')->nullable();
                $table->string('buyer_email');
                $table->string('buyer_phone', 40)->nullable();
                $table->string('site_name')->nullable();
                $table->string('service_address')->nullable();
                $table->string('property_type', 80)->nullable();
                $table->unsignedInteger('location_count')->nullable();
                $table->date('estimated_start_date')->nullable();
                $table->timestamp('buyer_email_verified_at')->nullable();

                // Qualification answers (spec 7-9). Shown to the vendor.
                $table->string('decision_maker_status', 40)->nullable();
                $table->string('decision_maker_name')->nullable();
                $table->string('decision_maker_title')->nullable();
                $table->string('decision_maker_email')->nullable();
                $table->string('budget_status', 40)->nullable();
                $table->string('buyer_intent', 40)->nullable();

                // Current-state mirror of the confirmed scope and latest pricing,
                // so dashboards and lists never have to join the version tables.
                $table->unsignedInteger('current_scope_version')->default(0);
                $table->decimal('baseline_wage', 10, 4)->nullable();
                $table->decimal('weekly_hours', 10, 2)->nullable();
                $table->decimal('annual_hours', 12, 2)->nullable();
                $table->decimal('manpower_required', 10, 4)->nullable();
                $table->decimal('current_bill_rate', 10, 4)->nullable();
                $table->decimal('current_annual_cost', 14, 2)->nullable();
                $table->decimal('estimated_bill_rate', 10, 4)->nullable();
                $table->decimal('weekly_cost', 14, 2)->nullable();
                $table->decimal('monthly_cost', 14, 2)->nullable();
                $table->decimal('annual_cost', 14, 2)->nullable();
                $table->decimal('capital_recovery_opportunity', 14, 2)->nullable();

                // Lifecycle stamps the vendor dashboard and timeline read.
                $table->timestamp('valid_until')->nullable();
                $table->timestamp('scope_confirmed_at')->nullable();
                $table->timestamp('vendor_responded_at')->nullable();
                $table->timestamp('priced_at')->nullable();
                $table->timestamp('first_viewed_at')->nullable();
                $table->timestamp('review_confirmed_at')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->string('decline_reason', 60)->nullable();
                $table->string('decline_comparison', 60)->nullable();
                $table->decimal('decline_comparison_amount', 14, 2)->nullable();
                $table->text('decline_notes')->nullable();
                $table->string('award_status', 20)->nullable();
                $table->string('success_fee_model', 20)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->timestamp('closed_at')->nullable();

                $table->index(['vendor_id', 'status']);
                $table->index('buyer_email');
            });
        }

        // ── Invitations ─────────────────────────────────────────────────────
        // Only the hash is stored, so a database copy cannot reconstruct a live
        // link. The token itself is shown once, in the invitation email.
        if (! Schema::hasTable('estimate_invitations')) {
            Schema::create('estimate_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('private_estimate_id')->constrained()->cascadeOnDelete();
                $table->string('token_hash', 64)->unique();
                $table->string('invited_email');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('revoked_at')->nullable();
                $table->string('revoked_reason')->nullable();
                $table->timestamps();
            });
        }

        // ── Scope versions ──────────────────────────────────────────────────
        if (! Schema::hasTable('estimate_scopes')) {
            Schema::create('estimate_scopes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('private_estimate_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->string('service_type', 60)->nullable();
                $table->unsignedInteger('locations')->default(1);
                $table->unsignedInteger('posts')->default(1);
                $table->decimal('hours_per_day', 6, 2)->nullable();
                $table->decimal('days_per_week', 4, 2)->nullable();
                $table->decimal('weeks_per_year', 5, 2)->default(52);
                $table->decimal('weekly_hours', 10, 2)->nullable();
                $table->decimal('annual_hours', 12, 2)->nullable();
                $table->decimal('guards_required', 10, 4)->nullable();
                $table->decimal('baseline_wage', 10, 4)->nullable();
                $table->date('start_date')->nullable();
                $table->unsignedInteger('contract_term')->nullable();
                // Everything else the buyer answered, plus the reason this
                // version exists and what moved the price against the last one.
                $table->json('scope_json')->nullable();
                $table->string('change_reason')->nullable();
                $table->json('changed_fields')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('created_by_role', 20)->default('buyer');
                $table->timestamp('buyer_approved_at')->nullable();
                $table->timestamp('vendor_approved_at')->nullable();
                $table->string('vendor_decline_reason')->nullable();
                $table->timestamps();

                $table->unique(['private_estimate_id', 'version'], 'est_scope_version_unique');
            });
        }

        // ── Pricing snapshots, one per scope version ────────────────────────
        if (! Schema::hasTable('estimate_pricing')) {
            Schema::create('estimate_pricing', function (Blueprint $table) {
                $table->id();
                $table->foreignId('private_estimate_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('scope_version');
                $table->decimal('base_wage', 10, 4)->nullable();
                $table->decimal('health_welfare', 10, 4)->default(0);
                $table->decimal('locality_pay', 10, 4)->default(0);
                $table->decimal('shift_differential', 10, 4)->default(0);
                $table->decimal('employer_burden', 10, 4)->nullable();
                $table->decimal('workforce_maintenance_hours', 10, 2)->nullable();
                $table->decimal('workforce_maintenance_cost', 14, 4)->nullable();
                $table->decimal('other_direct_cost', 10, 4)->default(0);
                $table->decimal('general_admin', 10, 4)->default(0);
                $table->decimal('profit_margin', 6, 4)->nullable();
                $table->decimal('cost_to_deliver', 10, 4)->nullable();
                $table->decimal('final_bill_rate', 10, 4)->nullable();
                $table->decimal('weekly_cost', 14, 2)->nullable();
                $table->decimal('monthly_cost', 14, 2)->nullable();
                $table->decimal('annual_cost', 14, 2)->nullable();
                $table->decimal('capital_recovery', 14, 2)->nullable();
                // Raw engine inputs and outputs, so an approved estimate can be
                // reproduced exactly even after defaults change.
                $table->json('inputs')->nullable();
                $table->json('outputs')->nullable();
                $table->string('calculation_version', 20)->nullable();
                $table->timestamps();

                $table->unique(['private_estimate_id', 'scope_version'], 'est_pricing_version_unique');
            });
        }

        // ── Locked documents and their vendor-released passwords ────────────
        if (! Schema::hasTable('estimate_documents')) {
            Schema::create('estimate_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('private_estimate_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('scope_version');
                $table->string('pdf_path')->nullable();
                // Encrypted at rest; only the vendor may reveal it.
                $table->text('encrypted_password')->nullable();
                $table->unsignedInteger('password_version')->default(1);
                $table->timestamp('generated_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('password_requested_at')->nullable();
                $table->timestamp('password_released_at')->nullable();
                $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('password_regenerated_at')->nullable();
                $table->unsignedInteger('download_limit')->nullable();
                $table->unsignedInteger('download_count')->default(0);
                $table->timestamp('first_downloaded_at')->nullable();
                $table->timestamp('last_downloaded_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->index(['private_estimate_id', 'scope_version']);
            });
        }

        // ── Activity trail (spec 65-66) ─────────────────────────────────────
        // analytics_events stays the funnel log; this is the per-estimate
        // timeline both sides see, and it is never deleted.
        if (! Schema::hasTable('estimate_activity')) {
            Schema::create('estimate_activity', function (Blueprint $table) {
                $table->id();
                $table->foreignId('private_estimate_id')->constrained()->cascadeOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_role', 20)->default('system');
                $table->string('event_type', 60);
                $table->json('event_data')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['private_estimate_id', 'created_at']);
            });
        }

        // ── Email OTP for buyers who have no account ────────────────────────
        // verification_codes was phone-only: user_id was required, which an
        // invited buyer cannot satisfy. Widen it rather than add a second table.
        Schema::table('verification_codes', function (Blueprint $table) {
            if (Schema::hasColumn('verification_codes', 'user_id')) {
                $table->foreignId('user_id')->nullable()->change();
            }
        });
        if (! Schema::hasColumn('verification_codes', 'context')) {
            Schema::table('verification_codes', function (Blueprint $table) {
                // What the code is for, e.g. private_estimate:41.
                $table->string('context', 80)->nullable()->after('type');
                $table->index(['email', 'type', 'status'], 'verification_codes_email_type_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_activity');
        Schema::dropIfExists('estimate_documents');
        Schema::dropIfExists('estimate_pricing');
        Schema::dropIfExists('estimate_scopes');
        Schema::dropIfExists('estimate_invitations');
        Schema::dropIfExists('private_estimates');

        if (Schema::hasColumn('verification_codes', 'context')) {
            Schema::table('verification_codes', function (Blueprint $table) {
                $table->dropIndex('verification_codes_email_type_status');
                $table->dropColumn('context');
            });
        }
    }
};
