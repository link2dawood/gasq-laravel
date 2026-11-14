<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carry the full GASQ Security Services Opportunity Qualification Form on the lead.
 *
 * Contact, budget and coverage answers become columns because the vendor filters,
 * sorts and prices from them. The 24 Yes/No answers stay in one JSON column: they
 * are read as a set, never queried individually, and the form's question list is
 * owned by App\Support\BuyerQualificationQuestionnaire rather than by the schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_buyer_leads', function (Blueprint $table) {
            $table->string('contact_title')->nullable()->after('contact_name');
            $table->string('decision_maker_name')->nullable()->after('site_address');
            $table->string('decision_maker_phone', 50)->nullable()->after('decision_maker_name');
            $table->string('decision_maker_email')->nullable()->after('decision_maker_phone');
            $table->decimal('approved_budget', 14, 2)->nullable()->after('decision_maker_email');
            $table->string('approved_budget_basis', 20)->nullable()->after('approved_budget');
            $table->date('service_end_date')->nullable()->after('start_date');
            $table->decimal('hours_per_day', 5, 2)->nullable()->after('service_end_date');
            $table->decimal('days_per_week', 4, 2)->nullable()->after('hours_per_day');
            $table->unsignedSmallInteger('officers_per_shift')->nullable()->after('days_per_week');
            $table->unsignedSmallInteger('number_of_weeks')->nullable()->after('officers_per_shift');
            $table->string('service_type_other', 100)->nullable()->after('service_type');
            $table->string('industry')->nullable()->after('service_type_other');
            $table->string('preferred_contact_method', 20)->nullable()->after('industry');
            $table->json('qualification_answers')->nullable()->after('scope');
            $table->string('qualification_status', 40)->nullable()->after('qualification_answers');
            $table->timestamp('qualification_submitted_at')->nullable()->after('qualification_status');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_buyer_leads', function (Blueprint $table) {
            $table->dropColumn([
                'contact_title',
                'decision_maker_name',
                'decision_maker_phone',
                'decision_maker_email',
                'approved_budget',
                'approved_budget_basis',
                'service_end_date',
                'hours_per_day',
                'days_per_week',
                'officers_per_shift',
                'number_of_weeks',
                'service_type_other',
                'industry',
                'preferred_contact_method',
                'qualification_answers',
                'qualification_status',
                'qualification_submitted_at',
            ]);
        });
    }
};
