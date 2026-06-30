<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('pricing_plans', 'monthly_price')) {
                // Recurring monthly price. Null = no monthly option for this plan.
                $table->decimal('monthly_price', 10, 2)->nullable()->after('price');
            }
        });

        // Default monthly price = the one-time price (adjustable in admin / seeder).
        DB::table('pricing_plans')->whereNull('monthly_price')->update(['monthly_price' => DB::raw('price')]);
    }

    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_plans', 'monthly_price')) {
                $table->dropColumn('monthly_price');
            }
        });
    }
};
