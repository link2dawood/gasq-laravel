<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_lead_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_buyer_lead_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('title');
            $table->json('buyer');
            $table->string('currency', 3);
            $table->text('scope');
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('weekly_hours', 8, 2);
            $table->unsignedTinyInteger('weeks_per_year');
            $table->decimal('one_time_cost', 12, 2)->default(0);
            $table->date('valid_until');
            $table->text('terms')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->index(['vendor_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_lead_estimates');
    }
};
