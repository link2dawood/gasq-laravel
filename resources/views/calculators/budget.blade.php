@extends('layouts.app')
@php
    $__buyerTitleViewer = auth()->check() && method_exists(auth()->user(), 'isBuyer') && auth()->user()->isBuyer();
@endphp
@section('title', $__buyerTitleViewer ? 'Know Before You Buy Calculator' : 'Workforce Absorbed Rate Calculator')
@section('header_variant', 'dashboard')

@php
    $budgetConfig = config('budget_calculator');
    $budgetGroups = $budgetConfig['groups'] ?? [];
    $defaultGovernmentShouldCost = (float) ($budgetConfig['default_government_should_cost_hourly'] ?? 86.75);
    $defaultAnnualBillableHours = (float) ($budgetConfig['default_annual_billable_hours'] ?? 8736);
    $defaultTotal = (float) ($budgetConfig['default_total'] ?? ($defaultGovernmentShouldCost * $defaultAnnualBillableHours));
    $modelAnnualTotal = collect($budgetGroups)->sum(
        fn (array $group) => collect($group['items'] ?? [])->sum('annual')
    );

    $defaults = [];
    foreach ($budgetGroups as &$group) {
        $groupAnnual = collect($group['items'] ?? [])->sum('annual');
        $group['id'] = 'bg_group_' . $group['key'];
        $group['amount_id'] = $group['id'] . '_amt';
        $group['pct_id'] = $group['id'] . '_pct';
        $group['default'] = $modelAnnualTotal > 0 ? round(($groupAnnual / $modelAnnualTotal) * 100, 2) : 0;

        foreach ($group['items'] as &$item) {
            $item['id'] = 'bg_' . $item['key'];
            $item['default'] = $modelAnnualTotal > 0 ? round(($item['annual'] / $modelAnnualTotal) * 100, 2) : 0;
            $defaults[$item['id']] = $item['default'];
        }
        unset($item);
    }
    unset($group);

    $budgetGroupsForJs = array_map(
        fn (array $group) => [
            'key' => $group['key'],
            'label' => $group['label'],
            'description' => $group['description'],
            'benchmarked' => (bool) ($group['benchmarked'] ?? false),
            'items' => array_map(
                fn (array $item) => [
                    'key' => $item['key'],
                    'id' => $item['id'],
                    'label' => $item['label'],
                    'default' => $item['default'],
                    'color' => $item['color'],
                ],
                $group['items'] ?? []
            ),
        ],
        $budgetGroups
    );
@endphp

@section('content')
@php
    $isBuyerView = auth()->check() && method_exists(auth()->user(), 'isBuyer') && auth()->user()->isBuyer();
