@extends('layouts.app')
@section('title', 'Economic Justification of Security Services')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-chart-line text-primary"></i> Economic Justification of Security Services
        </h1>
        <div class="text-gasq-muted small">Cost Comparison · Return on Investment Analysis</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="downloadPDF()"><i class="fa fa-download me-1"></i> Download PDF</button>
      <button class="btn btn-primary btn-sm" onclick="emailReport()"><i class="fa fa-envelope me-1"></i> Email Report</button>
    </div>
  </div>

  {{-- Header meta --}}
  <div class="card gasq-card mb-4 d-print-none">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><label class="form-label small fw-medium">Company Name</label><input type="text" id="ej_company" class="form-control form-control-sm" value="ABC COMPANY" oninput="calcEJ()"></div>
        <div class="col-md-3"><label class="form-label small fw-medium">Prepared By</label><input type="text" id="ej_preparedBy" class="form-control form-control-sm" placeholder="Your name"></div>
        <div class="col-md-3"><label class="form-label small fw-medium">Prepared For</label><input type="text" id="ej_preparedFor" class="form-control form-control-sm" placeholder="Client name"></div>
        <div class="col-md-3"><label class="form-label small fw-medium">Date</label><input type="date" id="ej_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}"></div>
        <div class="col-md-4"><label class="form-label small fw-medium">Client Email</label><input type="email" id="ej_email" class="form-control form-control-sm" placeholder="client@example.com"></div>
        <div class="col-md-4"><label class="form-label small fw-medium">Phone</label><input type="tel" id="ej_phone" class="form-control form-control-sm" placeholder="(555) 123-4567"></div>
        <div class="col-md-4"><label class="form-label small fw-medium">Address</label><input type="text" id="ej_address" class="form-control form-control-sm" placeholder="123 Main St, City, State"></div>
      </div>
    </div>
  </div>

  {{-- Print header (visible only when printing) --}}
  <div class="d-none d-print-block text-center mb-4">
    <h2 class="fw-bold">ECONOMIC JUSTIFICATION OF SECURITY SERVICES</h2>
    <h4>COST COMPARISON RETURN ON INVESTMENT ANALYSIS</h4>
    <h4>FOR <span id="print_company">ABC COMPANY</span></h4>
    <p class="text-danger fw-bold fs-5">CONFIDENTIAL</p>
  </div>

  {{-- Main calculation section --}}
  <div class="row g-4 mb-4">
    {{-- In-House Costs --}}
    <div class="col-lg-6">
      <div class="card gasq-card h-100" style="border-top:4px solid #ef4444">
        <div class="card-header py-3 bg-danger bg-opacity-10">
          <h5 class="card-title mb-0 fw-semibold text-danger">
            <i class="fa fa-building me-1"></i> Lost Revenue — Nonproductivity &amp; Staff Costs
          </h5>
          <div class="small text-gasq-muted">In-House Security Cost</div>
        </div>
        <div class="card-body d-flex flex-column gap-3">
          <div>
            <label class="form-label fw-medium">Employee True Hourly Cost ($/hr)</label>
            <div class="input-group"><span class="input-group-text">$</span><input type="number" id="ej_empCost" class="form-control" value="133.00" step="0.01" oninput="calcEJ()"></div>
            <div class="form-text">Includes wages, payroll taxes, benefits, HR overhead</div>
          </div>
          <div>
            <label class="form-label fw-medium">Weekly Hours Performed In-House</label>
            <input type="number" id="ej_weeklyHours" class="form-control" value="168" oninput="calcEJ()">
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small fw-medium">Weeks per Year</label>
              <input type="number" id="ej_weeksInYear" class="form-control form-control-sm" value="52" oninput="calcEJ()">
            </div>
            <div class="col-6">
              <label class="form-label small fw-medium">Months per Year</label>
              <input type="number" id="ej_monthsInYear" class="form-control form-control-sm" value="12" oninput="calcEJ()">
            </div>
          </div>
          <hr class="my-1">
          <div class="rounded p-3" style="background:rgba(239,68,68,0.08)">
            <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Inhouse estimated weekly cost</span><span class="fw-medium" id="r_ihWeekly">$0.00</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Inhouse estimated monthly cost</span><span class="fw-medium" id="r_ihMonthly">$0.00</span></div>
            <div class="d-flex justify-content-between fw-semibold"><span>Inhouse total cost</span><span class="text-danger" id="r_ihAnnual">$0.00</span></div>
          </div>
        </div>
      </div>
    </div>

    {{-- Vendor Costs --}}
    <div class="col-lg-6">
      <div class="card gasq-card h-100" style="border-top:4px solid #22c55e">
        <div class="card-header py-3 bg-success bg-opacity-10">
          <h5 class="card-title mb-0 fw-semibold text-success">
            <i class="fa fa-shield me-1"></i> Vendor Discounted Delivery Service Costs
          </h5>
          <div class="small text-gasq-muted">Outsourced Security Provider</div>
        </div>
        <div class="card-body d-flex flex-column gap-3">
          <div class="rounded p-3" style="background:rgba(34,197,94,0.08)">
            <div class="small text-gasq-muted mb-2"><i class="fa fa-info-circle me-1"></i> Vendor cost automatically calculated as 70% of in-house true cost</div>
            <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Vendor hourly rate</span><span class="fw-medium" id="r_vHourly">$0.00/hr</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Recruitment, replacement &amp; recovery</span><span class="fw-medium" id="r_vRecovery">$0.00/hr</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Weekly hours outsourced</span><span class="fw-medium" id="r_vWeeklyHrs">0</span></div>
          </div>
          <div class="rounded p-3" style="background:rgba(34,197,94,0.08)">
            <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Estimated total weekly cost</span><span class="fw-medium" id="r_vWeekly">$0.00</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Estimated monthly cost</span><span class="fw-medium" id="r_vMonthly">$0.00</span></div>
            <div class="d-flex justify-content-between fw-semibold"><span>Total staffing outsourcing cost</span><span class="text-success" id="r_vAnnual">$0.00</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ROI Results --}}
  <div class="card gasq-card mb-4" style="border-top:4px solid var(--gasq-primary)">
    <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2"><i class="fa fa-chart-line text-primary"></i> Customer Return on Investment Savings</h5></div>
    <div class="card-body">
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="rounded p-3 text-center" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3)">
            <div class="small text-gasq-muted mb-1">Cost Savings (&lt; 12 Months)</div>
            <div class="fs-5 fw-bold text-success" id="r_savings">$0.00</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="rounded p-3 text-center" style="background:rgba(6,45,121,0.08);border:1px solid rgba(6,45,121,0.2)">
            <div class="small text-gasq-muted mb-1">ROI % of Profits Saved</div>
            <div class="fs-5 fw-bold text-primary" id="r_roiPct">0.0%</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
            <div class="small text-gasq-muted mb-1">Payback Period</div>
            <div class="fs-5 fw-bold" id="r_payback">0.0 mo</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
            <div class="small text-gasq-muted mb-1">Dollar for Dollar Return</div>
            <div class="fs-5 fw-bold" id="r_dollar">$0.00</div>
          </div>
        </div>
      </div>

      {{-- Projected analysis --}}
      <h6 class="fw-semibold mb-3">Projected Hours &amp; Cost Analysis</h6>
      <div class="table-responsive rounded" style="background:var(--gasq-muted-bg)">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Metric</th><th class="text-center">In-House</th><th class="text-center">Vendor</th><th class="text-center">Variance</th></tr></thead>
          <tbody>
            <tr><td class="small text-gasq-muted">Projected Annual Hours</td><td class="text-center" id="p_ihHrs">0</td><td class="text-center" id="p_vHrs">0</td><td class="text-center">—</td></tr>
            <tr><td class="small text-gasq-muted">Projected Annual Cost</td><td class="text-center" id="p_ihCost">$0.00</td><td class="text-center" id="p_vCost">$0.00</td><td class="text-center text-success fw-semibold" id="p_variance">$0.00</td></tr>
            <tr><td class="small text-gasq-muted">Per-Employee Financial Contribution</td><td class="text-center" id="p_ihPerEmp">$0.00</td><td class="text-center" id="p_vPerEmp">$0.00</td><td class="text-center">—</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="text-center text-gasq-muted small d-print-block">
    <p>Proprietary Confidential Report — <span id="r_company">ABC COMPANY</span> | Prepared: {{ now()->format('M d, Y') }}</p>
  </div>

