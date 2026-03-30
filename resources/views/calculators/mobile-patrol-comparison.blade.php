@extends('layouts.app')
@section('title', 'Mobile Patrol Scenario Comparison')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('mobile-patrol-calculator') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-code-compare text-primary"></i> Scenario Comparison
        </h1>
        <div class="text-gasq-muted small">Compare two patrol scenarios side by side</div>
      </div>
    </div>
    <button class="btn btn-outline-secondary btn-sm d-print-none" onclick="window.print()">
      <i class="fa fa-print me-1"></i> Print
    </button>
  </div>

  {{-- Comparison Summary --}}
  <div class="card gasq-card mb-4" style="border-color:rgba(6,45,121,0.2);background:rgba(6,45,121,0.03)">
    <div class="card-header py-3">
      <h5 class="card-title mb-0 fw-semibold">Comparison Summary</h5>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="text-center p-3 rounded bg-white">
            <div class="small text-gasq-muted mb-1">Hourly Rate Difference</div>
            <div class="d-flex align-items-center justify-content-center gap-2">
              <i class="fa" id="s-hourlyIcon"></i>
              <span class="fs-4 fw-bold" id="s-hourlyDiff">$0.00</span>
            </div>
            <div class="small" id="s-hourlyPct">0.0%</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="text-center p-3 rounded bg-white">
            <div class="small text-gasq-muted mb-1">Monthly Cost Difference</div>
            <div class="d-flex align-items-center justify-content-center gap-2">
              <i class="fa" id="s-monthlyIcon"></i>
              <span class="fs-4 fw-bold" id="s-monthlyDiff">$0.00</span>
            </div>
            <div class="small text-gasq-muted">per month</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="text-center p-3 rounded bg-white">
            <div class="small text-gasq-muted mb-1">Annual Cost Difference</div>
            <div class="d-flex align-items-center justify-content-center gap-2">
              <i class="fa" id="s-annualIcon"></i>
              <span class="fs-4 fw-bold" id="s-annualDiff">$0.00</span>
            </div>
            <div class="small" id="s-annualPct">0.0%</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Side by Side Scenarios --}}
  <div class="row g-4">

    {{-- Scenario A --}}
    <div class="col-lg-6">
      <div class="d-flex align-items-center gap-2 mb-2 px-1">
        <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:#3b82f6"></span>
        <h5 class="fw-semibold mb-0" id="labelA">Scenario A – Current</h5>
      </div>
      <div class="card gasq-card">
        <div class="card-body">
          <div class="mb-2">
            <label class="form-label small fw-medium">Scenario Name</label>
            <input type="text" id="a_name" class="form-control form-control-sm" value="Scenario A – Current" oninput="updateLabel('a');calculate()">
          </div>
          <hr class="my-2">
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label x-sm fw-medium">Hours/day</label><input type="number" id="a_hoursPerDay" class="form-control form-control-sm" value="24" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Days/year</label><input type="number" id="a_daysPerYear" class="form-control form-control-sm" value="365" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Patrolman wage $/hr</label><input type="number" id="a_wage" class="form-control form-control-sm" value="30.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Payroll burden %</label><input type="number" id="a_burden" class="form-control form-control-sm" value="24" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Vehicle finance $/yr</label><input type="number" id="a_vehFin" class="form-control form-control-sm" value="7980.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Miles/day</label><input type="number" id="a_miles" class="form-control form-control-sm" value="360" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">MPG</label><input type="number" id="a_mpg" class="form-control form-control-sm" value="20" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Fuel $/gallon</label><input type="number" id="a_fuel" class="form-control form-control-sm" value="2.57" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Annual repairs $</label><input type="number" id="a_repairs" class="form-control form-control-sm" value="4000.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Tires/yr $</label><input type="number" id="a_tires" class="form-control form-control-sm" value="1200.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Oil change cost $</label><input type="number" id="a_oilCost" class="form-control form-control-sm" value="32" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Miles/oil change</label><input type="number" id="a_oilMiles" class="form-control form-control-sm" value="6000" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Auto insurance $/yr</label><input type="number" id="a_insurance" class="form-control form-control-sm" value="1500.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Markup %</label><input type="number" id="a_markup" class="form-control form-control-sm" value="27" oninput="calculate()"></div>
          </div>
          <div class="rounded p-3 mt-3 text-white text-center" style="background:var(--gasq-primary)">
            <div class="small mb-1" style="opacity:.85">Hourly Billable Rate</div>
            <div class="fs-2 fw-bold" id="a_rate">$0.00</div>
            <div class="small mt-1" style="opacity:.7">per hour</div>
          </div>
          <div class="mt-2 small text-gasq-muted text-center">Annual: <span id="a_annual">$0.00</span></div>
        </div>
      </div>
    </div>

    {{-- Scenario B --}}
    <div class="col-lg-6">
      <div class="d-flex align-items-center gap-2 mb-2 px-1">
        <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:#22c55e"></span>
        <h5 class="fw-semibold mb-0" id="labelB">Scenario B – Proposed</h5>
      </div>
      <div class="card gasq-card">
        <div class="card-body">
          <div class="mb-2">
            <label class="form-label small fw-medium">Scenario Name</label>
            <input type="text" id="b_name" class="form-control form-control-sm" value="Scenario B – Proposed" oninput="updateLabel('b');calculate()">
          </div>
          <hr class="my-2">
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label x-sm fw-medium">Hours/day</label><input type="number" id="b_hoursPerDay" class="form-control form-control-sm" value="12" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Days/year</label><input type="number" id="b_daysPerYear" class="form-control form-control-sm" value="365" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Patrolman wage $/hr</label><input type="number" id="b_wage" class="form-control form-control-sm" value="28.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Payroll burden %</label><input type="number" id="b_burden" class="form-control form-control-sm" value="24" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Vehicle finance $/yr</label><input type="number" id="b_vehFin" class="form-control form-control-sm" value="6500.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Miles/day</label><input type="number" id="b_miles" class="form-control form-control-sm" value="200" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">MPG</label><input type="number" id="b_mpg" class="form-control form-control-sm" value="25" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Fuel $/gallon</label><input type="number" id="b_fuel" class="form-control form-control-sm" value="2.57" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Annual repairs $</label><input type="number" id="b_repairs" class="form-control form-control-sm" value="3000.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Tires/yr $</label><input type="number" id="b_tires" class="form-control form-control-sm" value="1000.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Oil change cost $</label><input type="number" id="b_oilCost" class="form-control form-control-sm" value="32" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Miles/oil change</label><input type="number" id="b_oilMiles" class="form-control form-control-sm" value="6000" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Auto insurance $/yr</label><input type="number" id="b_insurance" class="form-control form-control-sm" value="1200.00" step="0.01" oninput="calculate()"></div>
            <div class="col-6"><label class="form-label x-sm fw-medium">Markup %</label><input type="number" id="b_markup" class="form-control form-control-sm" value="25" oninput="calculate()"></div>
          </div>
          <div class="rounded p-3 mt-3 text-white text-center" style="background:#16a34a">
            <div class="small mb-1" style="opacity:.85">Hourly Billable Rate</div>
            <div class="fs-2 fw-bold" id="b_rate">$0.00</div>
            <div class="small mt-1" style="opacity:.7">per hour</div>
          </div>
          <div class="mt-2 small text-gasq-muted text-center">Annual: <span id="b_annual">$0.00</span></div>
        </div>
      </div>
    </div>

  </div><!-- /row -->