@endphp
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-piggy-bank text-primary"></i> {{ $isBuyerView ? 'Know Before You Buy Calculator' : 'Workforce Absorbed Rate Calculator' }}
        </h1>
        <div class="text-gasq-muted small">Plan and analyze your workforce budget across detailed spreadsheet line items.</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      @if(request('from') === 'questionnaire')
        <button type="button" class="btn btn-success btn-sm fw-semibold" id="save_and_return_to_questionnaire">
          <i class="fa fa-check me-1"></i> Save &amp; return to questionnaire
        </button>
      @endif
      <button class="btn btn-outline-secondary btn-sm" onclick="resetBudget()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
    @if(request('from') === 'questionnaire')
      <div class="alert alert-info py-2 mb-0 small w-100 mt-2 d-print-none">
        <i class="fa fa-circle-info me-1"></i>
        You came from the job questionnaire. Adjust the baseline wage and scope below, then click <strong>Save &amp; return to questionnaire</strong> to send the contract value back.
      </div>
    @endif
  </div>

  <div class="row g-4">

    <div class="col-lg-7">
      <div class="card gasq-card h-100">
        <div class="card-header py-3">
          <h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fa fa-list text-primary"></i> Budget Line Items
          </h5>
        </div>
        <div class="card-body d-flex flex-column gap-3">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-medium">Baseline Wage ($/hr)</label>
              <div class="small text-gasq-muted mb-1">Raw hourly wage paid to the security professional</div>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_govShouldCost" class="form-control fs-6 fw-semibold" value="18.00" step="0.01" min="0" max="1000" oninput="scheduleBgTcoFetch()">
                <input type="range" id="bg_govShouldCost_range" class="form-range mb-0" min="0" max="1000" step="0.01" value="18.00" data-sync="bg_govShouldCost">
              </div>
            </div>
          </div>

          <div class="mt-1">
            <h6 class="fw-semibold mb-1">Scope of Coverage</h6>
            <p class="small text-gasq-muted mb-2">Annual hours auto-total from hours × days × weeks × staff per 8-hour shift.</p>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-medium">Hours per day</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_hoursPerDay" class="form-control form-control-sm fw-semibold" value="24" step="0.5" min="0.5" max="24" oninput="scheduleBgTcoFetch()">
                <input type="range" id="bg_hoursPerDay_range" class="form-range mb-0" min="0.5" max="24" step="0.5" value="24" data-sync="bg_hoursPerDay">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Days per week</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_daysPerWeek" class="form-control form-control-sm fw-semibold" value="7" step="1" min="1" max="7" oninput="scheduleBgTcoFetch()">
                <input type="range" id="bg_daysPerWeek_range" class="form-range mb-0" min="1" max="7" step="1" value="7" data-sync="bg_daysPerWeek">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Weeks per year</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_weeksPerYear" class="form-control form-control-sm fw-semibold" value="52" step="1" min="1" max="52" oninput="scheduleBgTcoFetch()">
                <input type="range" id="bg_weeksPerYear_range" class="form-range mb-0" min="1" max="52" step="1" value="52" data-sync="bg_weeksPerYear">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Staff per 8-hour shift</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_staffPerShift" class="form-control form-control-sm fw-semibold" value="1" step="1" min="1" max="100" oninput="scheduleBgTcoFetch()">
                <input type="range" id="bg_staffPerShift_range" class="form-range mb-0" min="1" max="100" step="1" value="1" data-sync="bg_staffPerShift">
              </div>
            </div>
          </div>

          {{-- Annual Coverage Hours now derived from the 4 scope inputs above. --}}
          <input type="hidden" id="bg_annualHours" value="8736">
          {{-- Vendor TCO hidden — now derived from Baseline Wage via the GASQ formula. --}}
          <input type="hidden" id="bg_vendorTco" value="0">

          <div>
            <label class="form-label fw-medium">Buyer Annual Total Cost of Ownership ($)</label>
            <div class="input-group">
              <span class="input-group-text fs-5 fw-semibold">$</span>
              <input type="text" id="bg_total" class="form-control fs-5 fw-semibold" value="0.00" readonly>
            </div>
          </div>

          <hr class="my-2">
          <div>
            <h6 class="fw-semibold mb-1">Contact Information</h6>
            <p class="small text-gasq-muted mb-2">This information will appear on the PDF report.</p>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-medium">Contact Name</label>
              <input type="text" id="bg_contactName" class="form-control form-control-sm" placeholder="Full name" oninput="scheduleBgTcoFetch()">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Company Name</label>
              <input type="text" id="bg_companyName" class="form-control form-control-sm" placeholder="Company" oninput="scheduleBgTcoFetch()">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Address</label>
              <input type="text" id="bg_contactAddress" class="form-control form-control-sm" placeholder="Street address, city, state" oninput="scheduleBgTcoFetch()">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Email</label>
              <input type="email" id="bg_contactEmail" class="form-control form-control-sm" placeholder="Email address" oninput="scheduleBgTcoFetch()">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Phone</label>
              <input type="tel" id="bg_contactPhone" class="form-control form-control-sm" placeholder="Phone number" oninput="scheduleBgTcoFetch()">
            </div>
          </div>

          @unless($isBuyerView)
          <hr class="my-1">
          <div>
            <h6 class="fw-semibold mb-1">Allocation Percentages</h6>
            <p class="small text-gasq-muted mb-0">Each slider represents a spreadsheet line item. Adjust the percentages so the total allocation equals 100%.</p>
          </div>

          @foreach($budgetGroups as $group)
          <section class="border rounded-3 p-3" style="border-color:rgba(15,23,42,0.08)!important;background:#fff">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
              <div>
                <h6 class="fw-semibold mb-1">{{ $group['label'] }}</h6>
                <p class="small text-gasq-muted mb-0">{{ $group['description'] }}</p>
              </div>
              <div class="text-end">
                <div class="fw-semibold" id="{{ $group['amount_id'] }}">$0.00</div>
                <div class="small text-gasq-muted" id="{{ $group['pct_id'] }}">0%</div>
              </div>
            </div>

            <div class="d-flex flex-column gap-3">
              @foreach($group['items'] as $item)
              <div class="budget-line-item">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-1">
                  <label class="form-label small fw-medium mb-0 d-flex align-items-center gap-2">
                    <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:{{ $item['color'] }}"></span>
                    {{ $item['label'] }}
                  </label>
                  <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="small fw-medium text-end" id="{{ $item['id'] }}_amt" style="min-width:110px">$0.00</span>
                    <input type="number" id="{{ $item['id'] }}" class="form-control form-control-sm text-center" style="width:88px" value="{{ number_format($item['default'], 2, '.', '') }}" min="0" max="100" step="0.1" oninput="calcBudget()">
                  </div>
                </div>
                <input
                  type="range"
                  id="{{ $item['id'] }}_range"
                  class="form-range mb-1"
                  min="0"
                  max="100"
                  step="0.1"
                  value="{{ number_format($item['default'], 2, '.', '') }}"
                  data-sync="{{ $item['id'] }}"
                  style="accent-color: {{ $item['color'] }}"
                >
                <div class="progress" style="height:6px">
                  <div class="progress-bar" id="{{ $item['id'] }}_bar" style="width:{{ $item['default'] }}%;background:{{ $item['color'] }}"></div>
                </div>
              </div>
              @endforeach
            </div>
          </section>
          @endforeach

          <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--gasq-muted-bg)">
            <span class="small fw-semibold">Total Allocated</span>
            <span class="fw-bold" id="bg_totalPct">100%</span>
          </div>
          <div class="alert alert-warning d-none py-2 mb-0" id="bg_warning" role="alert">
            <i class="fa fa-triangle-exclamation me-1"></i> Percentages should total 100%
          </div>
          @endunless

        </div>
      </div>
    </div>

    <div class="col-lg-5 budget-print-area">
      <div class="card gasq-card mb-4">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">{{ $isBuyerView ? 'Know Before You Buy Summary' : 'Budget Summary' }}</h5></div>
        <div class="card-body">
          {{-- Side-by-side comparison: Buyer Cost (left) vs Vendor Cost (right) --}}
          <div class="row g-2 mb-3">
            <div class="{{ $isBuyerView ? 'col-12' : 'col-6' }}">
              <div class="p-3 rounded text-center" style="background:#fdf2f2; border:2px solid #b91c1c;">
                <div class="text-uppercase small fw-semibold mb-1" style="color:#7f1d1d;">Buyer Cost to Protect In-house</div>
                <div class="h4 fw-bold mb-0" style="color:#7f1d1d;" id="r_internalTco">$0.00</div>
                <div class="small text-gasq-muted mt-1">per hour (TCO)</div>
                <hr class="my-2" style="border-color:#fecaca;">
                <div class="text-uppercase small fw-semibold mb-1" style="color:#7f1d1d;">Annual</div>
                <div class="fw-bold" style="color:#7f1d1d;" id="r_buyerAnnual">$0.00</div>
              </div>
            </div>
            @unless($isBuyerView)
            <div class="col-6">
              <div class="p-3 rounded text-center" style="background:#d1e7dd; border:2px solid #198754;">
                <div class="text-uppercase small fw-semibold mb-1" style="color:#0a3622;">Buyer Cost to Protect via Outsourcing</div>
                <div class="h4 fw-bold mb-0" style="color:#0a3622;" id="r_govShouldCost">$0.00</div>
                <div class="small text-gasq-muted mt-1">per hour (Vendor TCO)</div>
                <hr class="my-2" style="border-color:#a3cfbb;">
                <div class="text-uppercase small fw-semibold mb-1" style="color:#0a3622;">Annual</div>
                <div class="fw-bold" style="color:#0a3622;" id="r_vendorAnnual">$0.00</div>
              </div>
            </div>
            @endunless
          </div>

          {{-- Capital Recovery callout on the right-side stream --}}
          <div class="p-3 rounded text-center mb-4" style="background:#fff3cd; border:2px solid #f59f00;">
            <div class="text-uppercase small fw-semibold mb-1" style="color:#664d03;">💰 Capital Recovery Opportunity</div>
            <div class="row g-2 mt-1">
              <div class="col-6">
                <div class="small text-gasq-muted">Per hour</div>
                <div class="h5 fw-bold mb-0" style="color:#664d03;" id="r_recoveryPerHr">$0.00</div>
              </div>
              <div class="col-6">
                <div class="small text-gasq-muted">Annual</div>
                <div class="h5 fw-bold mb-0" style="color:#664d03;" id="r_recoveryAnnual">$0.00</div>
              </div>
            </div>
            <div class="small mt-2" style="color:#664d03;">
              Saved by outsourcing at Vendor TCO instead of in-house TCO
            </div>
          </div>

          <div class="d-flex justify-content-between small text-gasq-muted mb-3 px-1">
            <span>Loaded Wage: <strong id="r_loadedWage">$0.00</strong></span>
            <span>Annual Coverage: <strong id="r_hours">0</strong> hrs</span>
          </div>
          <div class="text-center small mb-4">
            <a href="{{ url('/workforce-appraisal-report') }}" class="fw-semibold text-decoration-none">Open Workforce Appraisal Report →</a>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Annual Budget</div>
                <div class="metric-value text-primary" id="r_annual">$0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Monthly Budget</div>
                <div class="metric-value" id="r_monthly">$0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Weekly Budget</div>
                <div class="metric-value" id="r_weekly">$0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Daily Budget</div>
                <div class="metric-value" id="r_daily">$0.00</div>
              </div>
            </div>
          </div>

          <h6 class="fw-semibold mb-3">Hours Summary</h6>
          <div class="row g-3 mb-4">
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Yearly Hours</div>
                <div class="metric-value text-primary" id="r_hours_yearly">0</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Monthly Hours</div>
                <div class="metric-value" id="r_hours_monthly">0</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Weekly Hours</div>
                <div class="metric-value" id="r_hours_weekly">0</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Daily Hours</div>
                <div class="metric-value" id="r_hours_daily">0</div>
              </div>
            </div>
          </div>

          @unless($isBuyerView)
          <h6 class="fw-semibold mb-3">Allocation Group Totals</h6>
          <div id="bg_group_summary" class="d-flex flex-column gap-2 mb-3"></div>
          <div class="d-flex justify-content-between align-items-center p-3 rounded mb-4"
               style="background:rgba(6,45,121,0.06);border:1px solid rgba(6,45,121,0.18);">
            <div>
              <div class="fw-semibold">Total Contract / Budget Value</div>
              <div class="text-gasq-muted small">Sum of all allocation groups · auto-calculated</div>
            </div>
            <div class="h4 fw-bold text-primary mb-0" id="bg_contract_total">$0.00</div>
          </div>

          <h6 class="fw-semibold mb-3">Line-Item Breakdown</h6>
          <div id="bg_breakdown" class="d-flex flex-column gap-3 mb-4"></div>

          <div class="rounded p-3" style="background:rgba(6,45,121,0.06);border:1px solid rgba(6,45,121,0.15)">
            <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2"><i class="fa fa-lightbulb text-primary"></i> Budget Insights</h6>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Government should-cost</span><span id="ins_govShouldCost" class="fw-medium">$0.00</span></div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Annual billable hours</span><span id="ins_annualHours" class="fw-medium">0</span></div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Labor & burden allocation</span><span id="ins_laborPct" class="fw-medium">0%</span></div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Industry benchmark (labor)</span><span class="text-gasq-muted">55–70%</span></div>
            <div class="d-flex justify-content-between small"><span class="text-gasq-muted">Labor status</span><span id="ins_laborStatus" class="fw-medium">—</span></div>
          </div>
          @endunless
        </div>
      </div>
    </div>
  </div>

  <div class="card gasq-card mt-4">
    <div class="card-header py-3">
      <h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2">
        <i class="fa fa-balance-scale text-primary"></i> {{ $isBuyerView ? 'Total Cost of Ownership Summary' : 'Cost to Protect Appraisal Comparison Summary' }}
      </h5>
      <div class="text-gasq-muted small">Side-by-side comparison of internal TCO vs vendor TCO. Updates live with the inputs above.</div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th>Description</th>
              <th class="text-end font-monospace">Buyer Internal Cost to Protect</th>
              <th class="text-end font-monospace">Buyer Outsourcing Cost to Protect <i class="fa fa-lock small text-secondary ms-1"></i></th>
            </tr>
          </thead>
          <tbody id="bg_ap_body"></tbody>
          <tbody id="bg_ap_foot"></tbody>
        </table>
      </div>
      <div class="px-3 py-2 small text-gasq-muted border-top d-flex align-items-start gap-2">
        <i class="fa fa-lock text-secondary mt-1"></i>
        <span>The <strong>Buyer Outsourcing Cost to Protect</strong> figures are unlocked in your Cost to Protect report below — download or email it for the full outsourcing appraisal.</span>
      </div>
    </div>
  </div>

  <x-report-actions reportType="budget-calculator" label="Cost to Protect Summary report — download or email" />
  <x-report-actions reportType="budget-calculator-allocation" label="Allocation & Line-Item Breakdown report — download or email" />

