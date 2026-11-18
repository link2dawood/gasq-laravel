<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many security personnel the vendor is staffing this estimate with.
 *
 * Nullable: estimates written before this column existed never captured it, and
 * a zero there would read as "no staff" rather than "not stated".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_lead_estimates', function (Blueprint $table) {
            $table->unsignedSmallInteger('staff_required')->nullable()->after('weeks_per_year');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_lead_estimates', function (Blueprint $table) {
            $table->dropColumn('staff_required');
        });
    }
};
