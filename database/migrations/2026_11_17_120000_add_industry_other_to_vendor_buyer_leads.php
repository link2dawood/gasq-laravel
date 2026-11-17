<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the buyer typed when they picked "Other" as their business industry.
 *
 * Kept beside `industry` rather than written into it, so the list of business
 * types stays a closed set that can be counted and filtered, and the buyer's own
 * wording is still carried to the vendor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_buyer_leads', function (Blueprint $table) {
            $table->string('industry_other', 100)->nullable()->after('industry');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_buyer_leads', function (Blueprint $table) {
            $table->dropColumn('industry_other');
        });
    }
};
