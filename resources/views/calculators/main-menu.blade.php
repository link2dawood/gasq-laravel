@extends('layouts.app')
@section('title', 'Main Menu Calculator')
@section('header_variant', 'dashboard')

@section('content')
<div class="py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  {{-- Page header --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
        <i class="fa fa-calculator text-primary"></i> Main Menu Calculator
      </h1>
      <div class="text-gasq-muted small">Security workforce planning &amp; cost analysis</div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary btn-sm" id="btnDownload" onclick="downloadReport()"><i class="fa fa-download me-1"></i> Download</button>
      <button class="btn btn-primary btn-sm" onclick="emailReport()"><i class="fa fa-envelope me-1"></i> Email Report</button>
    </div>
  </div>

  {{-- Economic Justification --}}
  <div class="card gasq-card">
    <div class="card-body p-4">
      <div>

      {{-- ========== ECONOMIC JUSTIFICATION ========== --}}
      <div id="tab-justification">
        <div class="row g-4">
          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2"><i class="fa fa-chart-line text-primary"></i> Economic Justification</h5>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-medium">Company Name</label>
                <input type="text" id="ej_company" class="form-control" value="ABC COMPANY" oninput="calcEJ()">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Prepared By</label>
                <input type="text" id="ej_preparedBy" class="form-control" placeholder="Your name" oninput="calcEJ()">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Employee True Hourly Cost ($/hr)</label>
                <input type="number" id="ej_employeeCost" class="form-control" value="133.00" step="0.01" oninput="calcEJ()">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Weekly Hours Performed</label>
                <input type="number" id="ej_weeklyHours" class="form-control" value="168" oninput="calcEJ()">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Weeks in Year</label>
                <input type="number" id="ej_weeksInYear" class="form-control" value="52" oninput="calcEJ()">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Months in Year</label>
                <input type="number" id="ej_monthsInYear" class="form-control" value="12" oninput="calcEJ()">
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3">ROI Analysis</h5>

            {{-- Two-column comparison table --}}
            <div class="table-responsive rounded" style="background:var(--gasq-muted-bg)">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th class="small fw-semibold">Metric</th>
                    <th class="small fw-semibold text-center">In-House</th>
                    <th class="small fw-semibold text-center">Vendor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td class="small text-gasq-muted">Hourly Cost</td><td class="text-center small fw-medium" id="ej_r_ihHourly">$0.00</td><td class="text-center small fw-medium" id="ej_r_vHourly">$0.00</td></tr>
                  <tr><td class="small text-gasq-muted">Weekly Cost</td><td class="text-center small fw-medium" id="ej_r_ihWeekly">$0.00</td><td class="text-center small fw-medium" id="ej_r_vWeekly">$0.00</td></tr>
                  <tr><td class="small text-gasq-muted">Monthly Cost</td><td class="text-center small fw-medium" id="ej_r_ihMonthly">$0.00</td><td class="text-center small fw-medium" id="ej_r_vMonthly">$0.00</td></tr>
                  <tr class="table-light fw-semibold"><td class="small">Annual Cost</td><td class="text-center small" id="ej_r_ihAnnual">$0.00</td><td class="text-center small" id="ej_r_vAnnual">$0.00</td></tr>
                </tbody>
              </table>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3)">
                  <div class="small text-gasq-muted mb-1">Cost Savings</div>
                  <div class="fs-5 fw-bold text-success" id="ej_r_savings">$0.00</div>
                </div>
              </div>
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:rgba(6,45,121,0.08);border:1px solid rgba(6,45,121,0.2)">
                  <div class="small text-gasq-muted mb-1">ROI %</div>
                  <div class="fs-5 fw-bold text-primary" id="ej_r_roi">0.0%</div>
                </div>
              </div>
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                  <div class="small text-gasq-muted mb-1">Payback Period</div>
                  <div class="fs-5 fw-bold" id="ej_r_payback">0.0 mo</div>
                </div>
              </div>
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                  <div class="small text-gasq-muted mb-1">Dollar for Dollar Return</div>
                  <div class="fs-5 fw-bold" id="ej_r_dollar">$0.00</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      </div><!-- /content -->
    </div><!-- /card-body -->
  </div><!-- /card -->

