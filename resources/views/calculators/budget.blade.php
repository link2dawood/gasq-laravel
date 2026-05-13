@extends('layouts.app')
@section('title', 'Workforce Absorbed Rate Calculator')
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
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-piggy-bank text-primary"></i> Workforce Absorbed Rate Calculator
        </h1>
        <div class="text-gasq-muted small">Plan and analyze your workforce budget across detailed spreadsheet line items.</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="resetBudget()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
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
                <input type="number" id="bg_govShouldCost" class="form-control fs-6 fw-semibold" value="25.00" step="0.01" min="0" oninput="calcBudget()">
                <input type="range" id="bg_govShouldCost_range" class="form-range mb-0" min="0" max="100" step="0.01" value="25.00" data-sync="bg_govShouldCost">
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
                <input type="number" id="bg_hoursPerDay" class="form-control form-control-sm fw-semibold" value="24" step="1" min="1" max="24" oninput="calcBudget()">
                <input type="range" id="bg_hoursPerDay_range" class="form-range mb-0" min="1" max="24" step="1" value="24" data-sync="bg_hoursPerDay">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Days per week</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_daysPerWeek" class="form-control form-control-sm fw-semibold" value="7" step="1" min="1" max="7" oninput="calcBudget()">
                <input type="range" id="bg_daysPerWeek_range" class="form-range mb-0" min="1" max="7" step="1" value="7" data-sync="bg_daysPerWeek">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Weeks per year</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_weeksPerYear" class="form-control form-control-sm fw-semibold" value="52" step="1" min="1" max="52" oninput="calcBudget()">
                <input type="range" id="bg_weeksPerYear_range" class="form-range mb-0" min="1" max="52" step="1" value="52" data-sync="bg_weeksPerYear">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Staff per 8-hour shift</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_staffPerShift" class="form-control form-control-sm fw-semibold" value="1" step="1" min="1" max="100" oninput="calcBudget()">
                <input type="range" id="bg_staffPerShift_range" class="form-range mb-0" min="1" max="100" step="1" value="1" data-sync="bg_staffPerShift">
              </div>
            </div>
          </div>

          {{-- Annual Coverage Hours now derived from the 4 scope inputs above. --}}
          <input type="hidden" id="bg_annualHours" value="8736">
          {{-- Vendor TCO hidden — now derived from Baseline Wage via the GASQ formula. --}}
          <input type="hidden" id="bg_vendorTco" value="0">

          <div>
            <label class="form-label fw-medium">Derived Annual Budget ($)</label>
            <div class="input-group">
              <span class="input-group-text fs-5 fw-semibold">$</span>
              <input type="number" id="bg_total" class="form-control fs-5 fw-semibold" value="0" step="1000" readonly>
            </div>
            <div class="small text-gasq-muted mt-1">
              <strong>Formula chain:</strong> Baseline Wage ÷ 0.70 × 3,744 ÷ 1,456 × 0.70 × Annual Coverage Hours
              <br>= Loaded Wage → Internal TCO → Vendor TCO → Total Budget
            </div>
          </div>

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

        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card gasq-card mb-4">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Budget Summary</h5></div>
        <div class="card-body">
          <div class="budget-benchmark-card mb-4">
            <div class="text-uppercase small fw-semibold text-gasq-muted mb-1">Buyer's Total Cost of Ownership ($/hr)</div>
            <div class="h3 fw-bold text-primary mb-1" id="r_internalTco">$0.00</div>
            <div class="small text-gasq-muted mb-3">Derived from Baseline Wage via GASQ formula — drives the annual budget</div>

            <div class="row g-2 small">
              <div class="col-6">
                <div class="text-gasq-muted">Loaded Wage</div>
                <div class="fw-semibold" id="r_loadedWage">$0.00</div>
              </div>
              <div class="col-6">
                <div class="text-gasq-muted">Vendor Offer Rate</div>
                <div class="fw-semibold text-success" id="r_govShouldCost">$0.00</div>
              </div>
              <div class="col-6">
                <div class="text-gasq-muted">Capital Recovery / hr</div>
                <div class="fw-semibold text-success" id="r_recoveryPerHr">$0.00</div>
              </div>
              <div class="col-6">
                <div class="text-gasq-muted">Annual Coverage</div>
                <div class="fw-semibold" id="r_hours">0</div>
              </div>
            </div>

            <div class="mt-3">
              <a href="{{ url('/workforce-appraisal-report') }}" class="small fw-semibold text-decoration-none">Open Workforce Appraisal Report</a>
            </div>
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
        </div>
      </div>
    </div>
  </div>

  <div class="card gasq-card mt-4">
    <div class="card-header py-3">
      <h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2">
        <i class="fa fa-balance-scale text-primary"></i> Appraisal Comparison Summary
      </h5>
      <div class="text-gasq-muted small">Side-by-side comparison of internal should-cost vs vendor TCO. Updates live with the inputs above.</div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th>Description</th>
              <th class="text-end font-monospace">Internal should-cost</th>
              <th class="text-end font-monospace">Vendor TCO</th>
            </tr>
          </thead>
          <tbody id="bg_ap_body"></tbody>
          <tbody id="bg_ap_foot"></tbody>
        </table>
      </div>
    </div>
  </div>

  <x-report-actions reportType="budget-calculator" />

