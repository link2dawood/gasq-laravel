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
      @if(auth()->user()?->isVendor() || auth()->user()?->isAdmin())
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('vendor-weighted-rates').open = true; document.getElementById('vendor-weighted-rates').scrollIntoView({behavior:'smooth'})">Vendor Weighted Rates</button>
      @endif
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary btn-sm" id="btnDownload" onclick="downloadReport()"><i class="fa fa-download me-1"></i> Download</button>
      <button class="btn btn-primary btn-sm" onclick="emailReport()"><i class="fa fa-envelope me-1"></i> Email Report</button>
    </div>
  </div>

  <details id="main-report-email" class="border rounded p-3 mb-4 d-print-none">
    <summary class="fw-semibold">Email main calculator report</summary>
    <x-report-actions report-type="main-menu" label="Email the saved main calculator report. Private vendor wage details are excluded." :password-protect="true" />
  </details>

  @php($budgetSummary = $budgetSummary ?? null)
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <p class="small text-gasq-muted mb-0">This dashboard and ROI use your saved Budget Summary inputs and cost-to-protect figures.</p>
    <a href="{{ route('budget-calculator.index') }}" class="btn btn-outline-primary btn-sm">Edit Budget Inputs</a>
  </div>
  {{-- Economic Justification --}}
  <div class="card gasq-card">
    <div class="card-body p-4">
      <div>

      {{-- ========== ECONOMIC JUSTIFICATION ========== --}}
      <div id="tab-justification">
        <div class="row g-4">
          <div class="col-lg-4">
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
                <label class="form-label fw-medium" for="ej_employeeCost">Workforce Baseline Assumption Labor Rate ({{ \App\Support\Currency::symbol() }}):</label>
                <input type="number" id="ej_employeeCost" class="form-control" value="{{ ($budgetSummary['baselineHourly'] ?? 0) * \App\Support\Currency::rate() }}" readonly>
                <div class="form-text">The shared labor rate is the same in both columns; the bill rates below include the Budget Summary cost-to-protect calculation.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Weekly Hours Performed</label>
                <input type="number" id="ej_weeklyHours" class="form-control" value="{{ $budgetSummary['totalWeeklyHours'] ?? 0 }}" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Weeks in Year</label>
                <input type="number" id="ej_weeksInYear" class="form-control" value="{{ $budgetSummary['weeksInYear'] ?? 52 }}" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Months in Year</label>
                <input type="number" id="ej_monthsInYear" class="form-control" value="12" readonly>
              </div>
            </div>
          </div>

          <div class="col-lg-8">
            <h5 class="fw-semibold mb-3">ROI Analysis</h5>

            <x-cost-to-protect-summary :summary="$budgetSummary ?? null" prefix="roi_summary_" :show-vendor="auth()->user()?->isVendor() || auth()->user()?->isAdmin()" />

          </div>
        </div>
      </div>

      </div><!-- /content -->
    </div><!-- /card-body -->
  </div><!-- /card -->

  @if(auth()->user()?->isVendor() || auth()->user()?->isAdmin())
    <div class="border rounded p-3 mt-4" id="main_vendor_comparison" hidden>
      <h2 class="h6 fw-semibold">Applied Vendor Comparison</h2>
      <div class="small text-gasq-muted mb-2" id="main_vendor_source"></div>
      <div class="row g-3">
        <div class="col-md-3"><div class="small">Weighted pay wage</div><strong id="main_vendor_pay"></strong></div>
        <div class="col-md-3"><div class="small">Weighted bill rate</div><strong id="main_vendor_bill"></strong></div>
        <div class="col-md-3"><div class="small">Annual billable service hours</div><strong id="main_vendor_service_hours"></strong></div>
        <div class="col-md-3"><div class="small">Project annual estimate</div><strong id="main_vendor_project"></strong></div>
      </div>
      <div class="small mt-2" id="main_vendor_baseline"></div>
    </div>
    @include('calculators.vendor-weighted-rates._module')
  @endif
</div>
</div>
@endsection

