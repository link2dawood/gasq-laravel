<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline wage on the opportunity (review spec §8, P0-3).
 *
 * The wage assumption the whole financial analysis rests on. It is deliberately NOT the
 * vendor bill rate — it is the labour assumption vendors must demonstrate their pricing
 * can support, which is why it is disclosed to them while the buyer's True Cost to Protect
 * is not (§9).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_postings')) {
            return;
        }

        Schema::table('job_postings', function (Blueprint $table) {
            if (! Schema::hasColumn('job_postings', 'baseline_wage')) {
                $table->decimal('baseline_wage', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('job_postings', 'baseline_wage_source')) {
                // current_employee | existing_vendor | current_contract | buyer_assumption
                // | local_market | living_wage | collective_agreement | other
                $table->string('baseline_wage_source', 40)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_postings')) {
            return;
        }

        Schema::table('job_postings', function (Blueprint $table) {
            foreach (['baseline_wage', 'baseline_wage_source'] as $col) {
                if (Schema::hasColumn('job_postings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