</div>
</div>
@endsection

@push('scripts')
<script>
function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v);}
function fmtN(v,dec=0){return new Intl.NumberFormat('en-US',{minimumFractionDigits:dec,maximumFractionDigits:dec}).format(v);}
function g(id){return parseFloat(document.getElementById(id).value)||0;}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

async function calcEJ(){
  const empCost = g('ej_empCost');
  const weeklyHours = g('ej_weeklyHours');
  const weeksInYear = g('ej_weeksInYear');
  const monthsInYear = g('ej_monthsInYear');
  const companyName = document.getElementById('ej_company').value || 'ABC COMPANY';

  const payload = { version:'v24', scenario:{ meta:{ employeeTrueHourlyCost: empCost, weeklyHours: weeklyHours, weeksInYear: weeksInYear, monthsInYear: monthsInYear } } };
  const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'economic-justification']) }}', {
    method:'POST',
    headers:{
      'Content-Type':'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
      'Accept':'application/json'
    },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if(!res.ok || !data || !data.ok){ console.error(data); return; }
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

  setText('r_ihWeekly', fmt(out.inHouseWeekly||0)); setText('r_ihMonthly', fmt(out.inHouseMonthly||0)); setText('r_ihAnnual', fmt(ihAnnual));
  setText('r_vHourly', fmt(vendorHourly)+'/hr'); setText('r_vRecovery', fmt(vendorRecovery)+'/hr');
  setText('r_vWeeklyHrs', fmtN(weeklyHours));
  setText('r_vWeekly', fmt(out.vendorWeekly||0)); setText('r_vMonthly', fmt(out.vendorMonthly||0)); setText('r_vAnnual', fmt(vAnnual));
  setText('r_savings', fmt(savings));
  const rosiEl = document.getElementById('r_roiPct');
  rosiEl.textContent = roiPct.toFixed(1)+'%';
  rosiEl.className = 'fs-5 fw-bold '+(roiPct>=0?'text-primary':'text-danger');
  setText('r_payback', payback.toFixed(1)+' mo');
  setText('r_dollar', fmt(dollar));
  setText('p_ihHrs', fmtN(projHrs)); setText('p_vHrs', fmtN(projHrs));
  setText('p_ihCost', fmt(ihAnnual)); setText('p_vCost', fmt(vAnnual));
  const varEl = document.getElementById('p_variance');
  varEl.textContent = fmt(savings);
  varEl.className = 'text-center fw-semibold '+(savings>=0?'text-success':'text-danger');
  setText('p_ihPerEmp', fmt(staffRequired>0?ihAnnual/staffRequired:0));
  setText('p_vPerEmp', fmt(staffRequired>0?vAnnual/staffRequired:0));
  setText('r_company', companyName);
  const printComp = document.getElementById('print_company');
  if(printComp) printComp.textContent = companyName;
}

function downloadPDF(){ window.print(); }
function emailReport(){
  const email = document.getElementById('ej_email').value;
  if(!email){ alert('Please enter a client email address.'); return; }
  alert('Report would be emailed to: ' + email + '\n\nConnect to POST /api/spa/mail/calculator-pdf');
}

document.addEventListener('DOMContentLoaded', calcEJ);
</script>
@endpush
