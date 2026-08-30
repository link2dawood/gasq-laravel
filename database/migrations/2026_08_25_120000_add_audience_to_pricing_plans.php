<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add the `audience` column PageController::renderPricing() has always filtered
 * on but that no migration ever created. Because Schema::hasColumn() returned
 * false, the audience filter silently no-opped and /pricing/buyers and
 * /pricing/vendors rendered an identical set of plans.
 *
 * The seeded Starter/Professional/Enterprise packs sell wallet credits, and
 * credits are a vendor-only currency: EnsureCreditsForCalculator returns early
 * for any non-vendor ("Buyers and admins access calculators for free"), so a
 * buyer never spends a credit. Those packs are therefore marked 'vendor'.
 *
 * Buyers are not billed from pricing_plans at all — buyer tooling is free and a
 * GASQ Certified(tm) Appraisal is charged per engagement through Stripe
 * (InstantEstimatorFeeCheckoutController), so the buyer page renders its own
 * static tiers rather than rows from this table.
 */
return new class extends Migration
{
    /** Credit packs that predate the audience split; all are vendor-side. */
    private array $vendorPlans = ['Starter', 'Professional', 'Enterprise'];

    public function up(): void
    {
        if (! Schema::hasTable('pricing_plans')) {
            return;
        }

        if (! Schema::hasColumn('pricing_plans', 'audience')) {
            Schema::table('pricing_plans', function (Blueprint $table) {
                $table->string('audience')->default('all')->after('name');
            });
        }

        DB::table('pricing_plans')
            ->whereIn('name', $this->vendorPlans)
            ->update(['audience' => 'vendor']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('pricing_plans') || ! Schema::hasColumn('pricing_plans', 'audience')) {
            return;
        }

        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
