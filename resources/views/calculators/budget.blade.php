@extends('layouts.app')
@section('title', 'Budget Calculator')
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
          <i class="fa fa-piggy-bank text-primary"></i> Security Budget Calculator
        </h1>
        <div class="text-gasq-muted small">Plan and analyze your security budget across detailed spreadsheet line items.</div>
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
            <div class="col-md-6">
              <label class="form-label fw-medium">Government Should-Cost ($/hr)</label>
              <div class="small text-gasq-muted mb-1">Current internal benchmark per hour</div>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_govShouldCost" class="form-control fs-6 fw-semibold" value="{{ number_format($defaultGovernmentShouldCost, 2, '.', '') }}" step="0.01" min="0" oninput="calcBudget()">
                <input type="range" id="bg_govShouldCost_range" class="form-range mb-0" min="0" max="250" step="0.01" value="{{ number_format($defaultGovernmentShouldCost, 2, '.', '') }}" data-sync="bg_govShouldCost">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Annual Billable Hours</label>
              <div class="small text-gasq-muted mb-1">Hours basis from Workforce Appraisal</div>
              <div class="d-flex align-items-center gap-2">
                <input type="number" id="bg_annualHours" class="form-control fs-6 fw-semibold" value="{{ number_format($defaultAnnualBillableHours, 0, '.', '') }}" step="1" min="0" oninput="calcBudget()">
                <input type="range" id="bg_annualHours_range" class="form-range mb-0" min="0" max="20000" step="1" value="{{ number_format($defaultAnnualBillableHours, 0, '.', '') }}" data-sync="bg_annualHours">
              </div>
            </div>
          </div>

          <div>
            <label class="form-label fw-medium">Derived Annual Budget ($)</label>
            <input type="number" id="bg_total" class="form-control fs-5 fw-semibold" value="{{ number_format($defaultTotal, 2, '.', '') }}" step="1000" readonly>
            <div class="small text-gasq-muted mt-1">Formula: Government Should-Cost × Annual Billable Hours</div>
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
            <div class="text-uppercase small fw-semibold text-gasq-muted mb-1">Government Should-Cost</div>
            <div class="h3 fw-bold text-primary mb-1" id="r_govShouldCost">$0.00</div>
            <div class="small text-gasq-muted mb-2">Current internal benchmark per hour</div>
            <div class="d-flex justify-content-between align-items-center small gap-3">
              <span class="text-gasq-muted">Annual billable hours</span>
              <span class="fw-semibold" id="r_hours">0</span>
            </div>
            <div class="mt-2">
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

          <h6 class="fw-semibold mb-3">Allocation Group Totals</h6>
          <div id="bg_group_summary" class="d-flex flex-column gap-2 mb-4"></div>

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

function calcBudget() {
  const governmentShouldCost = g('bg_govShouldCost');
  const annualHours = g('bg_annualHours');
  const total = governmentShouldCost * annualHours;
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

  setText('r_govShouldCost', fmt(governmentShouldCost));
  setText('r_hours', Math.round(annualHours).toLocaleString('en-US'));
  setText('r_annual', fmt(total));
  setText('r_monthly', fmt(total / 12));
  setText('r_weekly', fmt(total / 52));
  setText('r_daily', fmt(total / 365));

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
