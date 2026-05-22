<?php

namespace App\Http\Requests;

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
            // Top-level job fields
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'service_start_date' => ['required', 'date'],
            'service_end_date' => ['nullable', 'date', 'after_or_equal:service_start_date'],
            'guards_per_shift' => ['required', 'integer', 'min:1', 'max:100'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'description' => ['nullable', 'string'],
            'property_type' => ['required', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date'],

            // SECTION 1: Contact Information
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_job_title' => ['required', 'string', 'max:255'],
            'organization_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:40'],
            'preferred_contact_method' => ['required', 'in:email,mobile_phone,text_message'],
            'best_time_to_contact' => ['nullable', 'in:morning,midday,afternoon,evening'],

            // SECTION 2: Decision Authority
            'final_decision_maker' => ['required', 'in:yes,no,authorized_representative'],
            'approval_authority' => ['required', 'string', 'max:50'],
            'final_approver_name' => ['nullable', 'string', 'max:255'],
            'budget_approved_status' => ['required', 'in:yes,no,pending'],
            'move_forward_if_accepted' => ['required', 'in:yes,no,need_internal_review'],

            // SECTION 3: Service Location
            'business_address' => ['required', 'string', 'max:500'],
            'business_address_place_id' => ['nullable', 'string', 'max:255'],
            'multiple_locations' => ['required', 'in:yes,no'],
            'locations_count' => ['nullable', 'integer', 'min:1', 'max:999'],
            'property_type_other' => ['nullable', 'string', 'max:255'],

            // SECTION 4: Service Request Details
            'service_types' => ['required', 'array', 'min:1'],
            'service_types.*' => ['string', 'max:100'],
            'service_type_other' => ['nullable', 'string', 'max:255'],
            'request_type' => ['required', 'in:new_service,replace_current_provider,expand_existing_coverage,temporary_emergency_coverage'],
            'desired_contract_term' => ['required', 'string', 'max:60'],
            'primary_reason' => ['required', 'string', 'max:4000'],

            // SECTION 5: Scope, Schedule and Staffing
            'hours_per_day' => ['required', 'integer', 'min:1', 'max:24'],
            'days_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'weeks_per_year' => ['required', 'integer', 'min:1', 'max:52'],
            'staff_per_shift' => ['required', 'integer', 'min:1', 'max:100'],
            'shifts_needed' => ['required', 'array', 'min:1'],
            'shifts_needed.*' => ['string', 'max:50'],
            'patrol_types' => ['nullable', 'array'],
            'patrol_types.*' => ['in:Foot Patrol,Vehicle Patrol,Golf Cart Patrol,Bike Patrol'],

            // SECTION 6: Duties and Site Conditions
            'duties_required' => ['required', 'array', 'min:1'],
            'duties_required.*' => ['string', 'max:120'],
            'duties_other' => ['nullable', 'string', 'max:255'],
            'service_package_expectation' => ['required', 'in:observe_and_report_only,detect_delay_assess_respond'],
            'supporting_documents' => ['nullable', 'array'],
            'supporting_documents.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg,webp', 'max:5120'],
            'known_site_risks' => ['nullable', 'string', 'max:4000'],

            // SECTION 7: Budget and Offer Terms

            // SECTION 8: Compliance Requirements
            'insurance_minimums_required' => ['required', 'array', 'min:1'],
            'insurance_minimums_required.*' => ['in:General Liability,Workers Compensation,Auto Liability,Umbrella / Excess Liability,Not sure'],
            'compliance_terms' => ['nullable', 'string', 'max:4000'],

            // Auto-computed / calculator-derived contract value (hidden inputs on the form).
            'annual_budget' => ['nullable', 'numeric', 'min:0'],
            'monthly_budget' => ['nullable', 'numeric', 'min:0'],
            'hourly_budget' => ['nullable', 'numeric', 'min:0'],
            'budget_amount_range' => ['nullable', 'string', 'max:255'],

            // SECTION 9: Posting Terms and Submission
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
            'insurance_minimums_required.required' => 'Select the required insurance minimums.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (! $this->isMethod('post')) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            if (in_array($this->input('final_decision_maker'), ['no', 'authorized_representative'], true)
                && blank($this->input('final_approver_name'))) {
                $validator->errors()->add('final_approver_name', 'Please identify who approves the final award.');
            }

            // Reject Lorem-Ipsum / free-text addresses: require the user to pick a Google Places suggestion.
            if (filled(config('services.google.maps_api_key'))) {
                if ($this->filled('business_address') && blank($this->input('business_address_place_id'))) {
                    $validator->errors()->add(
                        'business_address',
                        'Please select your service address from the suggestions.'
                    );
                }
            }

            if ($this->input('multiple_locations') === 'yes' && ! $this->filled('locations_count')) {
                $validator->errors()->add('locations_count', 'Please enter the number of locations requiring coverage.');
            }

if ($this->input('property_type') === 'Other' && blank($this->input('property_type_other'))) {
                $validator->errors()->add('property_type_other', 'Please specify the property type.');
            }

            if (in_array('Other', (array) $this->input('service_types', []), true) && blank($this->input('service_type_other'))) {
                $validator->errors()->add('service_type_other', 'Please specify the additional service type.');
            }

if (in_array('Other', (array) $this->input('duties_required', []), true) && blank($this->input('duties_other'))) {
                $validator->errors()->add('duties_other', 'Please specify the additional duty requirement.');
            }
        });
    }
}