</div>
</div>
@endsection

@push('scripts')
<style>
  @media print {
    body * { visibility: hidden !important; }
    .budget-print-area, .budget-print-area * { visibility: visible !important; }
    .budget-print-area {
      position: absolute !important;
      left: 0 !important;
      top: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
      flex: 0 0 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }
    .budget-print-area .card { border: none !important; box-shadow: none !important; }
    .budget-print-area a { color: inherit !important; text-decoration: none !important; }
    @page { margin: 0.5in; }
  }
  /* Gate the "Buyer Outsourcing Cost to Protect" column on screen — the real
     figures live in the paid Cost to Protect report (server-rendered PDF). */
  .bg-redacted {
    filter: blur(7px);
    -webkit-filter: blur(7px);
    user-select: none;
    -webkit-user-select: none;
    cursor: not-allowed;
  }
  .budget-line-item .form-range { margin-bottom: 0.35rem; }
  .budget-group-summary-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0.85rem;
    padding: 0.75rem 0.9rem;
    background: #fff;
  }
  .budget-benchmark-card {
    border: 1px solid rgba(6, 45, 121, 0.12);
    border-radius: 1rem;
    padding: 1rem 1.05rem;
    background:
      radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 35%),
      linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  }
  .budget-breakdown-group {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0.85rem;
    padding: 0.85rem 0.9rem;
    background: #fff;
  }
  .budget-breakdown-item + .budget-breakdown-item {
    margin-top: 0.45rem;
  }
