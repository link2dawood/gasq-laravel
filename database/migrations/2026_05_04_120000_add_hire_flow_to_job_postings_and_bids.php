<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            if (! Schema::hasColumn('job_postings', 'hired_bid_id')) {
                $table->unsignedBigInteger('hired_bid_id')->nullable()->after('status');
            }
            if (! Schema::hasColumn('job_postings', 'hired_at')) {
                $table->timestamp('hired_at')->nullable()->after('hired_bid_id');
            }
            if (! Schema::hasColumn('job_postings', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('hired_at');
            }
            if (! Schema::hasColumn('job_postings', 'close_reason')) {
                $table->string('close_reason', 64)->nullable()->after('closed_at');
            }
            if (! Schema::hasColumn('job_postings', 'close_reason_other')) {
                $table->string('close_reason_other', 500)->nullable()->after('close_reason');
            }
            if (! Schema::hasColumn('job_postings', 'hired_external_name')) {
                $table->string('hired_external_name', 255)->nullable()->after('close_reason_other');
            }
            if (! Schema::hasColumn('job_postings', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('hired_external_name');
            }
            if (! Schema::hasColumn('job_postings', 'inactivity_survey_sent_at')) {
                $table->timestamp('inactivity_survey_sent_at')->nullable()->after('last_activity_at');
            }
        });

        Schema::table('bids', function (Blueprint $table) {
            if (! Schema::hasColumn('bids', 'hired_at')) {
                $table->timestamp('hired_at')->nullable()->after('counter_offer_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            foreach ([
                'hired_bid_id', 'hired_at', 'closed_at', 'close_reason',
                'close_reason_other', 'hired_external_name',
                'last_activity_at', 'inactivity_survey_sent_at',
            ] as $col) {
                if (Schema::hasColumn('job_postings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('bids', function (Blueprint $table) {
            if (Schema::hasColumn('bids', 'hired_at')) {
                $table->dropColumn('hired_at');
            }
        });
    }
};
