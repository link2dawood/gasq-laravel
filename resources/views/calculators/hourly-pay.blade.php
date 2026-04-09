@extends('layouts.app')
@section('title', 'Hourly Pay Calculator')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-clock text-primary"></i> Hourly Pay Calculator
        </h1>
        <div class="text-gasq-muted small">Calculate total compensation and gross pay</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="resetForm()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card gasq-card h-100">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2"><i class="fa fa-dollar-sign text-primary"></i> Pay Parameters</h5></div>
        <div class="card-body d-flex flex-column gap-4">

          <div>
            <label class="form-label fw-medium">Hourly Pay Rate ($/hr)</label>
            <input type="number" id="hp_rate" class="form-control" value="18.00" step="0.01" oninput="calcHP()">
          </div>

          <div>
            <label class="form-label fw-medium">Regular Hours per Week</label>
            <input type="number" id="hp_regHrs" class="form-control" value="40" step="0.5" oninput="calcHP()">
          </div>

          <div>
            <label class="form-label fw-medium">Overtime Hours per Week</label>
            <input type="number" id="hp_otHrs" class="form-control" value="0" step="0.5" oninput="calcHP()">
            <div class="form-text">Overtime paid at 1.5x base rate</div>
          </div>

          <div>
            <label class="form-label fw-medium">Double-Time Hours per Week</label>
            <input type="number" id="hp_dtHrs" class="form-control" value="0" step="0.5" oninput="calcHP()">
            <div class="form-text">Double time paid at 2.0x base rate</div>
          </div>

          <hr class="my-1">
          <h6 class="fw-semibold">Deductions &amp; Benefits</h6>

          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small fw-medium">Federal Tax (%)</label>
              <input type="number" id="hp_fedTax" class="form-control form-control-sm" value="12" step="0.1" oninput="calcHP()">
            </div>
            <div class="col-6">
              <label class="form-label small fw-medium">State Tax (%)</label>
              <input type="number" id="hp_stateTax" class="form-control form-control-sm" value="5" step="0.1" oninput="calcHP()">
            </div>
            <div class="col-6">
              <label class="form-label small fw-medium">FICA/SS (%)</label>
              <input type="number" id="hp_fica" class="form-control form-control-sm" value="6.2" step="0.1" oninput="calcHP()">
            </div>
            <div class="col-6">
              <label class="form-label small fw-medium">Medicare (%)</label>
              <input type="number" id="hp_medicare" class="form-control form-control-sm" value="1.45" step="0.01" oninput="calcHP()">
            </div>
            <div class="col-6">
              <label class="form-label small fw-medium">Health Insurance ($/wk)</label>
              <input type="number" id="hp_health" class="form-control form-control-sm" value="0" step="0.01" oninput="calcHP()">
            </div>
            <div class="col-6">
              <label class="form-label small fw-medium">Other Deductions ($/wk)</label>
              <input type="number" id="hp_other" class="form-control form-control-sm" value="0" step="0.01" oninput="calcHP()">
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card gasq-card h-100">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2"><i class="fa fa-chart-bar text-primary"></i> Pay Summary</h5></div>
        <div class="card-body d-flex flex-column gap-3">

          <div class="gasq-input-section">
            <h6 class="fw-semibold mb-2">Gross Pay</h6>
            <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Regular pay (<span id="r_regHrs">40</span>hr × <span id="r_rate">$18.00</span>)</span><span class="fw-medium gasq-mono" id="r_regPay">$0.00</span></div>
            <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Overtime pay (<span id="r_otHrs">0</span>hr × 1.5x)</span><span class="fw-medium gasq-mono" id="r_otPay">$0.00</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Double-time pay (<span id="r_dtHrs">0</span>hr × 2.0x)</span><span class="fw-medium gasq-mono" id="r_dtPay">$0.00</span></div>
            <hr class="my-1">
            <div class="d-flex justify-content-between fw-semibold"><span>Weekly Gross Pay</span><span class="gasq-mono" id="r_weeklyGross">$0.00</span></div>
          </div>

          <div class="gasq-input-section">
            <h6 class="fw-semibold mb-2">Deductions</h6>
            <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Federal tax</span><span class="fw-medium gasq-mono" id="r_fedTax">$0.00</span></div>
            <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">State tax</span><span class="fw-medium gasq-mono" id="r_stateTax">$0.00</span></div>
            <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">FICA / Social Security</span><span class="fw-medium gasq-mono" id="r_ficaAmt">$0.00</span></div>
            <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Medicare</span><span class="fw-medium gasq-mono" id="r_medicareAmt">$0.00</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Health &amp; other</span><span class="fw-medium gasq-mono" id="r_otherDed">$0.00</span></div>
            <hr class="my-1">
            <div class="d-flex justify-content-between fw-semibold"><span>Total Deductions</span><span class="gasq-mono" id="r_totalDed">$0.00</span></div>
          </div>

          {{-- Net Pay --}}
          <div class="rounded p-4 text-white text-center" style="background:var(--gasq-primary)">
            <div class="small mb-1" style="opacity:.85">Weekly Net (Take-Home) Pay</div>
            <div class="display-5 fw-bold" id="r_netPay">$0.00</div>
          </div>

          <div class="row g-3">
            <div class="col-4">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Bi-Weekly Net</div>
                <div class="metric-value text-primary" id="r_biweekly">$0.00</div>
              </div>
            </div>
            <div class="col-4">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Monthly Net</div>
                <div class="metric-value text-primary" id="r_monthly">$0.00</div>
              </div>
            </div>
            <div class="col-4">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Annual Net</div>
                <div class="metric-value text-primary" id="r_annual">$0.00</div>
              </div>
            </div>
          </div>

          <div class="rounded p-3" style="background:var(--gasq-muted-bg)">
            <h6 class="fw-semibold mb-2">Effective Rates</h6>
            <div class="d-flex justify-content-between mb-1"><span class="text-gasq-muted small">Total effective tax rate</span><span class="fw-medium" id="r_effTaxRate">0.0%</span></div>
            <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Effective hourly take-home</span><span class="fw-medium" id="r_effHourly">$0.00/hr</span></div>
          </div>

        </div>
      </div>
    </div>
  </div>