</style>
<script>
const savedScenario = window.__gasqCalculatorState?.scenario || null;
const BUDGET_GROUPS = @json($budgetGroupsForJs);
const DEFAULT_GOVERNMENT_SHOULD_COST = {{ json_encode($defaultGovernmentShouldCost) }};
const DEFAULT_ANNUAL_BILLABLE_HOURS = {{ json_encode($defaultAnnualBillableHours) }};
const DEFAULT_TOTAL = {{ json_encode($defaultTotal) }};
const DEFAULTS = @json($defaults);
const TOTAL_TOLERANCE = 0.05;
const ALL_ITEMS = BUDGET_GROUPS.flatMap((group) =>
  group.items.map((item) => ({ ...item, groupKey: group.key, groupLabel: group.label, benchmarked: group.benchmarked }))
);

let syncTimer = null;

function fmt(v) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(v);
}

function trimNumber(v, digits = 2) {
  return Number(v || 0)
    .toFixed(digits)
    .replace(/\.00$/, '')
    .replace(/(\.\d*[1-9])0+$/, '$1');
}

function fmtPct(v) {
  return `${trimNumber(v, 2)}%`;
}

function fmtHours(v) {
  return Number(v || 0).toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  });
}

function g(id) {
  return parseFloat(document.getElementById(id)?.value || '0') || 0;
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value;
}

function initSliderSync() {
  document.querySelectorAll('input[type="range"][data-sync]').forEach((rangeEl) => {
    const id = rangeEl.getAttribute('data-sync');
    const numEl = document.getElementById(id);
    if (!rangeEl || !numEl || rangeEl.dataset.bound === '1') return;

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
    const syncRangeFromNumber = () => {
      const min = parseFloat(rangeEl.min || '0');
      const max = parseFloat(rangeEl.max || '100');
      const value = parseFloat(numEl.value || rangeEl.value || '0');
      rangeEl.value = String(clamp(value, min, max));
    };
    const syncNumberFromRange = () => {
      numEl.value = rangeEl.value;
    };

    syncRangeFromNumber();
    rangeEl.dataset.bound = '1';

    rangeEl.addEventListener('input', () => {
      syncNumberFromRange();
      // Fire the number field's own handler so dragging behaves exactly like
      // typing — wage/scope fields refetch the TCO from the server, allocation
      // fields re-render. (Previously this only re-rendered the cached result,
      // so dragging the wage/scope sliders never updated the numbers.)
      numEl.dispatchEvent(new Event('input', { bubbles: true }));
    });

    numEl.addEventListener('input', () => {
      syncRangeFromNumber();
    });
  });
}

// Report download/email reflect the LAST server sync (which costs a credit).
// The on-screen preview updates for free, so it can drift ahead of the report.
// Guard against that: only allow Download/Email when the report data matches the
// current inputs (status 'synced'); otherwise disable and explain why.
function setReportSyncState(status, message) {
  const enabled = status === 'synced';
  document.querySelectorAll('.report-stale-warning').forEach((warn) => {
    if (status === 'stale') {
      warn.textContent = message || '⚠️ This report is out of date — change an input to refresh it before downloading.';
      warn.classList.remove('d-none');
    } else {
      warn.classList.add('d-none');
    }
  });
  document.querySelectorAll('.report-download-link, .report-email-submit').forEach((el) => {
    el.classList.toggle('disabled', !enabled);
    el.style.pointerEvents = enabled ? '' : 'none';
    el.style.opacity = enabled ? '' : '0.5';
    if (el.tagName === 'BUTTON') el.disabled = !enabled;
  });
}

