{{-- Part 2, Section C — Financial Responsibility --}}
<h4 class="mb-3">Part 2 — Section C: Financial Responsibility</h4>

@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q18_payroll_30_45', 'label' => '18. Can your company sustain payroll for at least 30–45 days before client payment?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q19_failed_payroll', 'label' => '19. Has your company ever failed to meet payroll obligations?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q20_lost_contract_staffing', 'label' => '20. Has your company ever lost a contract due to staffing failures?', 'required' => true])

<div class="mb-3">
    <label class="form-label fw-semibold">21. Insurances currently maintained — check all that apply <span class="text-danger">*</span></label>
    <p class="small text-muted mb-2">Workers' Compensation and General Liability are required to proceed.</p>
    @php
        $ins = $responses['p2_q21_insurances'] ?? [];
        $insOpts = [
            'workers_comp' => "Workers' Compensation Insurance",
            'general_liability' => 'General Liability Insurance',
            'auto_liability' => 'Automobile Liability Insurance',
            'umbrella' => 'Umbrella Insurance',
            'epli' => 'EPLI Coverage',
        ];
    @endphp
    @foreach($insOpts as $val => $label)
        <div class="form-check">
            <input class="form-check-input" type="checkbox"
                   id="ins_{{ $val }}" name="responses[p2_q21_insurances][]" value="{{ $val }}"
                   @checked(is_array($ins) && in_array($val, $ins))>
            <label class="form-check-label" for="ins_{{ $val }}">{{ $label }}</label>
        </div>
    @endforeach
</div>

@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q22_negligent_security_litigation', 'label' => '22. Has your company been involved in litigation related to negligent security?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q23_license_suspended', 'label' => '23. Has your company ever had a security license suspended or revoked?', 'required' => true])