</div>
</div>
@endsection

@push('scripts')
<style>.x-sm{font-size:0.75rem;line-height:1.2}</style>
<script>
function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v);}
function g(id){return parseFloat(document.getElementById(id).value)||0;}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

async function calcHP(){
  const rate=g('hp_rate'), regHrs=g('hp_regHrs'), otHrs=g('hp_otHrs'), dtHrs=g('hp_dtHrs');
  const payload = { version:'v24', scenario:{ meta:{
    hourlyRate: rate,
    regularHours: regHrs,
    otHours: otHrs,
    doubleTimeHours: dtHrs,
    fedTaxPct: g('hp_fedTax'),
    stateTaxPct: g('hp_stateTax'),
    ficaPct: g('hp_fica'),
    medicarePct: g('hp_medicare'),
    healthWeekly: g('hp_health'),
    otherWeekly: g('hp_other'),
  } } };
  const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'hourly-pay-calculator']) }}', {
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

  setText('r_regHrs', regHrs); setText('r_rate', fmt(rate)); setText('r_otHrs', otHrs); setText('r_dtHrs', dtHrs);
  setText('r_regPay', fmt(out.regPay||0)); setText('r_otPay', fmt(out.otPay||0)); setText('r_dtPay', fmt(out.dtPay||0));
  setText('r_weeklyGross', fmt(out.weeklyGross||0));
  setText('r_fedTax', fmt(out.fedTax||0)); setText('r_stateTax', fmt(out.stateTax||0));
  setText('r_ficaAmt', fmt(out.ficaAmt||0)); setText('r_medicareAmt', fmt(out.medicareAmt||0));
  setText('r_otherDed', fmt(out.otherDeductions||0)); setText('r_totalDed', fmt(out.totalDeductions||0));
  setText('r_netPay', fmt(out.netPay||0));
  setText('r_biweekly', fmt(out.biweeklyNetPay||0)); setText('r_monthly', fmt(out.monthlyNetPay||0)); setText('r_annual', fmt(out.annualNetPay||0));
  setText('r_effTaxRate', (out.effectiveTaxRatePct||0).toFixed(1)+'%'); setText('r_effHourly', fmt(out.effectiveNetHourly||0)+'/hr');
}

function resetForm(){
  ['hp_rate','hp_regHrs','hp_otHrs','hp_dtHrs','hp_fedTax','hp_stateTax','hp_fica','hp_medicare','hp_health','hp_other'].forEach(id=>{
    const defaults={hp_rate:18,hp_regHrs:40,hp_otHrs:0,hp_dtHrs:0,hp_fedTax:12,hp_stateTax:5,hp_fica:6.2,hp_medicare:1.45,hp_health:0,hp_other:0};
    const el=document.getElementById(id);if(el)el.value=defaults[id];
  });
  calcHP();
}

document.addEventListener('DOMContentLoaded', calcHP);
</script>
@endpush
