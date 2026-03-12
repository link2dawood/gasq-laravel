<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align job_postings and bids with Supabase table/column structure (MySQL).
     */
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('zip_code')->nullable()->after('location');
            $table->json('coverage_days')->nullable()->after('service_end_date');
            $table->time('daily_start_time')->nullable()->after('coverage_days');
            $table->time('daily_end_time')->nullable()->after('daily_start_time');
            $table->boolean('is_private')->default(false)->after('description');
            $table->string('status', 50)->default('draft')->after('is_private'); // draft, open, closed, awarded
        });

        Schema::table('bids', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('proposal');
            $table->unique(['job_posting_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['zip_code', 'coverage_days', 'daily_start_time', 'daily_end_time', 'is_private', 'status']);
        });

        Schema::table('bids', function (Blueprint $table) {
            $table->dropUnique(['job_posting_id', 'user_id']);
            $table->dropColumn('submitted_at');
        });
    }
};
