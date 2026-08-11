<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Correct the Enterprise plan feature wording: "Everything in Pro" ->
 * "Everything in Professional" (there is no "Pro" plan; the tier is
 * "Professional"). Updates the live pricing_plans rows in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pricing_plans')) {
            return;
        }

        foreach (DB::table('pricing_plans')->get() as $plan) {
            $features = json_decode($plan->features ?? '[]', true);
            if (! is_array($features)) {
                continue;
            }

            $updated = array_map(
                fn ($f) => $f === 'Everything in Pro' ? 'Everything in Professional' : $f,
                $features
            );

            if ($updated !== $features) {
                DB::table('pricing_plans')->where('id', $plan->id)
                    ->update(['features' => json_encode($updated)]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('pricing_plans')) {
            return;
        }

        foreach (DB::table('pricing_plans')->get() as $plan) {
            $features = json_decode($plan->features ?? '[]', true);
            if (! is_array($features)) {
                continue;
            }

            $reverted = array_map(
                fn ($f) => $f === 'Everything in Professional' ? 'Everything in Pro' : $f,
                $features
            );

            if ($reverted !== $features) {
                DB::table('pricing_plans')->where('id', $plan->id)
                    ->update(['features' => json_encode($reverted)]);
            }
        }
    }
};