</div>
</div>
@endsection

@push('scripts')
<style>
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
      calcBudget();
    });

    numEl.addEventListener('input', () => {
      syncRangeFromNumber();
    });
  });
}

function queueBudgetSync(total, allocations, governmentShouldCost, annualHours) {
  window.clearTimeout(syncTimer);
  syncTimer = window.setTimeout(() => syncBudget(total, allocations, governmentShouldCost, annualHours), 300);
}

async function syncBudget(total, allocations, governmentShouldCost, annualHours) {
  try {
    const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'budget-calculator']) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        version: 'v24',
        scenario: {
          meta: {
            governmentShouldCostHourly: governmentShouldCost,
            annualBillableHours: annualHours,
            annualBudget: total,
            allocations
          }
        }
      })
    });

    const data = await res.json();
    if (!res.ok || !data || !data.ok) {
      console.error(data);
    }
  } catch (error) {
    console.error(error);
  }
}

// GASQ Workforce-to-Post™ formula constants — shared by calcBudget() and refreshAppraisal()
// so the entire right column moves in lockstep with the Baseline Wage slider.
const EMPLOYER_FRINGE_FACTOR = 0.70;
const PAID_HOURS_PER_FTE = 3744;
const BILLABLE_HOURS_PER_FTE = 1456;
const VENDOR_DISCOUNT_FACTOR = 0.70;

