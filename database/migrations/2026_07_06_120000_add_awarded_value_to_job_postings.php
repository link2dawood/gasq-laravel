<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            if (! Schema::hasColumn('job_postings', 'awarded_value')) {
                // Contract value stamped at hire time so "value won" is a real,
                // frozen figure (not re-derived from a live budget estimate).
                $table->decimal('awarded_value', 14, 2)->nullable()->after('hired_external_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            if (Schema::hasColumn('job_postings', 'awarded_value')) {
                $table->dropColumn('awarded_value');
            }
        });
    }
};
