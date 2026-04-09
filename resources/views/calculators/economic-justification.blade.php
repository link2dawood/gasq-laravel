@extends('layouts.app')
@section('title', 'Economic Justification of Security Services')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .ej-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 30%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .ej-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .ej-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .ej-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .ej-section + .ej-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .ej-stat {
    border: 1px solid rgba(6, 45, 121, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .ej-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .ej-stat-value {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--gasq-primary);
  }
  .ej-panel {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .ej-panel-muted {
    background: rgba(6, 45, 121, 0.04);
  }
  .ej-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(6, 45, 121, 0.08);
    color: var(--gasq-primary);
    font-size: 0.78rem;
    font-weight: 600;
  }
  .ej-mono {
    font-variant-numeric: tabular-nums;
  }
  @media (max-width: 1199.98px) {
    .ej-sticky {
      position: static;
    }
  }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-chart-line text-primary"></i> Economic Justification of Security Services
        </h1>
        <div class="text-gasq-muted small">Shared input rail with live ROI, savings, and cost-comparison outputs</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="downloadPDF()"><i class="fa fa-download me-1"></i> Download PDF</button>
      <button class="btn btn-primary btn-sm" onclick="emailReport()"><i class="fa fa-envelope me-1"></i> Email Report</button>
    </div>
  </div>

  <div class="card gasq-card ej-shell overflow-hidden">
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-xl-4 border-end ej-sidebar">
          <div class="p-3 p-md-4 ej-sticky">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
              <div>
                <div class="ej-kicker mb-2">Shared Inputs</div>
                <h2 class="h4 fw-bold mb-2">Economic Model Inputs</h2>
                <p class="small text-gasq-muted mb-0">All sections on the right use these same inputs, so company details and cost assumptions stay synchronized everywhere.</p>
              </div>
              <span class="ej-chip"><i class="fa fa-bolt"></i> Live</span>
            </div>

            <div class="ej-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-address-card text-primary"></i>
                <h5 class="mb-0 fw-semibold">Report Details</h5>
              </div>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-medium">Company Name</label>
                  <input type="text" id="ej_company" class="form-control form-control-sm" value="ABC COMPANY" oninput="scheduleEJ()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Prepared By</label>
                  <input type="text" id="ej_preparedBy" class="form-control form-control-sm" placeholder="Your name" oninput="scheduleEJ()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Prepared For</label>
                  <input type="text" id="ej_preparedFor" class="form-control form-control-sm" placeholder="Client name" oninput="scheduleEJ()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Date</label>
                  <input type="date" id="ej_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" oninput="scheduleEJ()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Client Email</label>
                  <input type="email" id="ej_email" class="form-control form-control-sm" placeholder="client@example.com" oninput="scheduleEJ()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Phone</label>
                  <input type="tel" id="ej_phone" class="form-control form-control-sm" placeholder="(555) 123-4567" oninput="scheduleEJ()">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Address</label>
                  <input type="text" id="ej_address" class="form-control form-control-sm" placeholder="123 Main St, City, State" oninput="scheduleEJ()">
                </div>
              </div>
            </div>

            <div class="ej-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-sliders text-primary"></i>
                <h5 class="mb-0 fw-semibold">Cost Assumptions</h5>
              </div>
              <div class="d-flex flex-column gap-3">
                <div>
                  <label class="form-label fw-medium">Employee True Hourly Cost ($/hr)</label>
                  <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" id="ej_empCost" class="form-control" value="133.00" step="0.01" oninput="scheduleEJ()">
                  </div>
                  <div class="form-text">Includes wages, payroll taxes, benefits, HR overhead.</div>
                </div>
                <div>
                  <label class="form-label fw-medium">Weekly Hours Performed In-House</label>
                  <input type="number" id="ej_weeklyHours" class="form-control" value="168" oninput="scheduleEJ()">
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Weeks per Year</label>
                    <input type="number" id="ej_weeksInYear" class="form-control form-control-sm" value="52" oninput="scheduleEJ()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Months per Year</label>
                    <input type="number" id="ej_monthsInYear" class="form-control form-control-sm" value="12" oninput="scheduleEJ()">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="p-3 p-md-4">
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="ej-stat">
                  <div class="ej-stat-label mb-2">Annual Savings</div>
                  <div class="ej-stat-value ej-mono" id="r_savings_top">$0.00</div>
                  <div class="small text-gasq-muted">In-house cost minus vendor cost</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="ej-stat">
                  <div class="ej-stat-label mb-2">ROI Percentage</div>
                  <div class="ej-stat-value ej-mono" id="r_roiPct_top">0.0%</div>
                  <div class="small text-gasq-muted">Live profitability recovery view</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="ej-stat">
                  <div class="ej-stat-label mb-2">Payback Period</div>
                  <div class="ej-stat-value ej-mono" id="r_payback_top">0.0 mo</div>
                  <div class="small text-gasq-muted">Updated from the same left-side inputs</div>
                </div>
              </div>
            </div>

            <div class="alert alert-light border gasq-border small d-print-none mb-3" id="ej_error" style="display:none"></div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <div class="ej-kicker mb-1">Results Workspace</div>
                <h3 class="h5 fw-bold mb-0">Economic Justification Outputs</h3>
              </div>
              <div class="small text-gasq-muted">Every section on the right stays synchronized with the shared input rail on the left.</div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-lg-6">
                <div class="ej-panel p-3 h-100" style="border-top:4px solid #ef4444">
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div>
                      <h5 class="mb-0 fw-semibold text-danger">Lost Revenue — Nonproductivity &amp; Staff Costs</h5>
                      <div class="small text-gasq-muted">In-house security cost</div>
                    </div>
                    <i class="fa fa-building text-danger"></i>
                  </div>
                  <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Weekly cost</span><span class="fw-medium ej-mono" id="r_ihWeekly">$0.00</span></div>
                  <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Monthly cost</span><span class="fw-medium ej-mono" id="r_ihMonthly">$0.00</span></div>
                  <div class="d-flex justify-content-between fw-semibold"><span>Total in-house annual cost</span><span class="text-danger ej-mono" id="r_ihAnnual">$0.00</span></div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="ej-panel ej-panel-muted p-3 h-100" style="border-top:4px solid #22c55e">
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div>
                      <h5 class="mb-0 fw-semibold text-success">Vendor Discounted Delivery Service Costs</h5>
                      <div class="small text-gasq-muted">Outsourced security provider</div>
                    </div>
                    <i class="fa fa-shield text-success"></i>
                  </div>
                  <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Vendor hourly rate</span><span class="fw-medium ej-mono" id="r_vHourly">$0.00/hr</span></div>
                  <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Recovery / replacement factor</span><span class="fw-medium ej-mono" id="r_vRecovery">$0.00/hr</span></div>
                  <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Weekly hours outsourced</span><span class="fw-medium ej-mono" id="r_vWeeklyHrs">0</span></div>
                  <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Weekly cost</span><span class="fw-medium ej-mono" id="r_vWeekly">$0.00</span></div>
                  <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Monthly cost</span><span class="fw-medium ej-mono" id="r_vMonthly">$0.00</span></div>
                  <div class="d-flex justify-content-between fw-semibold"><span>Total vendor annual cost</span><span class="text-success ej-mono" id="r_vAnnual">$0.00</span></div>
                </div>
              </div>
            </div>

            <div class="ej-panel p-3 mb-4" style="border-top:4px solid var(--gasq-primary)">
              <h5 class="fw-semibold mb-3 d-flex align-items-center gap-2"><i class="fa fa-chart-line text-primary"></i> Customer Return on Investment Savings</h5>
              <div class="row g-3">
                <div class="col-md-3">
                  <div class="rounded p-3 text-center" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3)">
                    <div class="small text-gasq-muted mb-1">Cost Savings (&lt; 12 Months)</div>
                    <div class="fs-5 fw-bold text-success ej-mono" id="r_savings">$0.00</div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="rounded p-3 text-center" style="background:rgba(6,45,121,0.08);border:1px solid rgba(6,45,121,0.2)">
                    <div class="small text-gasq-muted mb-1">ROI % of Profits Saved</div>
                    <div class="fs-5 fw-bold text-primary ej-mono" id="r_roiPct">0.0%</div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                    <div class="small text-gasq-muted mb-1">Payback Period</div>
                    <div class="fs-5 fw-bold ej-mono" id="r_payback">0.0 mo</div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                    <div class="small text-gasq-muted mb-1">Dollar for Dollar Return</div>
                    <div class="fs-5 fw-bold ej-mono" id="r_dollar">$0.00</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="ej-panel p-3">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="fw-semibold mb-0">Projected Hours &amp; Cost Analysis</h5>
                <div class="small text-gasq-muted">Live company: <span id="r_company">ABC COMPANY</span></div>
              </div>
              <div class="table-responsive rounded" style="background:var(--gasq-muted-bg)">
                <table class="table table-sm align-middle mb-0">
                  <thead><tr><th>Metric</th><th class="text-center">In-House</th><th class="text-center">Vendor</th><th class="text-center">Variance</th></tr></thead>
                  <tbody>
                    <tr><td class="small text-gasq-muted">Projected Annual Hours</td><td class="text-center ej-mono" id="p_ihHrs">0</td><td class="text-center ej-mono" id="p_vHrs">0</td><td class="text-center">—</td></tr>
                    <tr><td class="small text-gasq-muted">Projected Annual Cost</td><td class="text-center ej-mono" id="p_ihCost">$0.00</td><td class="text-center ej-mono" id="p_vCost">$0.00</td><td class="text-center fw-semibold ej-mono" id="p_variance">$0.00</td></tr>
                    <tr><td class="small text-gasq-muted">Per-Employee Financial Contribution</td><td class="text-center ej-mono" id="p_ihPerEmp">$0.00</td><td class="text-center ej-mono" id="p_vPerEmp">$0.00</td><td class="text-center">—</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="d-none d-print-block text-center mt-4">
              <h2 class="fw-bold">ECONOMIC JUSTIFICATION OF SECURITY SERVICES</h2>
              <h4>COST COMPARISON RETURN ON INVESTMENT ANALYSIS</h4>
              <h4>FOR <span id="print_company">ABC COMPANY</span></h4>
              <p class="text-danger fw-bold fs-5">CONFIDENTIAL</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <x-report-actions reportType="economic-justification" />