function calcBudget() {
  const baselineWage = g('bg_govShouldCost');

  // Annual coverage hours derived from the 4 scope inputs (matches the standard
  // GASQ calculator: hoursPerDay × daysPerWeek × weeksPerYear × staffPerShift).
  const hoursPerDay = Math.min(24, Math.max(1, g('bg_hoursPerDay') || 24));
  const daysPerWeek = Math.min(7, Math.max(1, g('bg_daysPerWeek') || 7));
  const weeksPerYear = Math.min(52, Math.max(1, g('bg_weeksPerYear') || 52));
  const staffPerShift = Math.min(100, Math.max(1, g('bg_staffPerShift') || 1));
  const annualHours = hoursPerDay * daysPerWeek * weeksPerYear * staffPerShift;

  // Mirror to the hidden annualHours field so the appraisal block and any
  // downstream consumer reads a consistent value.
  const annualHoursEl = document.getElementById('bg_annualHours');
  if (annualHoursEl) annualHoursEl.value = annualHours;

  const monthlyHours = annualHours / 12;
  const weeklyHours = annualHours / 52;
  const dailyHours = annualHours / 365;

  // Baseline Wage → Loaded Wage → Buyer's TCO → Vendor TCO.
  // The buyer's Total Cost of Ownership ($91.83/hr from a $25 baseline) is the budget basis;
  // outsourcing at Vendor TCO ($64.28/hr) recovers the difference.
  const loadedWage = baselineWage > 0 ? baselineWage / EMPLOYER_FRINGE_FACTOR : 0;
  const internalTcoHourly = loadedWage > 0 ? (loadedWage * PAID_HOURS_PER_FTE) / BILLABLE_HOURS_PER_FTE : 0;
  const vendorTcoHourly = internalTcoHourly * VENDOR_DISCOUNT_FACTOR;
  // Total Budget reflects the BUYER'S TCO × coverage hours — what they'd spend in-house.
  const total = internalTcoHourly * annualHours;
  const vendorOfferTotal = vendorTcoHourly * annualHours;
  const capitalRecoveryAnnual = total - vendorOfferTotal;

  // Mirror to the hidden vendor-TCO input so any other consumer reads the derived value.
  const vendorTcoEl = document.getElementById('bg_vendorTco');
  if (vendorTcoEl) vendorTcoEl.value = vendorTcoHourly.toFixed(2);

  // For backward compatibility with downstream sync payload.
  const governmentShouldCost = vendorTcoHourly;
  const itemStates = ALL_ITEMS.map((item) => ({ ...item, pct: g(item.id) }));
  const allocationsPayload = Object.fromEntries(itemStates.map((item) => [item.key, item.pct]));
  const sumPct = itemStates.reduce((sum, item) => sum + item.pct, 0);
  const warning = document.getElementById('bg_warning');
  const pctEl = document.getElementById('bg_totalPct');
  const offTarget = Math.abs(sumPct - 100) > TOTAL_TOLERANCE;

  pctEl.textContent = fmtPct(sumPct);
  pctEl.className = `fw-bold ${offTarget ? 'text-danger' : 'text-success'}`;
  warning.classList.toggle('d-none', !offTarget);

  itemStates.forEach((item) => {
    const amount = total * item.pct / 100;
    const barEl = document.getElementById(`${item.id}_bar`);
    const amtEl = document.getElementById(`${item.id}_amt`);
    if (barEl) barEl.style.width = `${Math.min(item.pct, 100)}%`;
    if (amtEl) amtEl.textContent = fmt(amount);
  });

  const groupStates = BUDGET_GROUPS.map((group) => {
    const items = itemStates.filter((item) => item.groupKey === group.key);
    const pct = items.reduce((sum, item) => sum + item.pct, 0);
    const amount = total * pct / 100;
    return { ...group, items, pct, amount };
  });

  groupStates.forEach((group) => {
    setText(`bg_group_${group.key}_amt`, fmt(group.amount));
    setText(`bg_group_${group.key}_pct`, fmtPct(group.pct));
  });

  const totalEl = document.getElementById('bg_total');
  if (totalEl) totalEl.value = total.toFixed(2);

  // Right-side benchmark card: lead with the BUYER's TCO (which drives the annual budget),
  // and show the vendor offer rate + capital recovery as secondary metrics.
  const capitalRecoveryPerHour = internalTcoHourly - vendorTcoHourly;
  setText('r_internalTco', fmt(internalTcoHourly));    // big number — Buyer's TCO / hr
  setText('r_govShouldCost', fmt(vendorTcoHourly));    // vendor offer rate
  setText('r_loadedWage', fmt(loadedWage));
  setText('r_recoveryPerHr', fmt(capitalRecoveryPerHour));
  setText('r_hours', Math.round(annualHours).toLocaleString('en-US'));
  setText('r_annual', fmt(total));
  setText('r_monthly', fmt(total / 12));
  setText('r_weekly', fmt(total / 52));
  setText('r_daily', fmt(total / 365));
  setText('r_hours_yearly', fmtHours(annualHours));
  setText('r_hours_monthly', fmtHours(monthlyHours));
  setText('r_hours_weekly', fmtHours(weeklyHours));
  setText('r_hours_daily', fmtHours(dailyHours));

  // Sum of all allocation groups equals the derived total — display it prominently
  // so the user can see the contract/budget value at a glance.
  const groupSum = groupStates.reduce((acc, group) => acc + group.amount, 0);
  setText('bg_contract_total', fmt(groupSum));

  const groupSummary = document.getElementById('bg_group_summary');
  groupSummary.innerHTML = groupStates.map((group) => `
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
  breakdown.innerHTML = groupStates.map((group) => {
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

  queueBudgetSync(total, allocationsPayload, governmentShouldCost, annualHours);
  queueAppraisalRefresh();
}

// ---------- Appraisal Comparison Summary ----------
// Computed entirely client-side using the same formula as WorkforceAppraisalReportEngine.
// No backend call → no credit consumption → never silently empty due to 402.
function queueAppraisalRefresh() {
  // Renders are cheap, no debounce needed.
  refreshAppraisal();
}

function refreshAppraisal() {
  // ---------- GASQ side-by-side TCO formula ----------
  // 1. Loaded wage           = baselineWage / 0.70
  // 2. Annual workforce cost = loadedWage × 3,744  (paid hours per FTE inc. PTO/burden)
  // 3. Internal TCO/hr       = annualWorkforceCost / 1,456  (billable hours per FTE)
  // 4. Vendor TCO/hr         = internalTCO × 0.70  (vendor delivers same scope at 70% of internal)
  // 5. Capital recovery/hr   = internalTCO − vendorTCO
  // 6. Annual capital recovery = capitalRecovery/hr × annualCoverageHours
  // Constants are defined globally so calcBudget() and refreshAppraisal() share them.
  const baselineWage = g('bg_govShouldCost');
  const annualHours = g('bg_annualHours') || 8736;

  const loadedWage = baselineWage > 0 ? baselineWage / EMPLOYER_FRINGE_FACTOR : 0;
  const annualWorkforceCost = loadedWage * PAID_HOURS_PER_FTE;
  const internalTcoHourly = annualWorkforceCost / BILLABLE_HOURS_PER_FTE;
  const vendorTcoHourly = internalTcoHourly * VENDOR_DISCOUNT_FACTOR;
  const capitalRecoveryPerHour = internalTcoHourly - vendorTcoHourly;
  const annualCapitalRecovery = capitalRecoveryPerHour * annualHours;

  // Keep hidden Vendor TCO field in sync (some pieces of the page read it).
  const vendorTcoEl = document.getElementById('bg_vendorTco');
  if (vendorTcoEl) vendorTcoEl.value = vendorTcoHourly.toFixed(2);

  // Annual cost projection for the full contract
  const totalAnnualInt = internalTcoHourly * annualHours;
  const totalAnnualVend = vendorTcoHourly * annualHours;
  const totalMonthlyInt = totalAnnualInt / 12;
  const totalMonthlyVend = totalAnnualVend / 12;
  const totalWeeklyInt = totalAnnualInt / 52;
  const totalWeeklyVend = totalAnnualVend / 52;

  const ftesRequired = annualHours > 0 ? Math.max(1, Math.ceil(annualHours / BILLABLE_HOURS_PER_FTE)) : 0;
  const weeklyHours = annualHours / 52;
  const monthlyHours = annualHours / 12;

  const operationalCapitalPct = totalAnnualInt > 0
    ? Math.round(100 * annualCapitalRecovery / totalAnnualInt)
    : 0;
  const monthlySavings = totalMonthlyInt - totalMonthlyVend;
  const paybackMonths = monthlySavings > 0.01
    ? Math.ceil(annualCapitalRecovery / monthlySavings)
    : 0;

  const otMult = 1.5;
  const internalOt = internalTcoHourly * otMult;
  const vendorOt = vendorTcoHourly * otMult;

  // Annual cost per security professional (single FTE) — billable hours basis.
  const annualPerInt = internalTcoHourly * BILLABLE_HOURS_PER_FTE;
  const annualPerVend = vendorTcoHourly * BILLABLE_HOURS_PER_FTE;

  // Rows: kind controls formatting (money vs hours/count).
  // Row 1 "Workforce Baseline Assumption Labor Rate" shows the user's baseline-wage
  // input directly — same on both columns since it's the shared starting point.
  const rows = [
    { description: 'Workforce Baseline Assumption Labor Rate', internal: baselineWage, vendor: baselineWage, kind: 'money' },
    { description: 'Overtime / Holiday Rate', internal: internalOt, vendor: vendorOt, kind: 'money' },
    { description: 'Workforce Annual Cost per Security Professional', internal: annualPerInt, vendor: annualPerVend, kind: 'money' },
    { description: 'Total Weekly Hours of Coverage', internal: weeklyHours, vendor: weeklyHours, kind: 'hours' },
    { description: 'Total Monthly Hours of Coverage', internal: monthlyHours, vendor: monthlyHours, kind: 'hours' },
    { description: 'Total Annual Hours of Coverage', internal: annualHours, vendor: annualHours, kind: 'hours' },
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
    if (kind === 'months') return `${value} months`;
    if (kind === 'hours' || kind === 'count') return fmtHours(value);
    return String(value);
  };

  body.innerHTML = rows.map((r) => `
    <tr>
      <td>${r.description}</td>
      <td class="text-end font-monospace">${formatCell(r.internal, r.kind)}</td>
      <td class="text-end font-monospace">${formatCell(r.vendor, r.kind)}</td>
    </tr>
  `).join('');

  foot.innerHTML = footerRows.map((r) => `
    <tr class="fw-semibold" style="background:#fff4e6;">
      <td>${r.description}</td>
      <td class="text-end font-monospace">—</td>
      <td class="text-end font-monospace">${formatCell(r.vendor, r.kind)}</td>
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
  if (totalEl) totalEl.value = DEFAULT_TOTAL.toFixed(2);

  Object.entries(DEFAULTS).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) el.value = value;
    const rangeEl = document.getElementById(`${id}_range`);
    if (rangeEl) rangeEl.value = value;
  });

  calcBudget();
}

document.addEventListener('DOMContentLoaded', () => {
  hydrateSavedBudget();
  initSliderSync();
  calcBudget();
});
</script>
@endpush
