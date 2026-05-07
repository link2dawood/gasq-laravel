{{-- Part 2, Section A — Operational Capacity --}}
<h4 class="mb-3">Part 2 — Section A: Operational Capacity</h4>

@php
    $numFields = [
        'p2_q1_active_personnel' => '1. How many active security personnel does your company currently employ?',
        'p2_q2_supervisors' => '2. How many supervisors does your company currently employ?',
        'p2_q3_active_accounts' => '3. How many active client accounts does your company currently manage?',
        'p2_q4_weekly_billable_hours' => '4. How many total billable hours does your company service weekly?',
        'p2_q7_years_in_business' => '7. How many years has your company been in business?',
    ];
@endphp

@foreach($numFields as $name => $label)
    <div class="mb-3">
        <label class="form-label fw-semibold">{{ $label }}</label>
        <input type="number" min="0" class="form-control" name="responses[{{ $name }}]"
               value="{{ $responses[$name] ?? '' }}" placeholder="0">
    </div>
@endforeach

@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q5_similar_size', 'label' => '5. Does your company currently service accounts similar in size to this project?'])

<div class="mb-3">
    <label class="form-label fw-semibold">6. What is the largest account your company currently services?</label>
    <textarea class="form-control" rows="2" name="responses[p2_q6_largest_account]"
              placeholder="Describe the account">{{ $responses['p2_q6_largest_account'] ?? '' }}</textarea>
</div>

@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q8_dispatch_24_7', 'label' => '8. Does your company maintain a 24/7 dispatch operation?'])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q9_after_hours_supervisors', 'label' => '9. Does your company have field supervisors available after hours?'])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q10_emergency_replacement', 'label' => '10. Does your company maintain emergency replacement personnel?'])
