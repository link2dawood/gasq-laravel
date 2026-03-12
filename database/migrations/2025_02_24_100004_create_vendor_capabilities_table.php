<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Match Supabase vendor_capabilities table structure (MySQL).
     */
    public function up(): void
    {
        Schema::create('vendor_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('legal_business_name')->nullable();
            $table->string('dba_name')->nullable();
            $table->string('business_structure')->nullable();
            $table->text('business_address')->nullable();
            $table->string('website')->nullable();
            $table->string('duns_number')->nullable();
            $table->string('uei_number')->nullable();
            $table->string('cage_code')->nullable();
            $table->string('business_license_number')->nullable();
            $table->json('states_licensed')->nullable();
            $table->json('core_competencies')->nullable();
            $table->json('past_performance')->nullable();
            $table->text('differentiators')->nullable();
            $table->json('certifications')->nullable();
            $table->json('naics_codes')->nullable();
            $table->json('psc_codes')->nullable();
            $table->string('general_liability_insurance')->nullable();
            $table->string('professional_liability_insurance')->nullable();
            $table->string('workers_comp_coverage')->nullable();
            $table->string('bonding_capacity')->nullable();
            $table->json('service_areas')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('authorized_rep_name')->nullable();
            $table->string('authorized_rep_title')->nullable();
            $table->date('signature_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_capabilities');
    }
};
