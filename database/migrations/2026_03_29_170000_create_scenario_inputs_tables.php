<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('workbook_version', 32)->default('V24');
            $table->json('assumptions')->nullable();
            $table->json('vehicle')->nullable();
            $table->json('meta')->nullable();
            $table->json('coverage_snapshot')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('scenario_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained('scenarios')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('name')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 64)->nullable();
            $table->string('zip', 32)->nullable();
            $table->string('country', 64)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('google_place_id')->nullable();
            $table->timestamps();

            $table->index(['scenario_id', 'sort_order']);
        });

        Schema::create('scenario_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained('scenarios')->cascadeOnDelete();
            $table->foreignId('scenario_site_id')->nullable()->constrained('scenario_sites')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('post_name')->nullable();
            $table->string('position_title')->nullable();
            $table->string('location_text')->nullable();
            $table->unsignedInteger('qty_required')->default(1);
            $table->decimal('weekly_hours', 10, 2)->default(0);
            $table->string('pay_rate_mode', 16)->default('AUTO');
            $table->string('wage_mode', 16)->default('AUTO');
            $table->decimal('manual_pay_wage', 14, 4)->nullable();
            $table->decimal('manual_bill_rate', 14, 4)->nullable();
            $table->timestamps();

            $table->index(['scenario_id', 'sort_order']);
        });

        Schema::create('scenario_scope_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->unique()->constrained('scenarios')->cascadeOnDelete();
            $table->decimal('hours_coverage_per_day', 8, 2);
            $table->decimal('days_coverage_per_week', 8, 2);
            $table->decimal('weeks_of_coverage', 8, 2)->default(52);
            $table->decimal('staff_per_8hr_shift', 8, 2)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('scenario_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained('scenarios')->cascadeOnDelete();
            $table->foreignId('scenario_post_id')->nullable()->constrained('scenario_posts')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('label')->nullable();
            $table->decimal('hours_per_week', 10, 2)->default(0);
            $table->json('pattern')->nullable();
            $table->timestamps();

            $table->index(['scenario_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_shifts');
        Schema::dropIfExists('scenario_scope_requirements');
        Schema::dropIfExists('scenario_posts');
        Schema::dropIfExists('scenario_sites');
        Schema::dropIfExists('scenarios');
    }
};