function queueBudgetSync(total, allocations, governmentShouldCost, annualHours, scopeInputs, baselineWage) {
  setReportSyncState('pending'); // inputs changed; report not current until the sync confirms
  window.clearTimeout(syncTimer);
  syncTimer = window.setTimeout(() => syncBudget(total, allocations, governmentShouldCost, annualHours, scopeInputs, baselineWage), 300);
}

async function syncBudget(total, allocations, governmentShouldCost, annualHours, scopeInputs, baselineWage) {
  try {
    // FREE: just store the report payload so the downloadable report always
    // matches the screen. Editing/fine-tuning never costs credits — the charge
    // happens once, server-side, when a report is actually downloaded/emailed.
    const res = await fetch('{{ route('backend.report-payload.store') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        type: 'budget-calculator',
        scenario: {
          meta: {
            // baselineWage drives the GASQ TCO formula on the PDF side.
            baselineWage: baselineWage,
            governmentShouldCostHourly: governmentShouldCost,
            annualBillableHours: annualHours,
            annualBudget: total,
            allocations,
            // Send the 4 scope inputs as both nested scope.* and flat fields
            // so the PDF view can read either shape.
            scope: scopeInputs,
            hoursPerDay: scopeInputs?.hoursOfCoveragePerDay,
            daysPerWeek: scopeInputs?.daysOfCoveragePerWeek,
            weeksPerYear: scopeInputs?.weeksOfCoverage,
            staffPerShift: scopeInputs?.staffPerShift,
            contact: bgContact(),
          }
        },
        result: { kpis: bgTco || {} },
      })
    });

    if (res.ok) {
      setReportSyncState('synced'); // report data now matches the current inputs
    } else {
      setReportSyncState('stale', '⚠️ Could not save the report just now — change an input to retry before downloading.');
    }
  } catch (error) {
    setReportSyncState('stale', '⚠️ Could not save the report (connection issue) — change an input to retry.');
    console.error(error);
  }
}

// GASQ Workforce-to-Post™ TCO is derived server-side (BudgetTcoEngine) so the
// formula and its constants never ship to the browser. We cache the latest
// server result in bgTco and render from it; allocation sliders re-render
// instantly because they only scale the server-provided total.
const BUDGET_TCO_URL = @json(route('backend.budget-tco.compute'));
let bgTco = null;
let bgTcoTimer = null;

function bgScopePayload() {
  return {
    meta: {
      baselineWage: g('bg_govShouldCost'),
      hoursPerDay: g('bg_hoursPerDay'),
      daysPerWeek: g('bg_daysPerWeek'),
      weeksPerYear: g('bg_weeksPerYear'),
      staffPerShift: g('bg_staffPerShift'),
    },
  };
}

async function fetchBgTco() {
  try {
    const res = await fetch(BUDGET_TCO_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ scenario: bgScopePayload() }),
    });
    if (!res.ok) return;
    const data = await res.json();
    bgTco = data.kpis || bgTco;
    calcBudget();
  } catch (e) {
    // Keep last good values on a transient failure.
  }
}

// Baseline wage / coverage scope changed → recompute TCO on the server (debounced).
function scheduleBgTcoFetch() {
  clearTimeout(bgTcoTimer);
  bgTcoTimer = setTimeout(fetchBgTco, 300);
}

// Contact details that flow onto the PDF report. Read fresh on each sync.
function bgContact() {
  const v = (id) => (document.getElementById(id)?.value || '').trim();
  return {
    contactName: v('bg_contactName'),
    companyName: v('bg_companyName'),
    contactAddress: v('bg_contactAddress'),
    contactEmail: v('bg_contactEmail'),
    contactPhone: v('bg_contactPhone'),
  };
}

