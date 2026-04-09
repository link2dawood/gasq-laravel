@extends('layouts.app')
@section('title', 'Mobile Patrol Scenario Comparison')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .mpc-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 28%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .mpc-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .mpc-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .mpc-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .mpc-section + .mpc-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .mpc-panel {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .mpc-panel-muted {
    background: rgba(6, 45, 121, 0.04);
  }
  .mpc-stat {
    border: 1px solid rgba(6, 45, 121, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .mpc-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .mpc-stat-value {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--gasq-primary);
  }
  .mpc-chip {
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
  .mpc-dot {
    width: 12px;
    height: 12px;
    border-radius: 999px;
    display: inline-block;
  }
  .mpc-dot-a {
    background: #3b82f6;
  }
  .mpc-dot-b {
    background: #22c55e;
  }
  .mpc-mono {
    font-variant-numeric: tabular-nums;
  }
  .mpc-input-grid .form-label {
    font-size: 0.75rem;
    font-weight: 600;
  }
  @media (max-width: 1199.98px) {
    .mpc-sticky {
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
      <a href="{{ route('mobile-patrol-calculator') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-code-compare text-primary"></i> Mobile Patrol Scenario Comparison
        </h1>
        <div class="text-gasq-muted small">Shared input rail with live A/B patrol pricing differences</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-outline-secondary btn-sm" onclick="resetComparisonDefaults()">
        <i class="fa fa-rotate me-1"></i> Reset
      </button>
      <button class="btn btn-outline-secondary btn-sm d-print-none" onclick="window.print()">
        <i class="fa fa-print me-1"></i> Print
      </button>
    </div>
  </div>

  <div class="card gasq-card mpc-shell overflow-hidden">
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-xl-4 border-end mpc-sidebar">
          <div class="p-3 p-md-4 mpc-sticky">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
              <div>
                <div class="mpc-kicker mb-2">Shared Inputs</div>
                <h2 class="h4 fw-bold mb-2">Comparison Model Controls</h2>
                <p class="small text-gasq-muted mb-0">Both patrol scenarios are configured here. Every change updates the rate cards, savings deltas, and comparison summaries on the right in real time.</p>
              </div>
              <span class="mpc-chip"><i class="fa fa-bolt"></i> Live</span>
            </div>

            <div class="mpc-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="mpc-dot mpc-dot-a"></span>
                <h5 class="mb-0 fw-semibold">Scenario A Inputs</h5>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-medium">Scenario Name</label>
                <input type="text" id="a_name" class="form-control form-control-sm" value="Scenario A - Current" oninput="updateLabel('a');calculate()">
              </div>

              <div class="row g-3 mpc-input-grid">
                <div class="col-md-6"><label class="form-label">Hours/day</label><input type="number" id="a_hoursPerDay" class="form-control form-control-sm" value="24" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Days/year</label><input type="number" id="a_daysPerYear" class="form-control form-control-sm" value="365" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Patrolman wage $/hr</label><input type="number" id="a_wage" class="form-control form-control-sm" value="30.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Payroll burden %</label><input type="number" id="a_burden" class="form-control form-control-sm" value="24" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Vehicle finance $/yr</label><input type="number" id="a_vehFin" class="form-control form-control-sm" value="7980.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Miles/day</label><input type="number" id="a_miles" class="form-control form-control-sm" value="360" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">MPG</label><input type="number" id="a_mpg" class="form-control form-control-sm" value="20" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Fuel $/gallon</label><input type="number" id="a_fuel" class="form-control form-control-sm" value="2.57" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Annual repairs $</label><input type="number" id="a_repairs" class="form-control form-control-sm" value="4000.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Tires/yr $</label><input type="number" id="a_tires" class="form-control form-control-sm" value="1200.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Oil change cost $</label><input type="number" id="a_oilCost" class="form-control form-control-sm" value="32" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Miles/oil change</label><input type="number" id="a_oilMiles" class="form-control form-control-sm" value="6000" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Auto insurance $/yr</label><input type="number" id="a_insurance" class="form-control form-control-sm" value="1500.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Markup %</label><input type="number" id="a_markup" class="form-control form-control-sm" value="27" oninput="calculate()"></div>
              </div>
            </div>

            <div class="mpc-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="mpc-dot mpc-dot-b"></span>
                <h5 class="mb-0 fw-semibold">Scenario B Inputs</h5>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-medium">Scenario Name</label>
                <input type="text" id="b_name" class="form-control form-control-sm" value="Scenario B - Proposed" oninput="updateLabel('b');calculate()">
              </div>

              <div class="row g-3 mpc-input-grid">
                <div class="col-md-6"><label class="form-label">Hours/day</label><input type="number" id="b_hoursPerDay" class="form-control form-control-sm" value="12" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Days/year</label><input type="number" id="b_daysPerYear" class="form-control form-control-sm" value="365" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Patrolman wage $/hr</label><input type="number" id="b_wage" class="form-control form-control-sm" value="28.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Payroll burden %</label><input type="number" id="b_burden" class="form-control form-control-sm" value="24" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Vehicle finance $/yr</label><input type="number" id="b_vehFin" class="form-control form-control-sm" value="6500.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Miles/day</label><input type="number" id="b_miles" class="form-control form-control-sm" value="200" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">MPG</label><input type="number" id="b_mpg" class="form-control form-control-sm" value="25" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Fuel $/gallon</label><input type="number" id="b_fuel" class="form-control form-control-sm" value="2.57" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Annual repairs $</label><input type="number" id="b_repairs" class="form-control form-control-sm" value="3000.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Tires/yr $</label><input type="number" id="b_tires" class="form-control form-control-sm" value="1000.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Oil change cost $</label><input type="number" id="b_oilCost" class="form-control form-control-sm" value="32" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Miles/oil change</label><input type="number" id="b_oilMiles" class="form-control form-control-sm" value="6000" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Auto insurance $/yr</label><input type="number" id="b_insurance" class="form-control form-control-sm" value="1200.00" step="0.01" oninput="calculate()"></div>
                <div class="col-md-6"><label class="form-label">Markup %</label><input type="number" id="b_markup" class="form-control form-control-sm" value="25" oninput="calculate()"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="p-3 p-md-4">
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="mpc-stat">
                  <div class="mpc-stat-label mb-2">Hourly Rate Difference</div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="fa" id="s-hourlyIcon"></i>
                    <div class="mpc-stat-value mpc-mono" id="s-hourlyDiff">$0.00</div>
                  </div>
                  <div class="small mt-1" id="s-hourlyPct">0.0%</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mpc-stat">
                  <div class="mpc-stat-label mb-2">Monthly Cost Difference</div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="fa" id="s-monthlyIcon"></i>
                    <div class="mpc-stat-value mpc-mono" id="s-monthlyDiff">$0.00</div>
                  </div>
                  <div class="small text-gasq-muted mt-1">per month</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mpc-stat">
                  <div class="mpc-stat-label mb-2">Annual Cost Difference</div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="fa" id="s-annualIcon"></i>
                    <div class="mpc-stat-value mpc-mono" id="s-annualDiff">$0.00</div>
                  </div>
                  <div class="small mt-1" id="s-annualPct">0.0%</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <div class="mpc-kicker mb-1">Results Workspace</div>
                <h3 class="h5 fw-bold mb-0">Live Patrol Comparison Outputs</h3>
              </div>
              <div class="small text-gasq-muted">Both scenario result panels below update from the shared input rail on the left.</div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-12">
                <div class="mpc-panel mpc-panel-muted p-3">
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                      <div class="small text-gasq-muted mb-1">Decision Signal</div>
                      <h4 class="fw-bold mb-1" id="savingsHeadline">Scenario B saves vs Scenario A</h4>
                      <div class="small text-gasq-muted">Use this section to quickly see which setup is more efficient at current assumptions.</div>
                    </div>
                    <div class="text-end">
                      <div class="small text-gasq-muted">Annual Delta</div>
                      <div class="fs-3 fw-bold mpc-mono" id="savingsValue">$0.00</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-lg-6">
                <div class="mpc-panel p-3 h-100">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="mpc-dot mpc-dot-a"></span>
                    <h5 class="fw-semibold mb-0" id="labelA">Scenario A - Current</h5>
                  </div>
                  <div class="rounded p-3 text-white text-center mb-3" style="background:var(--gasq-primary)">
                    <div class="small mb-1" style="opacity:.85">Hourly Billable Rate</div>
                    <div class="fs-2 fw-bold mpc-mono" id="a_rate">$0.00</div>
                    <div class="small mt-1" style="opacity:.7">per hour</div>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Annual cost with markup</span>
                    <span class="fw-semibold mpc-mono" id="a_annual">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Monthly cost with markup</span>
                    <span class="fw-medium mpc-mono" id="a_monthly">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Hours per year</span>
                    <span class="fw-medium mpc-mono" id="a_hours">0</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-gasq-muted small">Pre-markup annual cost</span>
                    <span class="fw-medium mpc-mono" id="a_pre">$0.00</span>
                  </div>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="mpc-panel p-3 h-100">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="mpc-dot mpc-dot-b"></span>
                    <h5 class="fw-semibold mb-0" id="labelB">Scenario B - Proposed</h5>
                  </div>
                  <div class="rounded p-3 text-white text-center mb-3" style="background:#16a34a">
                    <div class="small mb-1" style="opacity:.85">Hourly Billable Rate</div>
                    <div class="fs-2 fw-bold mpc-mono" id="b_rate">$0.00</div>
                    <div class="small mt-1" style="opacity:.7">per hour</div>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Annual cost with markup</span>
                    <span class="fw-semibold mpc-mono" id="b_annual">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Monthly cost with markup</span>
                    <span class="fw-medium mpc-mono" id="b_monthly">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Hours per year</span>
                    <span class="fw-medium mpc-mono" id="b_hours">0</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-gasq-muted small">Pre-markup annual cost</span>
                    <span class="fw-medium mpc-mono" id="b_pre">$0.00</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <x-report-actions reportType="mobile-patrol-comparison" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
@endsection

@push('scripts')
<script>
const savedScenario = window.__gasqCalculatorState?.scenario || null;
const COMPARISON_DEFAULTS = {
  a_name: 'Scenario A - Current',
  a_hoursPerDay: 24,
  a_daysPerYear: 365,
  a_wage: 30.00,
  a_burden: 24,
  a_vehFin: 7980.00,
  a_miles: 360,
  a_mpg: 20,
  a_fuel: 2.57,
  a_repairs: 4000.00,
  a_tires: 1200.00,
  a_oilCost: 32,
  a_oilMiles: 6000,
  a_insurance: 1500.00,
  a_markup: 27,
  b_name: 'Scenario B - Proposed',
  b_hoursPerDay: 12,
  b_daysPerYear: 365,
  b_wage: 28.00,
  b_burden: 24,
  b_vehFin: 6500.00,
  b_miles: 200,
  b_mpg: 25,
  b_fuel: 2.57,
  b_repairs: 3000.00,
  b_tires: 1000.00,
  b_oilCost: 32,
  b_oilMiles: 6000,
  b_insurance: 1200.00,
  b_markup: 25
};

function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v);}
function fmtPct(v){return (v > 0 ? '+' : '') + v.toFixed(1) + '%';}
function fmtN(v, dec = 0){return new Intl.NumberFormat('en-US',{minimumFractionDigits:dec,maximumFractionDigits:dec}).format(v);}
function gv(id){return parseFloat(document.getElementById(id).value)||0;}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

function calcScenario(p){
  const hoursPerYear = p.hoursPerDay * p.daysPerYear;
  const annualWageCost = hoursPerYear * p.wage * (1 + p.burden/100);
  const milesDrivenPerYear = p.miles * p.daysPerYear;
  const fuelGallonsPerYear = p.mpg > 0 ? milesDrivenPerYear / p.mpg : 0;
  const annualFuelCost = fuelGallonsPerYear * p.fuel;
  const oilChangesPerYear = p.oilMiles > 0 ? milesDrivenPerYear / p.oilMiles : 0;
  const annualOilCost = oilChangesPerYear * p.oilCost;
  const totalPreMarkup = annualWageCost + p.vehFin + annualFuelCost + p.repairs + p.tires + annualOilCost + p.insurance;
  const markupFrac = p.markup / 100;
  const annualCostWithMarkup = markupFrac < 1 ? totalPreMarkup / (1 - markupFrac) : totalPreMarkup;
  const monthlyCostWithMarkup = annualCostWithMarkup / 12;
  const hourlyRate = hoursPerYear > 0 ? annualCostWithMarkup / hoursPerYear : 0;
  return { hourlyRate, annualCostWithMarkup, monthlyCostWithMarkup, hoursPerYear, totalPreMarkup };
}

function readScenario(prefix){
  return {
    name: document.getElementById(prefix + '_name').value || (prefix === 'a' ? 'Scenario A' : 'Scenario B'),
    hoursPerDay: gv(prefix + '_hoursPerDay'),
    daysPerYear: gv(prefix + '_daysPerYear'),
    wage: gv(prefix + '_wage'),
    burden: gv(prefix + '_burden'),
    vehFin: gv(prefix + '_vehFin'),
    miles: gv(prefix + '_miles'),
    mpg: gv(prefix + '_mpg'),
    fuel: gv(prefix + '_fuel'),
    repairs: gv(prefix + '_repairs'),
    tires: gv(prefix + '_tires'),
    oilCost: gv(prefix + '_oilCost'),
    oilMiles: gv(prefix + '_oilMiles'),
    insurance: gv(prefix + '_insurance'),
    markup: gv(prefix + '_markup'),
  };
}

function hydrateSavedScenario(prefix, values){
  if(!values) return;
  const map = {
    [prefix + '_name']: values.name,
    [prefix + '_hoursPerDay']: values.hoursPerDay,
    [prefix + '_daysPerYear']: values.daysPerYear,
    [prefix + '_wage']: values.wage,
    [prefix + '_burden']: values.burden,
    [prefix + '_vehFin']: values.vehFin,
    [prefix + '_miles']: values.miles,
    [prefix + '_mpg']: values.mpg,
    [prefix + '_fuel']: values.fuel,
    [prefix + '_repairs']: values.repairs,
    [prefix + '_tires']: values.tires,
    [prefix + '_oilCost']: values.oilCost,
    [prefix + '_oilMiles']: values.oilMiles,
    [prefix + '_insurance']: values.insurance,
    [prefix + '_markup']: values.markup,
  };

  Object.entries(map).forEach(([id, value]) => {
    if(value === undefined || value === null) return;
    const el = document.getElementById(id);
    if(el) el.value = value;
  });
}

function updateLabel(prefix){
  const name = document.getElementById(prefix + '_name').value || (prefix === 'a' ? 'Scenario A' : 'Scenario B');
  const target = document.getElementById('label' + prefix.toUpperCase());
  if (target) target.textContent = name;
}

let persistT = null;
async function persistReportPayload(a, b){
  try{
    const res = await fetch(@json(route('backend.report-payload.store')), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        type: 'mobile-patrol-comparison',
        scenario: { a: readScenario('a'), b: readScenario('b') },
        result: {
          scenario_a_annual: a.annualCostWithMarkup,
          scenario_b_annual: b.annualCostWithMarkup,
          savings: a.annualCostWithMarkup - b.annualCostWithMarkup,
          savings_percent: a.annualCostWithMarkup > 0 ? ((a.annualCostWithMarkup - b.annualCostWithMarkup) / a.annualCostWithMarkup) * 100 : 0,
        },
      }),
    });
    if(!res.ok){ return; }
  }catch(e){
    return;
  }
}