</div>
</div>
@endsection

@push('scripts')
<style>.x-sm{font-size:0.75rem}</style>
<script>
function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v);}
function fmtPct(v){return (v>0?'+':'')+v.toFixed(1)+'%';}
function gv(id){return parseFloat(document.getElementById(id).value)||0;}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

function calcScenario(p){
  const hoursPerYear = p.hoursPerDay * p.daysPerYear;
  const annualWageCost = hoursPerYear * p.wage * (1 + p.burden/100);
  const milesDrivenPerYear = p.miles * p.daysPerYear;
  const fuelGallonsPerYear = p.mpg>0 ? milesDrivenPerYear/p.mpg : 0;
  const annualFuelCost = fuelGallonsPerYear * p.fuel;
  const oilChangesPerYear = p.oilMiles>0 ? milesDrivenPerYear/p.oilMiles : 0;
  const annualOilCost = oilChangesPerYear * p.oilCost;
  const totalPreMarkup = annualWageCost + p.vehFin + annualFuelCost + p.repairs + p.tires + annualOilCost + p.insurance;
  const markupFrac = p.markup/100;
  const annualCostWithMarkup = markupFrac<1 ? totalPreMarkup/(1-markupFrac) : totalPreMarkup;
  const hourlyRate = hoursPerYear>0 ? annualCostWithMarkup/hoursPerYear : 0;
  return { hourlyRate, annualCostWithMarkup };
}

