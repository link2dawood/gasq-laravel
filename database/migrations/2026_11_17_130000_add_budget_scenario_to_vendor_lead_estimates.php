<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot of the Know Before You Bid scenario the estimate's dashboard is
     * drawn from.
     *
     * A snapshot rather than a live read of the vendor's calculator state: an
     * estimate sent in January must still print January's figures after the
     * vendor edits the calculator in March. The same reason
     * vendor_estimate_deliveries keeps a snapshot of the estimate itself.
     */
    public function up(): void
    {
        Schema::table('vendor_lead_estimates', function (Blueprint $table) {
            $table->json('budget_scenario')->nullable()->after('one_time_cost');
            $table->timestamp('budget_scenario_at')->nullable()->after('budget_scenario');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_lead_estimates', function (Blueprint $table) {
            $table->dropColumn(['budget_scenario', 'budget_scenario_at']);
        });
    }
};