function resetComparisonDefaults(){
  Object.entries(COMPARISON_DEFAULTS).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) el.value = value;
  });
  updateLabel('a');
  updateLabel('b');
  calculate();
}

function calculate(){
  const a = calcScenario(readScenario('a'));
  const b = calcScenario(readScenario('b'));

  setText('a_rate', fmt(a.hourlyRate));
  setText('a_annual', fmt(a.annualCostWithMarkup));
  setText('a_monthly', fmt(a.monthlyCostWithMarkup));
  setText('a_hours', fmtN(a.hoursPerYear, 0));
  setText('a_pre', fmt(a.totalPreMarkup));

  setText('b_rate', fmt(b.hourlyRate));
  setText('b_annual', fmt(b.annualCostWithMarkup));
  setText('b_monthly', fmt(b.monthlyCostWithMarkup));
  setText('b_hours', fmtN(b.hoursPerYear, 0));
  setText('b_pre', fmt(b.totalPreMarkup));

  const hourlyDiff = b.hourlyRate - a.hourlyRate;
  const annualDiff = b.annualCostWithMarkup - a.annualCostWithMarkup;
  const monthlyDiff = annualDiff / 12;
  const hourlyPct = a.hourlyRate > 0 ? (hourlyDiff / a.hourlyRate) * 100 : 0;
  const annualPct = a.annualCostWithMarkup > 0 ? (annualDiff / a.annualCostWithMarkup) * 100 : 0;

  const hEl = document.getElementById('s-hourlyDiff');
  hEl.textContent = fmt(Math.abs(hourlyDiff));
  hEl.className = 'mpc-stat-value mpc-mono ' + (hourlyDiff > 0 ? 'text-danger' : hourlyDiff < 0 ? 'text-success' : '');
  setText('s-hourlyPct', fmtPct(hourlyPct));
  document.getElementById('s-hourlyPct').className = 'small mt-1 ' + (hourlyPct > 0 ? 'text-danger' : hourlyPct < 0 ? 'text-success' : 'text-gasq-muted');

  const mEl = document.getElementById('s-monthlyDiff');
  mEl.textContent = fmt(Math.abs(monthlyDiff));
  mEl.className = 'mpc-stat-value mpc-mono ' + (monthlyDiff > 0 ? 'text-danger' : monthlyDiff < 0 ? 'text-success' : '');

  const aEl = document.getElementById('s-annualDiff');
  aEl.textContent = fmt(Math.abs(annualDiff));
  aEl.className = 'mpc-stat-value mpc-mono ' + (annualDiff > 0 ? 'text-danger' : annualDiff < 0 ? 'text-success' : '');
  setText('s-annualPct', fmtPct(annualPct));
  document.getElementById('s-annualPct').className = 'small mt-1 ' + (annualPct > 0 ? 'text-danger' : annualPct < 0 ? 'text-success' : 'text-gasq-muted');

  function setIcon(id, diff){
    document.getElementById(id).className = 'fa ' + (diff > 0 ? 'fa-arrow-trend-up text-danger' : diff < 0 ? 'fa-arrow-trend-down text-success' : 'fa-minus text-gasq-muted');
  }
  setIcon('s-hourlyIcon', hourlyDiff);
  setIcon('s-monthlyIcon', monthlyDiff);
  setIcon('s-annualIcon', annualDiff);

  const savingsHeadline = document.getElementById('savingsHeadline');
  const savingsValue = document.getElementById('savingsValue');
  if (annualDiff < 0) {
    savingsHeadline.textContent = 'Scenario B saves vs Scenario A';
    savingsValue.textContent = fmt(Math.abs(annualDiff));
    savingsValue.className = 'fs-3 fw-bold mpc-mono text-success';
  } else if (annualDiff > 0) {
    savingsHeadline.textContent = 'Scenario B costs more than Scenario A';
    savingsValue.textContent = fmt(Math.abs(annualDiff));
    savingsValue.className = 'fs-3 fw-bold mpc-mono text-danger';
  } else {
    savingsHeadline.textContent = 'Scenario A and B are currently matched';
    savingsValue.textContent = fmt(0);
    savingsValue.className = 'fs-3 fw-bold mpc-mono text-gasq-muted';
  }

  clearTimeout(persistT);
  persistT = setTimeout(() => persistReportPayload(a, b), 400);
}

document.addEventListener('DOMContentLoaded', () => {
  hydrateSavedScenario('a', savedScenario?.a);
  hydrateSavedScenario('b', savedScenario?.b);
  document.querySelectorAll('input').forEach((el) => el.addEventListener('input', calculate));
  updateLabel('a');
  updateLabel('b');
  calculate();
});
</script>
@endpush
