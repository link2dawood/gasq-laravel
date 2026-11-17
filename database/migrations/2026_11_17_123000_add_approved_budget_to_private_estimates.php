<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The approved budget amount behind budget_status.
 *
 * budget_status already records whether funding is approved (spec 8). The
 * comparison report has to reconcile the agreed price against the figure the
 * buyer actually had approved, so the amount and the basis it was quoted on
 * are stored alongside it. Basis matters: "$12,000 approved" is a different
 * estimate monthly than annually.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_estimates', function (Blueprint $table) {
            if (! Schema::hasColumn('private_estimates', 'budget_amount')) {
                $table->decimal('budget_amount', 14, 2)->nullable()->after('budget_status');
            }
            if (! Schema::hasColumn('private_estimates', 'budget_basis')) {
                // hourly | weekly | monthly | annual
                $table->string('budget_basis', 20)->nullable()->after('budget_amount');
            }
            if (! Schema::hasColumn('private_estimates', 'budget_approved_at')) {
                $table->timestamp('budget_approved_at')->nullable()->after('budget_basis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('private_estimates', function (Blueprint $table) {
            foreach (['budget_amount', 'budget_basis', 'budget_approved_at'] as $column) {
                if (Schema::hasColumn('private_estimates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
