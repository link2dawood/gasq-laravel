{{-- Part 2, Section B — Workforce Sustainment --}}
<h4 class="mb-3">Part 2 — Section B: Workforce Sustainment</h4>

@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q11_no_excessive_overtime', 'label' => '11. Can your company sustain this contract without relying on excessive overtime?'])

<div class="mb-3">
    <label class="form-label fw-semibold">12. Average employee turnover rate (%)</label>
    <div class="input-group">
        <input type="number" min="0" max="100" step="0.1" class="form-control"
               name="responses[p2_q12_turnover_pct]"
               value="{{ $responses['p2_q12_turnover_pct'] ?? '' }}" placeholder="e.g. 25">
        <span class="input-group-text">%</span>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">13. Average employee retention period (months)</label>
    <input type="number" min="0" class="form-control" name="responses[p2_q13_retention_months]"
           value="{{ $responses['p2_q13_retention_months'] ?? '' }}" placeholder="e.g. 18">
</div>

@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q14_employee_benefits', 'label' => '14. Do you currently provide employee benefits?'])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q15_formal_training', 'label' => '15. Does your company provide formal training programs?'])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q16_background_checks', 'label' => '16. Does your company perform background checks on all personnel?'])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q17_sops_post_orders', 'label' => '17. Does your company have or can provide written SOPs and post orders?'])
