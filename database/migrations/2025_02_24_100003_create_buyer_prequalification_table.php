<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Match Supabase buyer_prequalification table structure (MySQL).
     */
    public function up(): void
    {
        Schema::create('buyer_prequalification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_decision_maker')->nullable();
            $table->boolean('has_approved_budget')->nullable();
            $table->boolean('needs_services_within_45_days')->nullable();
            $table->boolean('has_inhouse_security_staff')->nullable();
            $table->boolean('has_current_vendor_onsite')->nullable();
            $table->boolean('wants_vendor_replacement_guarantee')->nullable();
            $table->boolean('wants_price_lock_guarantee')->nullable();
            $table->boolean('knows_inhouse_cost')->nullable();
            $table->boolean('requires_livable_wage')->nullable();
            $table->boolean('requires_health_benefits')->nullable();
            $table->boolean('requires_insurance')->nullable();
            $table->boolean('requires_working_capital_45_60_days')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_prequalification');
    }
};