function readScenario(prefix){
  return {
    hoursPerDay: gv(prefix+'_hoursPerDay'), daysPerYear: gv(prefix+'_daysPerYear'),
    wage: gv(prefix+'_wage'), burden: gv(prefix+'_burden'),
    vehFin: gv(prefix+'_vehFin'), miles: gv(prefix+'_miles'),
    mpg: gv(prefix+'_mpg'), fuel: gv(prefix+'_fuel'),
    repairs: gv(prefix+'_repairs'), tires: gv(prefix+'_tires'),
    oilCost: gv(prefix+'_oilCost'), oilMiles: gv(prefix+'_oilMiles'),
    insurance: gv(prefix+'_insurance'), markup: gv(prefix+'_markup'),
  };
}

function updateLabel(p){ document.getElementById('label'+p.toUpperCase()).textContent = document.getElementById(p+'_name').value; }

function calculate(){
  const a = calcScenario(readScenario('a'));
  const b = calcScenario(readScenario('b'));

  setText('a_rate', fmt(a.hourlyRate));
  setText('a_annual', fmt(a.annualCostWithMarkup));
  setText('b_rate', fmt(b.hourlyRate));
  setText('b_annual', fmt(b.annualCostWithMarkup));

  const hourlyDiff = b.hourlyRate - a.hourlyRate;
  const annualDiff = b.annualCostWithMarkup - a.annualCostWithMarkup;
  const monthlyDiff = annualDiff / 12;
  const hourlyPct = a.hourlyRate>0 ? (hourlyDiff/a.hourlyRate)*100 : 0;
  const annualPct = a.annualCostWithMarkup>0 ? (annualDiff/a.annualCostWithMarkup)*100 : 0;

  // Diff card
  const hEl = document.getElementById('s-hourlyDiff');
  hEl.textContent = fmt(Math.abs(hourlyDiff));
  hEl.className = 'fs-4 fw-bold ' + (hourlyDiff>0?'text-danger':'text-success');
  setText('s-hourlyPct', fmtPct(hourlyPct));
  document.getElementById('s-hourlyPct').className = 'small ' + (hourlyPct>0?'text-danger':'text-success');

  const mEl = document.getElementById('s-monthlyDiff');
  mEl.textContent = fmt(Math.abs(monthlyDiff));
  mEl.className = 'fs-4 fw-bold ' + (monthlyDiff>0?'text-danger':'text-success');

  const aEl = document.getElementById('s-annualDiff');
  aEl.textContent = fmt(Math.abs(annualDiff));
  aEl.className = 'fs-4 fw-bold ' + (annualDiff>0?'text-danger':'text-success');
  setText('s-annualPct', fmtPct(annualPct));
  document.getElementById('s-annualPct').className = 'small ' + (annualPct>0?'text-danger':'text-success');

  // Icons
  function setIcon(id, diff){ document.getElementById(id).className='fa ' + (diff>0?'fa-arrow-trend-up text-danger':diff<0?'fa-arrow-trend-down text-success':'fa-minus text-gasq-muted'); }
  setIcon('s-hourlyIcon', hourlyDiff); setIcon('s-monthlyIcon', monthlyDiff); setIcon('s-annualIcon', annualDiff);
}

document.addEventListener('DOMContentLoaded', calculate);
</script>
@endpush