function calcBudget() {
  const baselineWage = g('bg_govShouldCost');

  // Coverage scope inputs — read here for the saved-scenario sync payload below.
  const hoursPerDay = Math.min(24, Math.max(1, g('bg_hoursPerDay') || 24));
  const daysPerWeek = Math.min(7, Math.max(1, g('bg_daysPerWeek') || 7));
  const weeksPerYear = Math.min(52, Math.max(1, g('bg_weeksPerYear') || 52));
  const staffPerShift = Math.min(100, Math.max(1, g('bg_staffPerShift') || 1));

  // All TCO figures come from the server (BudgetTcoEngine). Render from the
  // cached result; until the first response lands these are 0.
  const t = bgTco || {};
  const annualHours = t.annualHours || 0;
  const loadedWage = t.loadedWage || 0;
  const internalTcoHourly = t.internalTcoHourly || 0;
  const vendorTcoHourly = t.vendorTcoHourly || 0;
  const total = t.total || 0;
  const vendorOfferTotal = t.vendorOfferTotal || 0;
  const capitalRecoveryAnnual = t.capitalRecoveryAnnual || 0;

  // Mirror derived values to the hidden fields other page logic reads.
  const annualHoursEl = document.getElementById('bg_annualHours');
  if (annualHoursEl) annualHoursEl.value = annualHours;
  const vendorTcoEl = document.getElementById('bg_vendorTco');
  if (vendorTcoEl) vendorTcoEl.value = vendorTcoHourly.toFixed(2);

  const monthlyHours = annualHours / 12;
  const weeklyHours = annualHours / weeksPerYear;
  const dailyHours = annualHours / 365;

  // For backward compatibility with downstream sync payload.
  const governmentShouldCost = vendorTcoHourly;
  const itemStates = ALL_ITEMS.map((item) => ({ ...item, pct: g(item.id) }));
  const allocationsPayload = Object.fromEntries(itemStates.map((item) => [item.key, item.pct]));
  const sumPct = itemStates.reduce((sum, item) => sum + item.pct, 0);
  const warning = document.getElementById('bg_warning');
  const pctEl = document.getElementById('bg_totalPct');
  const offTarget = Math.abs(sumPct - 100) > TOTAL_TOLERANCE;

  if (pctEl) {
    pctEl.textContent = fmtPct(sumPct);
    pctEl.className = `fw-bold ${offTarget ? 'text-danger' : 'text-success'}`;
  }
  if (warning) warning.classList.toggle('d-none', !offTarget);

  // Allocation 100% base = the VENDOR total (the line-item allocations break
  // down the vendor's contract value, not the buyer's in-house TCO).
  const allocationBase = vendorOfferTotal;

  itemStates.forEach((item) => {
    const amount = allocationBase * item.pct / 100;
    const barEl = document.getElementById(`${item.id}_bar`);
    const amtEl = document.getElementById(`${item.id}_amt`);
    if (barEl) barEl.style.width = `${Math.min(item.pct, 100)}%`;
    if (amtEl) amtEl.textContent = fmt(amount);
  });

  const groupStates = BUDGET_GROUPS.map((group) => {
    const items = itemStates.filter((item) => item.groupKey === group.key);
    const pct = items.reduce((sum, item) => sum + item.pct, 0);
    const amount = allocationBase * pct / 100;
    return { ...group, items, pct, amount };
  });

  groupStates.forEach((group) => {
    setText(`bg_group_${group.key}_amt`, fmt(group.amount));
    setText(`bg_group_${group.key}_pct`, fmtPct(group.pct));
  });

  const totalEl = document.getElementById('bg_total');
  if (totalEl) {
    // Format with comma thousands separators — e.g. 962742.86 → "962,742.86"
    totalEl.value = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Side-by-side comparison: Buyer (left, red) vs Vendor (right, green) + Capital Recovery callout.
  const capitalRecoveryPerHour = internalTcoHourly - vendorTcoHourly;
  setText('r_internalTco', fmt(internalTcoHourly));      // Buyer Cost / hr (left card)
  setText('r_buyerAnnual', fmt(total));                  // Buyer annual (left card)
  setText('r_govShouldCost', fmt(vendorTcoHourly));      // Vendor Cost / hr (right card)
  setText('r_vendorAnnual', fmt(vendorOfferTotal));      // Vendor annual (right card)
  setText('r_recoveryPerHr', fmt(capitalRecoveryPerHour));
  setText('r_recoveryAnnual', fmt(capitalRecoveryAnnual));
  setText('r_loadedWage', fmt(loadedWage));
  setText('r_hours', Math.round(annualHours).toLocaleString('en-US'));
  setText('r_annual', fmt(total));
  setText('r_monthly', fmt(total / 12));
  setText('r_weekly', fmt(total / weeksPerYear));
  setText('r_daily', fmt(total / 365));
  setText('r_hours_yearly', fmtHours(annualHours));
  setText('r_hours_monthly', fmtHours(monthlyHours));
  setText('r_hours_weekly', fmtHours(weeklyHours));
  setText('r_hours_daily', fmtHours(Math.ceil(dailyHours)));

  // Sum of all allocation groups equals the derived total — display it prominently
  // so the user can see the contract/budget value at a glance.
  const groupSum = groupStates.reduce((acc, group) => acc + group.amount, 0);
  setText('bg_contract_total', fmt(groupSum));

  const groupSummary = document.getElementById('bg_group_summary');
  if (groupSummary) groupSummary.innerHTML = groupStates.map((group) => `
    <div class="budget-group-summary-card d-flex justify-content-between align-items-center gap-3">
      <div>
        <div class="fw-semibold small">${group.label}</div>
        <div class="text-gasq-muted small">${group.description}</div>
      </div>
      <div class="text-end">
        <div class="fw-medium">${fmt(group.amount)}</div>
        <div class="text-gasq-muted small">${fmtPct(group.pct)}</div>
      </div>
    </div>
  `).join('');

  const breakdown = document.getElementById('bg_breakdown');
  if (breakdown) breakdown.innerHTML = groupStates.map((group) => {
    const visibleItems = group.items.filter((item) => item.pct > 0);
    const rows = visibleItems.length > 0
      ? visibleItems.map((item) => {
          const amount = total * item.pct / 100;
          return `
            <div class="budget-breakdown-item d-flex justify-content-between align-items-center small gap-3">
              <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:${item.color}"></span>
                <span class="text-gasq-muted">${item.label}</span>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="fw-medium">${fmt(amount)}</span>
                <span class="badge text-bg-secondary" style="font-size:0.65rem">${fmtPct(item.pct)}</span>
              </div>
            </div>
          `;
        }).join('')
      : '<div class="small text-gasq-muted">No allocation assigned yet.</div>';

    return `
      <div class="budget-breakdown-group">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold small">${group.label}</div>
          <div class="small text-gasq-muted">${fmtPct(group.pct)} · ${fmt(group.amount)}</div>
        </div>
        ${rows}
      </div>
    `;
  }).join('');

  const laborPct = groupStates
    .filter((group) => group.benchmarked)
    .reduce((sum, group) => sum + group.pct, 0);
  setText('ins_govShouldCost', fmt(governmentShouldCost));
  setText('ins_annualHours', Math.round(annualHours).toLocaleString('en-US'));
  setText('ins_laborPct', fmtPct(laborPct));

  const laborStatus = document.getElementById('ins_laborStatus');
  if (laborStatus) {
    if (laborPct < 55) {
      laborStatus.textContent = 'Below benchmark';
      laborStatus.className = 'fw-medium text-warning';
    } else if (laborPct > 70) {
      laborStatus.textContent = 'Above benchmark';
      laborStatus.className = 'fw-medium text-danger';
    } else {
      laborStatus.textContent = 'Within benchmark';
      laborStatus.className = 'fw-medium text-success';
    }
  }

  queueBudgetSync(total, allocationsPayload, governmentShouldCost, annualHours, {
    hoursOfCoveragePerDay: hoursPerDay,
    daysOfCoveragePerWeek: daysPerWeek,
    weeksOfCoverage: weeksPerYear,
    staffPerShift: staffPerShift,
  }, baselineWage);
  queueAppraisalRefresh();
}

// ---------- Appraisal Comparison Summary ----------
// Renders from the same server-computed TCO cache (bgTco) that calcBudget()
// uses, so the GASQ formula stays server-side. No extra round-trip and no
// credit consumption (the TCO endpoint is credit-free).
function queueAppraisalRefresh() {
  refreshAppraisal();
}

function refreshAppraisal() {
  // All figures come from the server-computed TCO cache (BudgetTcoEngine).
  // The diffs/period splits below are plain division of those values, not the
  // proprietary formula.
  const baselineWage = g('bg_govShouldCost');
  const t = bgTco || {};
  const annualHours = t.annualHours || 0;
  const internalTcoHourly = t.internalTcoHourly || 0;
  const vendorTcoHourly = t.vendorTcoHourly || 0;
  const annualCapitalRecovery = t.capitalRecoveryAnnual || 0;

  // Operating weeks of coverage — weekly figures divide by this, not a fixed 52,
  // so weekly = the real operating week and weekly × weeks = annual.
  const weeksOfCoverage = Math.min(52, Math.max(1, g('bg_weeksPerYear') || 52));
  const totalAnnualInt = t.total || 0;
  const totalAnnualVend = t.vendorOfferTotal || 0;
  const totalMonthlyInt = totalAnnualInt / 12;
  const totalMonthlyVend = totalAnnualVend / 12;
  const totalWeeklyInt = totalAnnualInt / weeksOfCoverage;
  const totalWeeklyVend = totalAnnualVend / weeksOfCoverage;

  const ftesRequired = t.ftesRequired || 0;
  const weeklyHours = annualHours / weeksOfCoverage;
  const monthlyHours = annualHours / 12;
  const monthsOfCoverage = Math.round(weeksOfCoverage * 12 / 52 * 10) / 10;
  const operationalCapitalPct = t.operationalCapitalPct || 0;
  const paybackMonths = t.paybackMonths || 0;
  const internalOt = t.internalOt || 0;
  const vendorOt = t.vendorOt || 0;
  const annualPerInt = t.annualPerInt || 0;
  const annualPerVend = t.annualPerVend || 0;

  // Rows: kind controls formatting (money vs hours/count).
  // Row 1 "Workforce Baseline Assumption Labor Rate" shows the user's baseline-wage
  // input directly — same on both columns since it's the shared starting point.
  const rows = [
    { description: 'Workforce Baseline Assumption Labor Rate', internal: baselineWage, vendor: baselineWage, kind: 'money' },
    { description: 'Workforce Cost to Protect Hourly Rate', internal: internalTcoHourly, vendor: vendorTcoHourly, kind: 'money' },
    { description: 'Overtime / Holiday Rate', internal: internalOt, vendor: vendorOt, kind: 'money' },
    @unless($isBuyerView)
    { description: 'Workforce Annual Cost per Security Professional', internal: annualPerInt, vendor: annualPerVend, kind: 'money' },
    @endunless
    { description: 'Total Weekly Hours of Coverage', internal: weeklyHours, vendor: weeklyHours, kind: 'hours' },
    { description: 'Total Monthly Hours of Coverage', internal: monthlyHours, vendor: monthlyHours, kind: 'hours' },
    { description: 'Total Annual Hours of Coverage', internal: annualHours, vendor: annualHours, kind: 'hours' },
    { description: 'Total Weeks of Coverage', internal: weeksOfCoverage, vendor: weeksOfCoverage, kind: 'count' },
    { description: 'Total Months of Coverage', internal: monthsOfCoverage, vendor: monthsOfCoverage, kind: 'count' },
    { description: 'Total Workforce Required for Coverage', internal: ftesRequired, vendor: ftesRequired, kind: 'count' },
    { description: 'Total Weekly Cost', internal: totalWeeklyInt, vendor: totalWeeklyVend, kind: 'money' },
    { description: 'Total Monthly Cost', internal: totalMonthlyInt, vendor: totalMonthlyVend, kind: 'money' },
    { description: 'Total Annual Cost', internal: totalAnnualInt, vendor: totalAnnualVend, kind: 'money' },
  ];

  const footerRows = [
    { description: 'Operational Capital Recovered', vendor: annualCapitalRecovery, kind: 'money' },
    { description: 'Operational Capital Recovered (%)', vendor: operationalCapitalPct, kind: 'percent' },
    { description: 'Payback & Recovery Period', vendor: paybackMonths, kind: 'months' },
  ];

  const body = document.getElementById('bg_ap_body');
  const foot = document.getElementById('bg_ap_foot');
  if (!body || !foot) return;

  const formatCell = (value, kind) => {
    if (value === null || value === undefined) return '—';
    if (kind === 'money') return fmt(value);
    if (kind === 'percent') return `${Number(value).toFixed(0)}%`;
    if (kind === 'months') return `${Number(value).toFixed(1)} months`;
    if (kind === 'hours' || kind === 'count') return fmtHours(value);
    return String(value);
  };

  body.innerHTML = rows.map((r) => `
    <tr>
      <td>${r.description}</td>
      <td class="text-end font-monospace">${formatCell(r.internal, r.kind)}</td>
      <td class="text-end font-monospace"><span class="bg-redacted">${formatCell(r.vendor, r.kind)}</span></td>
    </tr>
  `).join('');

  foot.innerHTML = footerRows.map((r) => `
    <tr class="fw-semibold" style="background:#fff4e6;">
      <td>${r.description}</td>
      <td class="text-end font-monospace">—</td>
      <td class="text-end font-monospace"><span class="bg-redacted">${formatCell(r.vendor, r.kind)}</span></td>
    </tr>
  `).join('');
}

function hydrateSavedBudget() {
  const meta = savedScenario?.meta || {};
  const allocations = meta.allocations || {};

  if (meta.governmentShouldCostHourly !== undefined) {
    const govEl = document.getElementById('bg_govShouldCost');
    if (govEl) govEl.value = meta.governmentShouldCostHourly;
  }

  if (meta.annualBillableHours !== undefined) {
    const hoursEl = document.getElementById('bg_annualHours');
    if (hoursEl) hoursEl.value = meta.annualBillableHours;
  }

  Object.entries(allocations).forEach(([key, value]) => {
    if (value === undefined || value === null) return;
    const el = document.getElementById(`bg_${key}`);
    if (el) el.value = value;
  });
}

function resetBudget() {
  const govEl = document.getElementById('bg_govShouldCost');
  if (govEl) govEl.value = DEFAULT_GOVERNMENT_SHOULD_COST;

  const hoursEl = document.getElementById('bg_annualHours');
  if (hoursEl) hoursEl.value = DEFAULT_ANNUAL_BILLABLE_HOURS;

  const totalEl = document.getElementById('bg_total');
  if (totalEl) {
    totalEl.value = Number(DEFAULT_TOTAL).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  Object.entries(DEFAULTS).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) el.value = value;
    const rangeEl = document.getElementById(`${id}_range`);
    if (rangeEl) rangeEl.value = value;
  });

  // Baseline/scope reset to defaults → recompute TCO on the server, then render.
  fetchBgTco();
}

