<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer-side job-offer workflow checklist columns: interviews, on-site risk
 * assessment, final verification gate, and a high-level "offer_status" enum
 * (open / hired / closed_no_hire). Drives the buyer/admin dashboard summary.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_postings')) {
            return;
        }
        Schema::table('job_postings', function (Blueprint $table) {
            if (! Schema::hasColumn('job_postings', 'offer_status')) {
                // open | hired | closed_no_hire
                $table->string('offer_status', 32)->default('open')->after('status');
            }
            if (! Schema::hasColumn('job_postings', 'interviews_scheduled')) {
                $table->boolean('interviews_scheduled')->nullable()->after('offer_status');
            }
            if (! Schema::hasColumn('job_postings', 'interviews_completed')) {
                $table->boolean('interviews_completed')->nullable()->after('interviews_scheduled');
            }
            if (! Schema::hasColumn('job_postings', 'risk_assessment_scheduled')) {
                $table->boolean('risk_assessment_scheduled')->nullable()->after('interviews_completed');
            }
            if (! Schema::hasColumn('job_postings', 'risk_assessment_completed')) {
                $table->boolean('risk_assessment_completed')->nullable()->after('risk_assessment_scheduled');
            }
            if (! Schema::hasColumn('job_postings', 'final_verifications_complete')) {
                $table->boolean('final_verifications_complete')->nullable()->after('risk_assessment_completed');
            }
            if (! Schema::hasColumn('job_postings', 'service_end_date')) {
                // Used as the displayed "Estimated Close-Out Date" — bid window close.
                $table->date('service_end_date')->nullable()->after('service_start_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_postings')) {
            return;
        }
        Schema::table('job_postings', function (Blueprint $table) {
            foreach (['offer_status', 'interviews_scheduled', 'interviews_completed', 'risk_assessment_scheduled', 'risk_assessment_completed', 'final_verifications_complete'] as $col) {
                if (Schema::hasColumn('job_postings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
