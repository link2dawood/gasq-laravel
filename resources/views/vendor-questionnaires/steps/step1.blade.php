{{-- Part 1, Section A — Vendor Submission Compliance --}}
<h4 class="mb-3">Part 1 — Section A: Vendor Submission Compliance</h4>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">1. Company Legal Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="responses[p1_q1_legal_name]"
               value="{{ $responses['p1_q1_legal_name'] ?? '' }}" placeholder="Full legal business name">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Business Physical Address</label>
        <input type="text" class="form-control" name="responses[p1_q1_address]"
               value="{{ $responses['p1_q1_address'] ?? '' }}" placeholder="Street, City, State, ZIP">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">2. DBA Name</label>
        <input type="text" class="form-control" name="responses[p1_q2_dba]"
               value="{{ $responses['p1_q2_dba'] ?? '' }}" placeholder="Doing Business As (if applicable)">
    </div>
</div>

<h5 class="mt-4">3. Primary Contact Information</h5>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" class="form-control" name="responses[p1_q3_contact_name]"
               value="{{ $responses['p1_q3_contact_name'] ?? '' }}" placeholder="Contact name">
    </div>
    <div class="col-md-6">
        <label class="form-label">Title</label>
        <input type="text" class="form-control" name="responses[p1_q3_contact_title]"
               value="{{ $responses['p1_q3_contact_title'] ?? '' }}" placeholder="Title">
    </div>
    <div class="col-md-6">
        <label class="form-label">Company Email</label>
        <input type="email" class="form-control" name="responses[p1_q3_contact_email]"
               value="{{ $responses['p1_q3_contact_email'] ?? '' }}" placeholder="name@company.com">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone Number</label>
        <input type="tel" class="form-control" name="responses[p1_q3_contact_phone]"
               value="{{ $responses['p1_q3_contact_phone'] ?? '' }}" placeholder="(555) 555-5555">
    </div>
</div>

@include('vendor-questionnaires._partials.yesno', [
    'name' => 'p1_q4_licensed',
    'label' => '4. Is your company currently licensed to provide security services in this state?',
    'required' => true,
])

<h5 class="mt-4">5. Required Documents</h5>
<p class="text-muted small">
    Documents already on file from your profile are pre-filled. Upload any missing ones; you may also replace pre-filled documents.
</p>
<div class="row g-3 mb-4">
    @foreach($documentTypes as $type => $label)
        @php $existing = $documents[$type] ?? null; @endphp
        <div class="col-md-6">
            <label class="form-label fw-semibold">{{ $label }} <span class="text-danger">*</span></label>
            @if($existing && $existing->fileUpload)
                <div class="alert alert-success py-2 px-3 mb-2 small">
                    <strong>On file:</strong> {{ $existing->fileUpload->filename }}
                    @if($existing->prefilled_from_profile)
                        <span class="badge bg-info ms-1">Pre-filled from profile</span>
                    @endif
                </div>
            @endif
            <input type="file" class="form-control" name="documents[{{ $type }}]"
                   accept=".pdf,.png,.jpg,.jpeg,.doc,.docx">
        </div>
    @endforeach
</div>

@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q6_start_date', 'label' => '6. Can your company meet the required contract start date?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q7_coverage_hours', 'label' => '7. Can your company meet all required coverage hours?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q8_staff_personnel', 'label' => '8. Can your company provide the required number of staff personnel?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q9_uniform', 'label' => '9. Can your company comply with required uniform standards?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q10_reporting', 'label' => '10. Can your company comply with reporting requirements?', 'required' => true])

<div class="mb-3">
    <label class="form-label fw-semibold">11. Technology compliance — check all that apply <span class="text-danger">*</span></label>
    @php
        $tech = $responses['p1_q11_technology'] ?? [];
        $techOpts = [
            'guard_tour' => 'Guard tour system',
            'gps' => 'GPS tracking',
            'incident_software' => 'Incident reporting software',
            'body_cameras' => 'Body cameras',
            'ai_surveillance' => 'AI surveillance integration',
        ];
    @endphp
    @foreach($techOpts as $val => $label)
        <div class="form-check">
            <input class="form-check-input" type="checkbox"
                   id="tech_{{ $val }}" name="responses[p1_q11_technology][]" value="{{ $val }}"
                   @checked(is_array($tech) && in_array($val, $tech))>
            <label class="form-check-label" for="tech_{{ $val }}">{{ $label }}</label>
        </div>
    @endforeach
</div>

@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q11_technology_compliance', 'label' => '11b. Can your company comply with all required technology requirements above?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q12_insurance_minimums', 'label' => '12. Can your company comply with all insurance minimums?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q13_wage', 'label' => '13. Can your company comply with all wage requirements?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q14_training', 'label' => '14. Can your company comply with all training/certification requirements?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q15_response_time', 'label' => '15. Can your company comply with response time requirements?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q16_scope_reviewed', 'label' => '16. Have you reviewed the full scope of work?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q17_terms_agreed', 'label' => '17. Do you agree to all mandatory terms and conditions?', 'required' => true])