document.addEventListener('DOMContentLoaded', () => {
  setReportSyncState('pending'); // disable Download/Email until the first sync confirms
  hydrateSavedBudget();
  initSliderSync();

  // ===== Questionnaire ↔ Calculator handoff =====
  // When the buyer comes here from /jobs/create via the "Fine-tune in calculator"
  // CTA, we pre-fill scope inputs from sessionStorage and offer a "Save & return"
  // button that ships the calculator's annual budget back to the questionnaire.
  (function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('from') !== 'questionnaire') return;

    const SCOPE_KEY = 'gasq_questionnaire_scope';
    const BUDGET_KEY = 'gasq_questionnaire_budget_override';

    let scope = null;
    try { scope = JSON.parse(localStorage.getItem(SCOPE_KEY) || 'null'); } catch (e) {}

    if (scope) {
      const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (!el || val === undefined || val === null || val === '') return;
        el.value = val;
        const range = document.getElementById(id + '_range');
        if (range) range.value = val;
      };
      if (scope.hoursPerDay > 0) setVal('bg_hoursPerDay', scope.hoursPerDay);
      if (scope.daysPerWeek > 0) setVal('bg_daysPerWeek', scope.daysPerWeek);
      if (scope.weeksPerYear > 0) setVal('bg_weeksPerYear', scope.weeksPerYear);
      if (scope.staffPerShift > 0) setVal('bg_staffPerShift', scope.staffPerShift);
      if (scope.baselineWage > 0) setVal('bg_govShouldCost', scope.baselineWage);
      if (typeof fetchBgTco === 'function') fetchBgTco();
    }

    const saveBtn = document.getElementById('save_and_return_to_questionnaire');
    if (saveBtn) {
      saveBtn.addEventListener('click', async function () {
        const baselineWage = parseFloat(document.getElementById('bg_govShouldCost')?.value) || 0;
        const hoursPerDay = parseFloat(document.getElementById('bg_hoursPerDay')?.value) || 0;
        const daysPerWeek = parseFloat(document.getElementById('bg_daysPerWeek')?.value) || 0;
        const weeksPerYear = parseFloat(document.getElementById('bg_weeksPerYear')?.value) || 0;
        const staffPerShift = parseFloat(document.getElementById('bg_staffPerShift')?.value) || 1;

        // Vendor (outsourced) hourly comes from the server-computed TCO; the
        // GASQ formula constants stay server-side. Refresh first so the value
        // reflects any just-typed input that hasn't synced yet.
        await fetchBgTco();
        const outsourcedHourly = (bgTco && bgTco.vendorTcoHourly) || 0;
        const weeklyHours = hoursPerDay * daysPerWeek * Math.max(1, staffPerShift);
        const annualCoverageHours = weeklyHours * 52;
        const annualBudget = outsourcedHourly * annualCoverageHours;
        const monthlyBudget = annualBudget / 12;

        const override = {
          baselineWage: baselineWage,
          hourlyBudget: outsourcedHourly,
          monthlyBudget: monthlyBudget,
          annualBudget: annualBudget,
          annualCoverageHours: annualCoverageHours,
          hoursPerDay: hoursPerDay,
          daysPerWeek: daysPerWeek,
          weeksPerYear: weeksPerYear,
          staffPerShift: staffPerShift,
          source: 'budget-calculator',
          ts: Date.now(),
        };

        try { localStorage.setItem(BUDGET_KEY, JSON.stringify(override)); } catch (e) {}

        // If this calculator tab was opened from the questionnaire (target="_blank"),
        // try to close it so the user lands back on the questionnaire tab that's
        // already listening for the localStorage event. If the browser blocks
        // window.close (no opener), fall back to redirecting in place.
        try { window.close(); } catch (e) {}
        setTimeout(function () {
          if (!document.hidden) {
            window.location.href = '{{ route('jobs.create') }}';
          }
        }, 250);
      });
    }
  })();

  // Initial load: fetch server-computed TCO, then render.
  fetchBgTco();
});
</script>
@include('partials.calculator-protect')
@endpush
