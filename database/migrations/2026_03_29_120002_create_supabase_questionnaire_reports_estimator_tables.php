<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('buyer_prequalification_responses')) {
            Schema::create('buyer_prequalification_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->text('prior_crimes_near_property')->nullable();
                $table->text('regular_crime_analysis')->nullable();
                $table->text('unsafe_reports')->nullable();
                $table->json('current_security_measures')->nullable();
                $table->text('previous_security_vendors')->nullable();
                $table->text('written_security_policies')->nullable();
                $table->text('liability_insurance')->nullable();
                $table->text('mutual_hold_harmless_agreement')->nullable();
                $table->text('sb68_awareness')->nullable();
                $table->text('decision_maker')->nullable();
                $table->text('security_budget_type')->nullable();
                $table->text('decision_maker_type')->nullable();
                $table->text('budget_allocated')->nullable();
                $table->text('budget_range')->nullable();
                $table->text('service_type')->nullable();
                $table->text('coverage_hours')->nullable();
                $table->text('insurance_requirement')->nullable();
                $table->text('services_without_insurance')->nullable();
                $table->text('decision_timeline')->nullable();
                $table->text('selection_factor')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('buyer_questionnaire_responses')) {
            Schema::create('buyer_questionnaire_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('contact_name')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone')->nullable();
                $table->string('company_name')->nullable();
                $table->text('property_address')->nullable();
                $table->string('organization_type')->nullable();
                $table->string('organization_type_other')->nullable();
                $table->json('security_services')->nullable();
                $table->text('security_services_other')->nullable();
                $table->date('desired_start_date')->nullable();
                $table->date('desired_end_date')->nullable();
                $table->string('hours_per_week')->nullable();
                $table->string('budget_allocated')->nullable();
                $table->string('budget_range')->nullable();
                $table->string('require_multiple_bids')->nullable();
                $table->text('require_multiple_bids_explain')->nullable();
                $table->string('decision_maker_role')->nullable();
                $table->string('board_authorization')->nullable();
                $table->string('procurement_timeline')->nullable();
                $table->string('follow_up_method')->nullable();
                $table->string('current_threat_level')->nullable();
                $table->string('current_vulnerability_level')->nullable();
                $table->text('prior_crimes_on_property')->nullable();
                $table->text('prior_crimes_details')->nullable();
                $table->json('current_security_measures')->nullable();
                $table->string('can_add_to_insurance')->nullable();
                $table->string('mutual_hold_harmless')->nullable();
                $table->string('agree_to_risk_assessment')->nullable();
                $table->string('recent_risk_assessment')->nullable();
                $table->string('known_crime_stats')->nullable();
                $table->text('crimes_against_people')->nullable();
                $table->text('crimes_against_property')->nullable();
                $table->string('budget_verified')->nullable();
                $table->string('know_inhouse_cost')->nullable();
                $table->string('price_shopping_only')->nullable();
                $table->string('working_capital_required')->nullable();
                $table->text('regular_crime_analysis')->nullable();
                $table->text('safety_reports')->nullable();
                $table->text('previous_security_vendors')->nullable();
                $table->text('previous_vendor_details')->nullable();
                $table->text('written_security_policies')->nullable();
                $table->text('liability_insurance')->nullable();
                $table->string('aware_of_sb68')->nullable();
                $table->text('direct_warnings')->nullable();
                $table->text('pattern_of_risk')->nullable();
                $table->text('should_have_known')->nullable();
                $table->text('crimes_within_500_yards')->nullable();
                $table->text('onsite_person_crimes')->nullable();
                $table->text('nearby_safety_reports')->nullable();
                $table->text('property_crime_experience')->nullable();
                $table->text('security_breaches')->nullable();
                $table->text('public_access_points')->nullable();
                $table->text('lighting_conditions')->nullable();
                $table->text('current_staffing')->nullable();
                $table->text('threat_perception')->nullable();
                $table->text('preparedness')->nullable();
                $table->string('coverage_type')->nullable();
                $table->string('insurance_requirement')->nullable();
                $table->string('decision_timeline')->nullable();
                $table->string('important_factor')->nullable();
                $table->integer('cri_score')->nullable();
                $table->integer('crs_score')->nullable();
                $table->integer('bqs_score')->nullable();
                $table->integer('bfi_score')->nullable();
                $table->string('risk_level')->nullable();
                $table->string('critical_level')->nullable();
                $table->string('buyer_fit')->nullable();
                $table->timestamps();
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('economic_justification_reports')) {
            Schema::create('economic_justification_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('company_name')->nullable();
                $table->string('prepared_by')->nullable();
                $table->string('prepared_for')->nullable();
                $table->json('calculation_data');
                $table->text('comments')->nullable();
                $table->json('uploaded_files')->nullable();
                $table->timestamps();
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('gasq_estimator_progress')) {
            Schema::create('gasq_estimator_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('user_type')->nullable();
                $table->boolean('step_1_complete')->default(false);
                $table->boolean('step_2_complete')->default(false);
                $table->boolean('step_3_complete')->default(false);
                $table->boolean('step_4_complete')->default(false);
                $table->boolean('general_questions_complete')->default(false);
                $table->boolean('buyer_questions_complete')->default(false);
                $table->boolean('vendor_questions_complete')->default(false);
                $table->boolean('crime_questions_complete')->default(false);
                $table->json('general_questions_data')->nullable();
                $table->json('buyer_questions_data')->nullable();
                $table->json('vendor_questions_data')->nullable();
                $table->json('crime_questions_data')->nullable();
                $table->json('calculator_data')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gasq_estimator_progress');
        Schema::dropIfExists('economic_justification_reports');
        Schema::dropIfExists('buyer_questionnaire_responses');
        Schema::dropIfExists('buyer_prequalification_responses');
    }
};