</div>
</div>
@endsection

@push('scripts')
<script>
const savedScenario = window.__gasqCalculatorState?.scenario || null;
const masterInputs = window.__gasqMasterInputs || {};
function fmt(v, dec = 2) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);
}
function fmtN(v, dec = 0) {
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);
}
function gNum(id) {
  const el = document.getElementById(id);
  const n = el ? parseFloat(el.value) : NaN;
  return Number.isFinite(n) ? n : 0;
}
function gVal(id) {
  const el = document.getElementById(id);
  return el ? (el.value ?? '') : '';
}
function setText(id, v) {
  const el = document.getElementById(id);
  if (el) el.textContent = v;
}

async function computeMainMenu() {
  const payload = {
    version: 'v24',
    scenario: {
      assumptions: {},
      scope: {},
      posts: [],
      vehicle: {},
      uniform: {},
      meta: {
        employeeTrueHourlyCost: gNum('ej_employeeCost'),
        weeklyHoursPerformed: gNum('ej_weeklyHours'),
        weeksInYear: gNum('ej_weeksInYear') || 52,
        monthsInYear: gNum('ej_monthsInYear') || 12,
      },
    },
  };

  const res = await fetch('{{ route('backend.main-menu.compute') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
      'Accept': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const data = await res.json();
  if (!res.ok || !data || !data.ok) {
    throw new Error((data && (data.message || data.error)) || 'Compute failed');
  }
  return data;
}

function renderMainMenu(data) {
  const tabs = data.tabs || {};

  const ej = tabs.economicJustification || {};
  setText('ej_r_ihHourly', fmt(ej.inHouseHourly || 0));
  setText('ej_r_vHourly', fmt(ej.vendorHourly || 0));
  setText('ej_r_ihWeekly', fmt(ej.inHouseWeekly || 0));
  setText('ej_r_vWeekly', fmt(ej.vendorWeekly || 0));
  setText('ej_r_ihMonthly', fmt(ej.inHouseMonthly || 0));
  setText('ej_r_vMonthly', fmt(ej.vendorMonthly || 0));
  setText('ej_r_ihAnnual', fmt(ej.inHouseAnnual || 0));
  setText('ej_r_vAnnual', fmt(ej.vendorAnnual || 0));
  setText('ej_r_savings', fmt(ej.costSavings || 0));
  setText('ej_r_roi', (ej.roiPct || 0).toFixed(1) + '%');
  setText('ej_r_payback', (ej.paybackMonths || 0).toFixed(1) + ' mo');
  setText('ej_r_dollar', fmt(ej.dollarForDollarReturn || 0));
}

let computeTimer = null;
function scheduleCompute() {
  clearTimeout(computeTimer);
  computeTimer = setTimeout(async () => {
    try {
      const data = await computeMainMenu();
      renderMainMenu(data);
    } catch (e) {
      // Keep UI stable; log for debugging
      console.error(e);
    }
  }, 150);
}

function hydrateSavedMainMenu() {
  const meta = savedScenario?.meta || {};

  const map = {
    ej_employeeCost: meta.employeeTrueHourlyCost,
    ej_weeklyHours: meta.weeklyHoursPerformed,
    ej_weeksInYear: meta.weeksInYear,
    ej_monthsInYear: meta.monthsInYear,
  };

  Object.entries(map).forEach(([id, value]) => {
    if (value === undefined || value === null) return;
    const el = document.getElementById(id);
    if (el) el.value = value;
  });
}

function downloadReport(){ window.print(); }
function emailReport(){ alert('Email functionality: connect to POST /api/spa/mail/calculator-pdf'); }

document.addEventListener('DOMContentLoaded', function () {
  hydrateSavedMainMenu();
  // Recompute on any input change on this page.
  document.querySelectorAll('input,select,textarea').forEach(el => {
    el.addEventListener('input', scheduleCompute);
    el.addEventListener('change', scheduleCompute);
  });
  scheduleCompute();
});
</script>
@endpush