</div>
</div>
@endsection

@push('scripts')
<script>
function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v || 0);}
function fmtN(v,dec=0){return new Intl.NumberFormat('en-US',{minimumFractionDigits:dec,maximumFractionDigits:dec}).format(v || 0);}
function g(id){return parseFloat(document.getElementById(id)?.value)||0;}
function t(id){return (document.getElementById(id)?.value || '').trim();}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

let ejTimer = null;
let ejInflight = null;
function ejSetError(msg){
  const el = document.getElementById('ej_error');
  if(!el) return;
  if(!msg){ el.style.display='none'; el.textContent=''; return; }
  el.style.display='';
  el.textContent = msg;
}

function scheduleEJ(){
  clearTimeout(ejTimer);
  ejTimer = setTimeout(calcEJ, 300);
}

async function calcEJ(){
  const empCost = g('ej_empCost');
  const weeklyHours = g('ej_weeklyHours');
  const weeksInYear = g('ej_weeksInYear');
  const monthsInYear = g('ej_monthsInYear');
  const companyName = t('ej_company') || 'ABC COMPANY';

  const payload = { version:'v24', scenario:{ meta:{ employeeTrueHourlyCost: empCost, weeklyHours: weeklyHours, weeksInYear: weeksInYear, monthsInYear: monthsInYear } } };
  try{
    ejSetError('');
    if(ejInflight){ ejInflight.abort(); }
    ejInflight = new AbortController();
    const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'economic-justification']) }}', {
      method:'POST',
      signal: ejInflight.signal,
      headers:{
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept':'application/json'
      },
      body: JSON.stringify(payload)
    });
    let data = null;
    try { data = await res.json(); } catch { data = null; }
    if(!res.ok || !data || !data.ok){
      if (data && data.error === 'insufficient_credits') {
        ejSetError(data.message || 'Not enough credits to run this calculator.');
      } else {
        ejSetError('Unable to calculate right now. Please try again.');
      }
      console.error(data);
      return;
    }

    const out = data.kpis||{};

    const vendorHourly = out.vendorHourly||0;
    const vendorRecovery = vendorHourly * 0.70;
    const ihAnnual = out.inHouseAnnual||0;
    const vAnnual = out.vendorAnnual||0;
    const savings = out.savings||0;
    const roiPct = out.roiPct||0;
    const payback = out.paybackMonths||0;
    const dollar = out.dollarForDollar||0;

    const projHrs = weeklyHours * weeksInYear;
    const staffRequired = Math.ceil(weeklyHours / 28);

    setText('r_ihWeekly', fmt(out.inHouseWeekly||0));
    setText('r_ihMonthly', fmt(out.inHouseMonthly||0));
    setText('r_ihAnnual', fmt(ihAnnual));
    setText('r_vHourly', fmt(vendorHourly)+'/hr');
    setText('r_vRecovery', fmt(vendorRecovery)+'/hr');
    setText('r_vWeeklyHrs', fmtN(weeklyHours));
    setText('r_vWeekly', fmt(out.vendorWeekly||0));
    setText('r_vMonthly', fmt(out.vendorMonthly||0));
    setText('r_vAnnual', fmt(vAnnual));
    setText('r_savings', fmt(savings));
    setText('r_savings_top', fmt(savings));

    const roiText = roiPct.toFixed(1)+'%';
    const rosiEl = document.getElementById('r_roiPct');
    const roiTopEl = document.getElementById('r_roiPct_top');
    if(rosiEl){
      rosiEl.textContent = roiText;
      rosiEl.className = 'fs-5 fw-bold ej-mono ' + (roiPct>=0?'text-primary':'text-danger');
    }
    if(roiTopEl){
      roiTopEl.textContent = roiText;
      roiTopEl.className = 'ej-stat-value ej-mono ' + (roiPct>=0?'text-primary':'text-danger');
    }

    setText('r_payback', payback.toFixed(1)+' mo');
    setText('r_payback_top', payback.toFixed(1)+' mo');
    setText('r_dollar', fmt(dollar));
    setText('p_ihHrs', fmtN(projHrs));
    setText('p_vHrs', fmtN(projHrs));
    setText('p_ihCost', fmt(ihAnnual));
    setText('p_vCost', fmt(vAnnual));
    const varEl = document.getElementById('p_variance');
    if(varEl){
      varEl.textContent = fmt(savings);
      varEl.className = 'text-center fw-semibold ej-mono ' + (savings>=0?'text-success':'text-danger');
    }
    setText('p_ihPerEmp', fmt(staffRequired>0?ihAnnual/staffRequired:0));
    setText('p_vPerEmp', fmt(staffRequired>0?vAnnual/staffRequired:0));
    setText('r_company', companyName);
    const printComp = document.getElementById('print_company');
    if(printComp) printComp.textContent = companyName;
  }catch(e){
    if(e?.name === 'AbortError') return;
    console.error(e);
    ejSetError('Unable to calculate right now. Please try again.');
  }
}

function downloadPDF(){ window.print(); }
function emailReport(){
  const email = t('ej_email');
  if(!email){ alert('Please enter a client email address.'); return; }
  alert('Report would be emailed to: ' + email + '\n\nConnect to POST /api/spa/mail/calculator-pdf');
}

document.addEventListener('DOMContentLoaded', () => {
  calcEJ();
});
</script>
@endpush
