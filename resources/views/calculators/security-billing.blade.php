@extends('layouts.app')
@section('title', 'Security Billing Calculator')
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
          <i class="fa fa-file-invoice-dollar text-primary"></i> Security Billing Calculator
        </h1>
        <div class="text-gasq-muted small">Detailed security billing rate analysis</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="resetAll()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-download me-1"></i> Download PDF</button>
    </div>
  </div>

  {{-- Tabs --}}
  <ul class="nav nav-tabs mb-0 d-print-none border-bottom-0" role="tablist">
    <li class="nav-item"><a class="nav-link active fw-medium" data-bs-toggle="tab" href="#sb-calculator"><i class="fa fa-calculator me-1"></i> Calculator</a></li>
    <li class="nav-item"><a class="nav-link fw-medium" data-bs-toggle="tab" href="#sb-comparison"><i class="fa fa-code-compare me-1"></i> Side-by-Side Comparison</a></li>
    <li class="nav-item"><a class="nav-link fw-medium" data-bs-toggle="tab" href="#sb-profile"><i class="fa fa-user me-1"></i> Profile &amp; Rates</a></li>
  </ul>

  <div class="tab-content card gasq-card" style="border-top-left-radius:0;border-top-right-radius:0">
    <div class="card-body p-4">

      {{-- ===== CALCULATOR TAB ===== --}}
      <div class="tab-pane fade show active" id="sb-calculator">

        {{-- Contact Info --}}
        <div class="card gasq-card mb-4">
          <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2"><i class="fa fa-user text-primary"></i> Contact Information</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3"><label class="form-label small fw-medium">Customer Name</label><input type="text" id="sb_custName" class="form-control form-control-sm" placeholder="John Doe"></div>
              <div class="col-md-3"><label class="form-label small fw-medium">Company Name</label><input type="text" id="sb_compName" class="form-control form-control-sm" placeholder="ABC Security"></div>
              <div class="col-md-3"><label class="form-label small fw-medium">Email</label><input type="email" id="sb_email" class="form-control form-control-sm" placeholder="john@example.com"></div>
              <div class="col-md-3"><label class="form-label small fw-medium">Phone</label><input type="tel" id="sb_phone" class="form-control form-control-sm" placeholder="(555) 123-4567"></div>
            </div>
          </div>
        </div>

        <div class="row g-4">

          {{-- Inputs --}}
          <div class="col-lg-6">
            <div class="card gasq-card h-100">
              <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Billing Parameters</h5></div>
              <div class="card-body d-flex flex-column gap-3">

                <div>
                  <label class="form-label fw-medium">Base Pay Rate ($/hr)</label>
                  <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" id="sb_basePay" class="form-control" value="18.00" step="0.01" oninput="calcSB()">
                  </div>
                </div>

                <div>
                  <label class="form-label fw-medium">Hours per Week</label>
                  <input type="number" id="sb_hours" class="form-control" value="40" step="0.5" oninput="calcSB()">
                </div>

                <div>
                  <label class="form-label fw-medium">Weeks per Year</label>
                  <input type="number" id="sb_weeks" class="form-control" value="52" oninput="calcSB()">
                </div>

                <hr class="my-1">
                <h6 class="fw-semibold">Cost Components (%)</h6>

                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label small fw-medium">FICA &amp; Medicare (%)</label>
                    <input type="number" id="sb_fica" class="form-control form-control-sm" value="7.65" step="0.01" oninput="calcSB()">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-medium">FUTA (%)</label>
                    <input type="number" id="sb_futa" class="form-control form-control-sm" value="0.8" step="0.01" oninput="calcSB()">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-medium">SUTA (%)</label>
                    <input type="number" id="sb_suta" class="form-control form-control-sm" value="5.76" step="0.01" oninput="calcSB()">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-medium">Overhead (%)</label>
                    <input type="number" id="sb_overhead" class="form-control form-control-sm" value="35" step="0.1" oninput="calcSB()">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-medium">Profit Margin (%)</label>
                    <input type="number" id="sb_profitPct" class="form-control form-control-sm" value="15" step="0.1" oninput="calcSB()">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-medium">Uniform Cost (per uniform $)</label>
                    <input type="number" id="sb_uniformCost" class="form-control form-control-sm" value="75" step="0.01" oninput="calcSB()">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-medium">Uniforms per Employee</label>
                    <input type="number" id="sb_uniformQty" class="form-control form-control-sm" value="2" oninput="calcSB()">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-medium">Training Cost per Hire ($)</label>
                    <input type="number" id="sb_trainingCost" class="form-control form-control-sm" value="500" oninput="calcSB()">
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Results --}}
          <div class="col-lg-6">
            <div class="card gasq-card h-100">
              <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Billing Results</h5></div>
              <div class="card-body d-flex flex-column gap-3">

                {{-- Key metrics --}}
                <div class="rounded p-3" style="background:var(--gasq-muted-bg)">
                  <h6 class="fw-semibold mb-2">Hourly Rates</h6>
                  <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Base Pay Rate</span><span class="fw-medium" id="sb_r_basePay">$0.00/hr</span></div>
                  <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Cost with Payroll Taxes</span><span class="fw-medium" id="sb_r_withTaxes">$0.00/hr</span></div>
                  <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Cost with Overhead</span><span class="fw-medium" id="sb_r_withOverhead">$0.00/hr</span></div>
                  <hr class="my-2">
                  <div class="d-flex justify-content-between"><span class="fw-semibold small">Bill Rate</span><span class="fw-bold text-primary" id="sb_r_billRate">$0.00/hr</span></div>
                </div>

                <div class="rounded p-3" style="background:var(--gasq-muted-bg)">
                  <h6 class="fw-semibold mb-2">Overtime Rates</h6>
                  <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">OT Bill Rate (1.5x)</span><span class="fw-medium" id="sb_r_otRate">$0.00/hr</span></div>
                  <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Holiday Bill Rate (1.5x)</span><span class="fw-medium" id="sb_r_holidayRate">$0.00/hr</span></div>
                </div>

                <div class="rounded p-3" style="background:var(--gasq-muted-bg)">
                  <h6 class="fw-semibold mb-2">Totals</h6>
                  <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Weekly Total</span><span class="fw-medium" id="sb_r_weekly">$0.00</span></div>
                  <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Monthly Total</span><span class="fw-medium" id="sb_r_monthly">$0.00</span></div>
                  <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Annual Total</span><span class="fw-semibold" id="sb_r_annual">$0.00</span></div>
                </div>

                <div class="rounded p-3" style="background:var(--gasq-muted-bg)">
                  <h6 class="fw-semibold mb-2">Uniform &amp; Training</h6>
                  <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Total uniform cost</span><span class="fw-medium" id="sb_r_uniforms">$0.00</span></div>
                  <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Training cost (amortized/hr)</span><span class="fw-medium" id="sb_r_trainingHr">$0.00/hr</span></div>
                </div>

                {{-- Big callout --}}
                <div class="rounded p-4 text-white text-center" style="background:var(--gasq-primary)">
                  <div class="small mb-1" style="opacity:.85">Total Bill Rate</div>
                  <div class="display-5 fw-bold" id="sb_r_totalBillRate">$0.00</div>
                  <div class="small mt-1" style="opacity:.7">per hour</div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== COMPARISON TAB ===== --}}
      <div class="tab-pane fade" id="sb-comparison">
        <p class="text-gasq-muted mb-4">Compare two billing scenarios side by side. Adjust both scenarios independently.</p>
        <div class="row g-4">
          @foreach(['A','B'] as $s)
          <div class="col-lg-6">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:{{ $s==='A'?'#3b82f6':'#22c55e' }}"></span>
              <h5 class="mb-0 fw-semibold">Scenario {{ $s }}</h5>
            </div>
            <div class="card gasq-card">
              <div class="card-body">
                <div class="row g-2 mb-3">
                  <div class="col-6"><label class="form-label x-sm fw-medium">Base Pay $/hr</label><input type="number" id="cmp{{ $s }}_basePay" class="form-control form-control-sm" value="{{ $s==='A'?'18.00':'20.00' }}" step="0.01" oninput="calcCmp()"></div>
                  <div class="col-6"><label class="form-label x-sm fw-medium">Hours/week</label><input type="number" id="cmp{{ $s }}_hours" class="form-control form-control-sm" value="40" oninput="calcCmp()"></div>
                  <div class="col-6"><label class="form-label x-sm fw-medium">Overhead %</label><input type="number" id="cmp{{ $s }}_overhead" class="form-control form-control-sm" value="35" oninput="calcCmp()"></div>
                  <div class="col-6"><label class="form-label x-sm fw-medium">Profit %</label><input type="number" id="cmp{{ $s }}_profit" class="form-control form-control-sm" value="15" oninput="calcCmp()"></div>
                </div>
                <div class="rounded p-3 text-white text-center" style="background:{{ $s==='A'?'var(--gasq-primary)':'#16a34a' }}">
                  <div class="small mb-1" style="opacity:.85">Bill Rate</div>
                  <div class="fs-2 fw-bold" id="cmpRate{{ $s }}">$0.00</div>
                  <div class="small text-center mt-1" style="opacity:.7">Annual: <span id="cmpAnnual{{ $s }}">$0.00</span></div>
                </div>
              </div>
            </div>
          </div>
          @endforeach
        </div>
        <div class="card gasq-card mt-4">
          <div class="card-header py-3"><h5 class="card-title mb-0">Difference (B vs A)</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4 text-center"><div class="small text-gasq-muted mb-1">Hourly Rate</div><div class="fs-5 fw-bold" id="cmpDiffRate">$0.00</div></div>
              <div class="col-md-4 text-center"><div class="small text-gasq-muted mb-1">Weekly</div><div class="fs-5 fw-bold" id="cmpDiffWeekly">$0.00</div></div>
              <div class="col-md-4 text-center"><div class="small text-gasq-muted mb-1">Annual</div><div class="fs-5 fw-bold" id="cmpDiffAnnual">$0.00</div></div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== PROFILE TAB ===== --}}
      <div class="tab-pane fade" id="sb-profile">
        <p class="text-gasq-muted mb-4">Profile information used for PDF reports and billing summaries.</p>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label fw-medium">Company Name</label><input type="text" class="form-control" placeholder="Global Security Management"></div>
          <div class="col-md-6"><label class="form-label fw-medium">Prepared By</label><input type="text" class="form-control" placeholder="Your Name"></div>
          <div class="col-md-6"><label class="form-label fw-medium">Address</label><input type="text" class="form-control" placeholder="123 Main St, City, State"></div>
          <div class="col-md-6"><label class="form-label fw-medium">Phone Number</label><input type="tel" class="form-control" placeholder="(555) 123-4567"></div>
          <div class="col-md-6"><label class="form-label fw-medium">Payroll Cycle</label>
            <select class="form-select"><option>Bi-Weekly</option><option>Weekly</option><option>Monthly</option></select>
          </div>
          <div class="col-md-6"><label class="form-label fw-medium">Email</label><input type="email" class="form-control" placeholder="billing@example.com"></div>
        </div>
      </div>

    </div><!-- /card-body -->
  </div><!-- /card -->

