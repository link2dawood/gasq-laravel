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
            if (! Schema::hasColumn('job_postings', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('location');
            }
            if (! Schema::hasColumn('job_postings', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('job_postings', 'google_place_id')) {
                $table->string('google_place_id', 255)->nullable()->after('longitude');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_postings')) {
            return;
        }

        Schema::table('job_postings', function (Blueprint $table) {
            if (Schema::hasColumn('job_postings', 'google_place_id')) {
                $table->dropColumn('google_place_id');
            }
            if (Schema::hasColumn('job_postings', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('job_postings', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });
    }
};
