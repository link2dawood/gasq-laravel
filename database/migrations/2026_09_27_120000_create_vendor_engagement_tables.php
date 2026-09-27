<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GASQ Vendor Access — the controlled ten-stage engagement a vendor works
 * through for one qualified opportunity: opportunity, accept or decline,
 * qualify, meet, assess, interview, solution, selection, price, success fee.
 *
 * The sequence is the product: qualifications first, operating solution next,
 * price last. So progress is stored per stage rather than as a single column,
 * a stage only opens when the ones before it are complete, and every move is
 * written to an append-only trail.
 *
 * This sits alongside vendor_opportunity_invitations rather than replacing it.
 * The invitation remains the access credential; the engagement is what the
 * vendor does once inside.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_engagements')) {
            Schema::create('vendor_engagements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_opportunity_invitation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vendor_opportunity_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

                $table->string('stage', 40)->default('opportunity');
                $table->string('status', 24)->default('active');

                $table->timestamp('started_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('declined_at')->nullable();
                $table->string('decline_reason')->nullable();
                $table->timestamp('adjustment_requested_at')->nullable();
                $table->text('adjustment_note')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                // One engagement per vendor per opportunity.
                $table->unique(['vendor_opportunity_id', 'vendor_id'], 'vendor_engagement_unique');
                $table->index(['vendor_id', 'status']);
            });
        }

        if (! Schema::hasTable('vendor_engagement_stages')) {
            Schema::create('vendor_engagement_stages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_engagement_id')->constrained()->cascadeOnDelete();
                $table->string('stage_key', 40);
                $table->unsignedSmallInteger('position');
                // locked · available · in_progress · complete · skipped
                $table->string('status', 20)->default('locked');
                // Saved answers, so a half-finished stage can be resumed.
                $table->json('payload')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['vendor_engagement_id', 'stage_key'], 'vendor_engagement_stage_unique');
            });
        }

        if (! Schema::hasTable('vendor_engagement_activity')) {
            Schema::create('vendor_engagement_activity', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_engagement_id')->constrained()->cascadeOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_role', 20)->default('system');
                $table->string('event_type', 60);
                $table->json('event_data')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['vendor_engagement_id', 'created_at'], 'vendor_engagement_activity_idx');
            });
        }

        // ── Invitation access controls ──────────────────────────────────────
        // The invitation already expires. These add limited use and revocation,
        // and record where a link was last used from, so a shared credential
        // shows up as one invitation opened from several addresses.
        Schema::table('vendor_opportunity_invitations', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_opportunity_invitations', 'max_uses')) {
                $table->unsignedSmallInteger('max_uses')->nullable()->after('expires_at');
            }
            if (! Schema::hasColumn('vendor_opportunity_invitations', 'use_count')) {
                $table->unsignedSmallInteger('use_count')->default(0)->after('max_uses');
            }
            if (! Schema::hasColumn('vendor_opportunity_invitations', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable()->after('use_count');
            }
            if (! Schema::hasColumn('vendor_opportunity_invitations', 'last_used_ip')) {
                $table->string('last_used_ip', 45)->nullable()->after('last_used_at');
            }
            if (! Schema::hasColumn('vendor_opportunity_invitations', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('last_used_ip');
            }
            if (! Schema::hasColumn('vendor_opportunity_invitations', 'revoked_by')) {
                $table->foreignId('revoked_by')->nullable()->after('revoked_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('vendor_opportunity_invitations', 'revoked_reason')) {
                $table->string('revoked_reason')->nullable()->after('revoked_by');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_engagement_activity');
        Schema::dropIfExists('vendor_engagement_stages');
        Schema::dropIfExists('vendor_engagements');

        Schema::table('vendor_opportunity_invitations', function (Blueprint $table) {
            foreach (['max_uses', 'use_count', 'last_used_at', 'last_used_ip', 'revoked_at', 'revoked_reason'] as $column) {
                if (Schema::hasColumn('vendor_opportunity_invitations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
