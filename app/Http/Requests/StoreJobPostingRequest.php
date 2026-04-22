<?php

namespace App\Http\Requests;

use App\Services\PhoneOtpService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreJobPostingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'latitude' => $this->input('latitude') === '' || $this->input('latitude') === null
                ? null
                : $this->input('latitude'),
            'longitude' => $this->input('longitude') === '' || $this->input('longitude') === null
                ? null
                : $this->input('longitude'),
            'google_place_id' => $this->input('google_place_id') === '' || $this->input('google_place_id') === null
                ? null
                : $this->input('google_place_id'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isBuyer() ?? false;
    }

    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [
                'title' => ['required', 'string', 'max:255'],
                'category' => ['nullable', 'string', 'max:100'],
                'location' => ['nullable', 'string', 'max:255'],
                'zip_code' => ['nullable', 'string', 'max:20'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'google_place_id' => ['nullable', 'string', 'max:255'],
                'service_start_date' => ['nullable', 'date'],
                'service_end_date' => ['nullable', 'date', 'after_or_equal:service_start_date'],
                'guards_per_shift' => ['nullable', 'integer', 'min:1', 'max:255'],
                'budget_min' => ['nullable', 'numeric', 'min:0'],
                'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
                'description' => ['nullable', 'string'],
                'property_type' => ['nullable', 'string', 'max:100'],
                'special_requirements' => ['nullable', 'string'],
                'expires_at' => ['nullable', 'date'],
            ];
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'service_start_date' => ['required', 'date'],
            'service_end_date' => ['nullable', 'date', 'after_or_equal:service_start_date'],
            'guards_per_shift' => ['required', 'integer', 'min:1', 'max:255'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'description' => ['nullable', 'string'],
            'property_type' => ['required', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date'],

            // Questionnaire fields
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_job_title' => ['required', 'string', 'max:255'],
            'organization_name' => ['required', 'string', 'max:255'],
            'property_site_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:40'],
            'business_address' => ['required', 'string', 'max:500'],
            'preferred_contact_method' => ['required', 'in:email,mobile_phone,text_message'],
            'best_time_to_contact' => ['nullable', 'in:morning,midday,afternoon,evening'],
            'final_decision_maker' => ['required', 'in:yes,no,authorized_representative'],
            'approval_authority' => ['required', 'string', 'max:50'],
            'final_approver_name' => ['nullable', 'string', 'max:255'],
            'knows_true_inhouse_cost' => ['required', 'in:yes,no'],
            'project_readiness_reasons' => ['required', 'array', 'min:1'],
            'project_readiness_reasons.*' => ['string', 'max:80'],
            'service_start_timeline' => ['required', 'in:immediate,15_days_or_less,30_days_or_less,30_60_days,future_planning'],
            'funds_approval_status' => ['required', 'in:flexible_budget,restrictive_budget,pending,no_approved_budget'],
            'budget_type' => ['required', 'in:monthly,annual,contract_total'],
            'budget_amount_range' => ['nullable', 'string', 'max:255'],
            'true_internal_cost_calculated' => ['required', 'in:yes,no'],
            'if_pricing_exceeds' => ['nullable', 'array'],
            'if_pricing_exceeds.*' => ['string', 'max:60'],
            'current_security_setup' => ['required', 'in:in_house,outsourced,none'],
            'is_replacing_provider' => ['required', 'in:yes,no'],
            'multiple_bids_required' => ['required', 'in:yes,no'],
            'willing_adjust_scope_to_budget' => ['required', 'in:yes,no'],
            'move_forward_if_accepted' => ['required', 'in:yes,no,need_internal_review'],
            'risk_assessment_last_12_months' => ['required', 'in:yes_recent,no_want_one,no_waiver_required'],
            'multiple_locations' => ['required', 'in:yes,no'],
            'locations_count' => ['nullable', 'integer', 'min:1', 'max:999'],
            'property_type_other' => ['nullable', 'string', 'max:255'],
            'service_types' => ['required', 'array', 'min:1'],
            'service_types.*' => ['string', 'max:100'],
            'service_type_other' => ['nullable', 'string', 'max:255'],
            'request_type' => ['required', 'in:new_service,replace_current_provider,expand_existing_coverage,temporary_emergency_coverage'],
            'desired_contract_term' => ['required', 'string', 'max:60'],
            'primary_reason' => ['required', 'string', 'max:4000'],
            'hours_per_day' => ['required', 'numeric', 'min:1', 'max:24'],
            'days_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'weeks_per_year' => ['required', 'integer', 'min:1', 'max:53'],
            'shifts_needed' => ['required', 'array', 'min:1'],
            'shifts_needed.*' => ['string', 'max:50'],
            'assignment_type' => ['required', 'in:dedicated_post,patrol_route,hybrid'],
            'patrol_types' => ['nullable', 'array'],
            'patrol_types.*' => ['in:Foot Patrol,Vehicle Patrol,Golf Cart Patrol,Bike Patrol'],
            'duties_required' => ['required', 'array', 'min:1'],
            'duties_required.*' => ['string', 'max:120'],
            'duties_other' => ['nullable', 'string', 'max:255'],
            'service_package_expectation' => ['required', 'in:observe_and_report_only,detect_delay_assess_respond'],
            'hands_off_expected' => ['required', 'in:yes,no,not_sure'],
            'has_written_post_orders' => ['required', 'in:yes,no,in_progress'],
            'supporting_documents' => ['nullable', 'array'],
            'supporting_documents.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg,webp', 'max:5120'],
            'known_site_risks' => ['nullable', 'string', 'max:4000'],
            'budget_format' => ['required', 'in:hourly_budget,monthly_budget,annual_budget,need_gasq_estimate'],
            'hourly_budget' => ['nullable', 'numeric', 'min:0'],
            'monthly_budget' => ['nullable', 'numeric', 'min:0'],
            'annual_budget' => ['nullable', 'numeric', 'min:0'],
            'willing_post_offer' => ['required', 'in:yes,no'],
            'allow_scope_adjustment' => ['required', 'in:yes,no,maybe_after_review'],
            'cost_comparison_requested' => ['required', 'in:yes,no'],
            'officer_licensing_required' => ['required', 'in:yes,no,depends_on_assignment'],
            'background_checks_required' => ['required', 'in:yes,no'],
            'drug_testing_required' => ['required', 'in:yes,no'],
            'uniformed_officers_required' => ['required', 'in:yes,no'],
            'insurance_minimums_required' => ['nullable', 'array'],
            'insurance_minimums_required.*' => ['in:General Liability,Workers Compensation,Auto Liability,Umbrella / Excess Liability,Not sure'],
            'compliance_terms' => ['nullable', 'string', 'max:4000'],
            'vendor_response_deadline' => ['required', 'date', 'after_or_equal:today'],
            'additional_notes_to_vendors' => ['nullable', 'string', 'max:4000'],
            'buyer_certification' => ['required', 'accepted'],
            'consent_to_contact' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'buyer_certification.accepted' => 'Buyer certification is required.',
            'consent_to_contact.accepted' => 'Consent to contact is required.',
            'project_readiness_reasons.required' => 'Select at least one reason for requesting security services.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (! $this->isMethod('post')) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            $phoneOtp = app(PhoneOtpService::class);
            $normalizedContactPhone = $phoneOtp->normalizePhoneToE164((string) $this->input('contact_phone'));
            $normalizedAccountPhone = $phoneOtp->normalizePhoneToE164((string) ($this->user()?->phone ?? ''));

            if (
                ! (bool) $this->user()?->phone_verified
                || $normalizedContactPhone === null
                || $normalizedAccountPhone === null
                || $normalizedContactPhone !== $normalizedAccountPhone
            ) {
                $validator->errors()->add('contact_phone', 'Verify this mobile number by SMS on this page before posting.');
            }

            if (in_array($this->input('final_decision_maker'), ['no', 'authorized_representative'], true)
                && blank($this->input('final_approver_name'))) {
                $validator->errors()->add('final_approver_name', 'Please identify who approves the final award.');
            }

            if ($this->input('multiple_locations') === 'yes' && ! $this->filled('locations_count')) {
                $validator->errors()->add('locations_count', 'Please enter the number of locations requiring coverage.');
            }

            $budgetFormat = $this->input('budget_format');
            $budgetField = match ($budgetFormat) {
                'hourly_budget' => 'hourly_budget',
                'monthly_budget' => 'monthly_budget',
                'annual_budget' => 'annual_budget',
                default => null,
            };

            if ($budgetField !== null && ! $this->filled($budgetField)) {
                $validator->errors()->add($budgetField, 'Enter a budget amount for the selected budget format.');
            }

            if ($this->input('property_type') === 'Other' && blank($this->input('property_type_other'))) {
                $validator->errors()->add('property_type_other', 'Please specify the property type.');
            }

            if (in_array('Other', (array) $this->input('service_types', []), true) && blank($this->input('service_type_other'))) {
                $validator->errors()->add('service_type_other', 'Please specify the additional service type.');
            }

            if (in_array($this->input('assignment_type'), ['patrol_route', 'hybrid'], true)
                && count((array) $this->input('patrol_types', [])) === 0) {
                $validator->errors()->add('patrol_types', 'Select at least one patrol type when patrol coverage is required.');
            }

            if (in_array('Other', (array) $this->input('duties_required', []), true) && blank($this->input('duties_other'))) {
                $validator->errors()->add('duties_other', 'Please specify the additional duty requirement.');
            }
        });
    }
}
