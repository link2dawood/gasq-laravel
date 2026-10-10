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
                <label class="form-label fw-medium">Employee True Hourly Cost ({{ \App\Support\Currency::symbol() }}/hr)</label>
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
                  <tr><td class="small text-gasq-muted">Hourly Cost</td><td class="text-center small fw-medium" id="ej_r_ihHourly">{{ \App\Support\Currency::format(0) }}</td><td class="text-center small fw-medium" id="ej_r_vHourly">{{ \App\Support\Currency::format(0) }}</td></tr>
                  <tr><td class="small text-gasq-muted">Weekly Cost</td><td class="text-center small fw-medium" id="ej_r_ihWeekly">{{ \App\Support\Currency::format(0) }}</td><td class="text-center small fw-medium" id="ej_r_vWeekly">{{ \App\Support\Currency::format(0) }}</td></tr>
                  <tr><td class="small text-gasq-muted">Monthly Cost</td><td class="text-center small fw-medium" id="ej_r_ihMonthly">{{ \App\Support\Currency::format(0) }}</td><td class="text-center small fw-medium" id="ej_r_vMonthly">{{ \App\Support\Currency::format(0) }}</td></tr>
                  <tr class="table-light fw-semibold"><td class="small">Annual Cost</td><td class="text-center small" id="ej_r_ihAnnual">{{ \App\Support\Currency::format(0) }}</td><td class="text-center small" id="ej_r_vAnnual">{{ \App\Support\Currency::format(0) }}</td></tr>
                </tbody>
              </table>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-6">
                <div class="rounded p-3 text-center" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3)">
                  <div class="small text-gasq-muted mb-1">Cost Savings</div>
                  <div class="fs-5 fw-bold text-success" id="ej_r_savings">{{ \App\Support\Currency::format(0) }}</div>
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
                  <div class="fs-5 fw-bold" id="ej_r_dollar">{{ \App\Support\Currency::format(0) }}</div>
                </div>
              </div>
            </div>
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
const savedScenario = window.__gasqCalculatorState?.scenario || null;
let mainScenario = JSON.parse(JSON.stringify(savedScenario || {}));
const masterInputs = window.__gasqMasterInputs || {};
function fmt(v, dec = 2) {
  return new Intl.NumberFormat((window.GASQ_CURRENCY&&window.GASQ_CURRENCY.locale)||'en-US', { style: 'currency', currency:(window.GASQ_CURRENCY&&window.GASQ_CURRENCY.code)||'USD', minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);
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
        employeeTrueHourlyCost: gNum('ej_employeeCost'),
        weeklyHoursPerformed: gNum('ej_weeklyHours'),
        weeksInYear: gNum('ej_weeksInYear') || 52,
        monthsInYear: gNum('ej_monthsInYear') || 12,
  });
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
  scheduleCompute();
});
</script>
@endpush