@push('scripts')
<script>
const budgetSummary = @json($budgetSummary);
const savedScenario = window.__gasqCalculatorState?.scenario || null;
let mainScenario = JSON.parse(JSON.stringify(savedScenario || {}));
const masterInputs = window.__gasqMasterInputs || {};
function fxRate() { return Number(window.GASQ_CURRENCY?.rate) || 1; }
function fmt(v, dec = 2, convert = true) {
  // Remove binary FX round-trip noise at half-cent boundaries for display only.
  const amount = Number((v * (convert ? fxRate() : 1)).toFixed(10));
  return new Intl.NumberFormat((window.GASQ_CURRENCY&&window.GASQ_CURRENCY.locale)||'en-US', { style: 'currency', currency:(window.GASQ_CURRENCY&&window.GASQ_CURRENCY.code)||'USD', minimumFractionDigits: dec, maximumFractionDigits: dec }).format(amount);
}
function showMoneyInput(el, usd) {
  el.value = String(Number((Number(usd) * fxRate()).toFixed(6)));
  el.dataset.usdValue = usd;
  el.dataset.displayValue = el.value;
}
function readMoneyInput(id) {
  const el = document.getElementById(id);
  // Recalculating or switching currency must not round the saved USD model.
  return el.value === el.dataset.displayValue ? Number(el.dataset.usdValue) : gNum(id) / fxRate();
}
function fmtN(v, dec = 0) {
  return new Intl.NumberFormat((window.GASQ_CURRENCY&&window.GASQ_CURRENCY.locale)||'en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);
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

function buildMainScenario() {
  const scenario = JSON.parse(JSON.stringify(mainScenario));
  scenario.meta = Object.assign({}, scenario.meta || {}, {
        companyName: gVal('ej_company'),
        preparedBy: gVal('ej_preparedBy'),
  });
  if (budgetSummary) {
    scenario.meta.roiCostBasis = 'budget';
  } else {
    delete scenario.meta.roiCostBasis;
    delete scenario.meta.budgetScenario;
  }
  // Saved comparison rates must not freeze the live 70/30 calculation.
  if (!scenario.meta.vendorWeightedRates?.applied) {
    delete scenario.meta.vendorTrueHourlyCost;
  }
  return scenario;
}

async function computeMainMenu() {
  const payload = {version:'v24',scenario:buildMainScenario()};

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
  ['inHouseHourly', 'vendorHourly', 'inHouseAnnual', 'vendorAnnual', 'recoveryPerHour', 'costSavings'].forEach(key => {
    setText('roi_summary_' + key, fmt(ej[key] || 0));
  });
  ['baselineHourly', 'Hourly', 'WeeklyHours', 'MonthlyHours', 'AnnualHours', 'StaffRequired', 'Weekly', 'Monthly', 'Annual'].forEach(key => {
    const shared = key === 'baselineHourly' || key.endsWith('Hours') || key === 'StaffRequired';
    const money = key === 'baselineHourly' || !shared;
    const internal = ej[shared ? (key === 'baselineHourly' ? key : 'total' + key) : 'inHouse' + key] || 0;
    const vendor = shared ? internal : (ej['vendor' + key] || 0);
    const hours = value => new Intl.NumberFormat(window.GASQ_CURRENCY?.locale || 'en-US', {maximumFractionDigits: 2}).format(value);
    setText('roi_summary_table_ih' + key, money ? fmt(internal) : hours(internal));
    setText('roi_summary_table_v' + key, money ? fmt(vendor) : hours(vendor));
  });
  setText('roi_summary_roiPct', fmtN(ej.roiPct || 0) + '%');
  setText('roi_summary_paybackMonths', (ej.paybackMonths || 0).toFixed(1) + ' months');
  setText('roi_summary_dollarForDollarReturn', fmt(ej.dollarForDollarReturn || 0, 2, false));
  showMoneyInput(document.getElementById('ej_employeeCost'), ej.baselineHourly || 0);
  document.getElementById('ej_weeklyHours').value = ej.totalWeeklyHours || 0;
  document.getElementById('ej_weeksInYear').value = ej.weeksInYear || 52;
  const comparison = tabs.vendorComparison;
  const panel = document.getElementById('main_vendor_comparison');
  if(panel) {
    panel.hidden = !comparison;
    if(comparison) {
      setText('main_vendor_pay', fmt(comparison.weightedPayWage)+'/hr');
      setText('main_vendor_bill', fmt(comparison.weightedBillRate)+'/hr');
      setText('main_vendor_service_hours', fmtN(comparison.annualBillableServiceHours,2));
      setText('main_vendor_project', fmt(comparison.projectAnnualEstimate));
      setText('main_vendor_source', comparison.rateSource+' · '+comparison.mode+' · '+comparison.wageSource);
      setText('main_vendor_baseline', 'Buyer baseline wage: '+(comparison.buyerBaselineWage == null ? 'not entered' : fmt(comparison.buyerBaselineWage)+'/hr')+'; in-house hourly cost: '+fmt(comparison.buyerBaselineHourlyCost)+'. Roster payroll hours: '+fmtN(comparison.annualPayrollHours,2)+'.');
    }
  }
}

let computeTimer = null, mainReportReady = false, mainComputeRevision = 0;
function setMainReportReady(ready, message = '') {
  mainReportReady = ready;
  const panel = document.getElementById('main-report-email');
  panel.querySelectorAll('.report-email-submit,.report-download-link').forEach(button => {
    button.disabled = !ready;
    button.classList.toggle('disabled',!ready);
  });
  const warning = panel.querySelector('.report-stale-warning');
  warning.textContent = message;
  warning.classList.toggle('d-none',!message);
}
document.addEventListener('submit', event => {
  if(event.target.closest('#main-report-email') && !mainReportReady) {
    event.preventDefault(); event.stopImmediatePropagation();
  }
},true);
function calcEJ() { scheduleCompute(); }
function scheduleCompute() {
  if (!budgetSummary) return;
  clearTimeout(computeTimer);
  const submittedRevision = ++mainComputeRevision;
  setMainReportReady(false,'Saving current calculator inputs before sending…');
  computeTimer = setTimeout(async () => {
    try {
      const data = await computeMainMenu();
      if(submittedRevision !== mainComputeRevision) return;
      renderMainMenu(data);
      setMainReportReady(true);
    } catch (e) {
      if(submittedRevision === mainComputeRevision) setMainReportReady(false,'Unable to save current inputs. '+e.message);
    }
  }, 150);
}

function hydrateSavedMainMenu() {
  const meta = savedScenario?.meta || {};

  const map = {
    ej_company: meta.companyName,
    ej_preparedBy: meta.preparedBy,
  };

  Object.entries(map).forEach(([id, value]) => {
    if (value === undefined || value === null) return;
    const el = document.getElementById(id);
    if (el) el.value = value;
  });
  showMoneyInput(document.getElementById('ej_employeeCost'), budgetSummary?.baselineHourly ?? 0);
}

function downloadReport(){
  if(window.VendorWeightedRates) window.VendorWeightedRates.requestReport();
  else window.location.assign('{{ route('reports.download', ['type' => 'main-menu']) }}');
}
function emailReport(){
  if(window.VendorWeightedRates) { window.VendorWeightedRates.requestEmailReport(); return; }
  const panel = document.getElementById('main-report-email');
  panel.open = true;
  panel.scrollIntoView({block:'center',behavior:'smooth'});
  panel.querySelector('[name=email]').focus({preventScroll:true});
}

document.addEventListener('DOMContentLoaded', function () {
  hydrateSavedMainMenu();
  // Recompute on any input change on this page.
  document.querySelectorAll('#tab-justification input,#tab-justification select,#tab-justification textarea').forEach(el => {
    el.addEventListener('input', scheduleCompute);
    el.addEventListener('change', scheduleCompute);
    el.addEventListener('input', () => window.VendorWeightedRates?.refreshContext());
  });
  setMainReportReady(false, budgetSummary ? 'Saving current budget summary…' : 'Save your Budget Calculator inputs first.');
  scheduleCompute();
});
</script>
@endpush
