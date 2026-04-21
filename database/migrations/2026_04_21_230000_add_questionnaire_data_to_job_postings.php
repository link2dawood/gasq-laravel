<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_postings')) {
            return;
        }

        Schema::table('job_postings', function (Blueprint $table) {
            if (! Schema::hasColumn('job_postings', 'questionnaire_data')) {
                $table->json('questionnaire_data')->nullable()->after('special_requirements');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_postings') || ! Schema::hasColumn('job_postings', 'questionnaire_data')) {
            return;
        }

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('questionnaire_data');
        });
    }
};