</div>
</div>
@endsection

@push('scripts')
<style>.x-sm{font-size:0.75rem}</style>
<script>
function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v);}
function g(id){return parseFloat(document.getElementById(id).value)||0;}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

async function calcSB(){
  const basePay = g('sb_basePay'), hours = g('sb_hours'), weeks = g('sb_weeks');
  const fica = g('sb_fica')/100, futa = g('sb_futa')/100, suta = g('sb_suta')/100;
  const overhead = g('sb_overhead')/100, profitPct = g('sb_profitPct')/100;
  const uCost = g('sb_uniformCost'), uQty = g('sb_uniformQty'), trainingCost = g('sb_trainingCost');

  const payload = {
    version: 'v24',
    scenario: {
      meta: {
        basePayRate: basePay,
        hoursPerWeek: hours,
        weeksPerYear: weeks,
        ficaPct: fica*100,
        futaPct: futa*100,
        sutaPct: suta*100,
        overheadPct: overhead*100,
        profitPct: profitPct*100,
        uniformCostPerUniform: uCost,
        uniformsPerEmployee: uQty,
        trainingCostPerHire: trainingCost
      }
    }
  };

  const res = await fetch('{{ route('backend.security-billing.v24.compute') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
      'Accept': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if(!res.ok || !data || !data.ok){
    console.error(data);
    return;
  }
  const out = data.kpis || {};

  setText('sb_r_basePay', fmt(basePay)+'/hr');
  setText('sb_r_withTaxes', fmt(out.costWithPayrollTaxes||0)+'/hr');
  setText('sb_r_withOverhead', fmt(out.costWithOverhead||0)+'/hr');
  setText('sb_r_billRate', fmt(out.billRate||0)+'/hr');
  setText('sb_r_otRate', fmt(out.otBillRate||0)+'/hr');
  setText('sb_r_holidayRate', fmt(out.holidayBillRate||0)+'/hr');
  setText('sb_r_weekly', fmt(out.weeklyTotal||0));
  setText('sb_r_monthly', fmt(out.monthlyTotal||0));
  setText('sb_r_annual', fmt(out.annualTotal||0));
  setText('sb_r_uniforms', fmt(out.uniformTotal||0));
  setText('sb_r_trainingHr', fmt(out.trainingCostPerHour||0)+'/hr');
  setText('sb_r_totalBillRate', fmt(out.totalBillRate||0));
}

function calcCmp(){
  ['A','B'].forEach(s=>{
    const basePay = g('cmp'+s+'_basePay'), hours = g('cmp'+s+'_hours');
    const overhead = g('cmp'+s+'_overhead')/100, profitPct = g('cmp'+s+'_profit')/100;
    const taxRate = 0.1441; // default tax
    const withTaxes = basePay * (1 + taxRate);
    const withOverhead = withTaxes * (1 + overhead);
    const billRate = withOverhead / (1 - profitPct);
    const annual = billRate * hours * 52;
    setText('cmpRate'+s, fmt(billRate));
    setText('cmpAnnual'+s, fmt(annual));
    window['_cmp'+s] = { billRate, hours, annual };
  });
  const diffRate = (_cmpB.billRate||0) - (_cmpA.billRate||0);
  const diffWeekly = ((_cmpB.billRate||0)*(_cmpB.hours||0)) - ((_cmpA.billRate||0)*(_cmpA.hours||0));
  const diffAnnual = (_cmpB.annual||0) - (_cmpA.annual||0);
  function styleN(id, v){ const el=document.getElementById(id); el.textContent=fmt(v); el.className='fs-5 fw-bold '+(v>0?'text-danger':v<0?'text-success':''); }
  styleN('cmpDiffRate', diffRate); styleN('cmpDiffWeekly', diffWeekly); styleN('cmpDiffAnnual', diffAnnual);
}

function resetAll(){
  ['sb_basePay','sb_hours','sb_weeks','sb_fica','sb_futa','sb_suta','sb_overhead','sb_profitPct',
   'sb_uniformCost','sb_uniformQty','sb_trainingCost'].forEach(id=>{
    const defaults = {sb_basePay:18,sb_hours:40,sb_weeks:52,sb_fica:7.65,sb_futa:0.8,sb_suta:5.76,
      sb_overhead:35,sb_profitPct:15,sb_uniformCost:75,sb_uniformQty:2,sb_trainingCost:500};
    const el=document.getElementById(id); if(el&&defaults[id]!==undefined) el.value=defaults[id];
  });
  calcSB();
}

document.addEventListener('DOMContentLoaded', ()=>{ calcSB(); calcCmp(); });
</script>
@endpush
