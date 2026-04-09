@extends('layouts.app')
@section('title', 'Mobile Patrol Calculator')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .mp-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 30%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .mp-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .mp-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .mp-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .mp-section + .mp-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .mp-stat {
    border: 1px solid rgba(6, 45, 121, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .mp-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .mp-stat-value {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--gasq-primary);
  }
  .mp-panel {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .mp-panel-muted {
    background: rgba(6, 45, 121, 0.04);
  }
  .mp-chip {
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
  .mp-mono {
    font-variant-numeric: tabular-nums;
  }
  @media (max-width: 1199.98px) {
    .mp-sticky {
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
      <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-car text-primary"></i> Mobile Patrol Calculator
        </h1>
        <div class="text-gasq-muted small">Shared input rail with live patrol cost and bill-rate outputs</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('mobile-patrol-comparison') }}" class="btn btn-outline-primary btn-sm">
        <i class="fa fa-code-compare me-1"></i> Compare Scenarios
      </a>
      <button class="btn btn-outline-secondary btn-sm" onclick="resetToDefaults()">
        <i class="fa fa-rotate me-1"></i> Reset
      </button>
      <button class="btn btn-outline-secondary btn-sm d-print-none" onclick="window.print()">
        <i class="fa fa-print me-1"></i> Print
      </button>
      <button class="btn btn-outline-secondary btn-sm d-print-none" onclick="downloadPDF()">
        <i class="fa fa-download me-1"></i> Download PDF
      </button>
      <button class="btn btn-primary btn-sm d-print-none" onclick="sendEmail()">
        <i class="fa fa-envelope me-1"></i> Send Email
      </button>
    </div>
  </div>

  <div class="card gasq-card mp-shell overflow-hidden">
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-xl-4 border-end mp-sidebar">
          <div class="p-3 p-md-4 mp-sticky">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
              <div>
                <div class="mp-kicker mb-2">Shared Inputs</div>
                <h2 class="h4 fw-bold mb-2">Patrol Model Controls</h2>
                <p class="small text-gasq-muted mb-0">Contact details, route setup, and patrol assumptions all live here and update the results workspace on the right in real time.</p>
              </div>
              <span class="mp-chip"><i class="fa fa-bolt"></i> Live</span>
            </div>

            <div class="mp-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-address-card text-primary"></i>
                <h5 class="mb-0 fw-semibold">Contact Information</h5>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Customer Name</label>
                  <input type="text" id="customerName" class="form-control form-control-sm" placeholder="John Doe">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Company Name</label>
                  <input type="text" id="companyName" class="form-control form-control-sm" placeholder="ABC Security">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Email Address</label>
                  <input type="email" id="emailAddr" class="form-control form-control-sm" placeholder="john@example.com">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Phone Number</label>
                  <input type="tel" id="phoneNum" class="form-control form-control-sm" placeholder="(555) 123-4567">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-medium">CC Email (Second Recipient)</label>
                  <input type="email" id="ccEmail" class="form-control form-control-sm" placeholder="colleague@example.com">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-medium">Comments</label>
                  <textarea id="comments" class="form-control form-control-sm" rows="1" placeholder="Additional notes..."></textarea>
                </div>
              </div>
            </div>

            @php($mpMapsKey = config('services.google.maps_api_key'))
            @if($mpMapsKey)
            <div class="mp-section d-print-none">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-route text-primary"></i>
                <h5 class="mb-0 fw-semibold">Patrol Route</h5>
              </div>
              <div class="row g-3 align-items-end">
                <div class="col-12">
                  <label class="form-label small fw-medium">Patrol start / base</label>
                  <input type="text" id="mp-route-origin" class="form-control form-control-sm" placeholder="Start typing an address…" autocomplete="off">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-medium">Patrol end / turnaround</label>
                  <input type="text" id="mp-route-dest" class="form-control form-control-sm" placeholder="Destination address…" autocomplete="off">
                </div>
                <div class="col-12 d-flex flex-wrap gap-3 align-items-center">
                  <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="mp-route-roundtrip" checked>
                    <label class="form-check-label small" for="mp-route-roundtrip">Round trip (×2)</label>
                  </div>
                  <button type="button" class="btn btn-primary btn-sm" onclick="applyMpRouteMiles()">
                    <i class="fa fa-road me-1"></i> Apply to miles/day
                  </button>
                </div>
              </div>
              <p class="small text-gasq-muted mb-2 mt-2" id="mp-route-summary"></p>
              <div id="mp-route-map" class="rounded border mt-2" style="height: 260px; min-height: 200px; border-color: var(--gasq-border);"></div>
            </div>
            @else
            <div class="mp-section d-print-none">
              <div class="alert alert-light border gasq-border py-2 small mb-0">
                <i class="fa fa-map me-1 text-primary"></i>
                Set <code>GOOGLE_MAPS_API_KEY</code> in <code>.env</code> (with Maps JavaScript, Places, and Directions APIs) to show the patrol route map and auto-fill miles per day.
              </div>
            </div>
            @endif

            <div class="mp-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-sliders text-primary"></i>
                <h5 class="mb-0 fw-semibold">Submission Details</h5>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-medium">Patrol Type / Scenario</label>
                <select id="scenarioName" class="form-select form-select-sm" onchange="onPatrolTypeChange()">
                  <option value="8-hour|8">8-hour Mobile Patrol</option>
                  <option value="10-hour|10">10-hour Mobile Patrol</option>
                  <option value="12-hour|12">12-hour Mobile Patrol</option>
                  <option value="16-hour|16">16-hour Mobile Patrol</option>
                  <option value="24-hour|24" selected>24-hour Mobile Patrol</option>
                </select>
              </div>

              <div class="mp-section">
                <h6 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                  <i class="fa fa-clock text-primary"></i> Time Parameters
                </h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Hours per day coverage</label>
                    <input type="number" id="hoursPerDay" class="form-control form-control-sm" value="24" oninput="calculate()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Days per year</label>
                    <input type="number" id="daysPerYear" class="form-control form-control-sm" value="365" oninput="calculate()">
                  </div>
                </div>
              </div>

              <div class="mp-section">
                <h6 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                  <i class="fa fa-dollar-sign text-primary"></i> Labor Costs
                </h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Patrolman Gross Wage</label>
                    <input type="number" id="patrolmanHourlyWage" class="form-control form-control-sm" value="30.00" step="0.01" oninput="calculate()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Employer Full Burden Cost (%)</label>
                    <input type="number" id="payrollBurdenPercent" class="form-control form-control-sm" value="24" oninput="calculate()">
                  </div>
                </div>
              </div>

              <div class="mp-section">
                <h6 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                  <i class="fa fa-car text-primary"></i> Vehicle Costs
                </h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Vehicle annual finance cost ($)</label>
                    <input type="number" id="vehicleAnnualFinanceCost" class="form-control form-control-sm" value="7980.00" step="0.01" oninput="calculate()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Auto insurance annual ($)</label>
                    <input type="number" id="autoInsuranceAnnualCost" class="form-control form-control-sm" value="1500.00" step="0.01" oninput="calculate()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Annual repairs ($)</label>
                    <input type="number" id="annualRepairs" class="form-control form-control-sm" value="4000.00" step="0.01" oninput="calculate()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Tires annual cost ($)</label>
                    <input type="number" id="tiresAnnualCost" class="form-control form-control-sm" value="1200.00" step="0.01" oninput="calculate()">
                  </div>
                </div>
              </div>

              <div class="mp-section">
                <h6 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                  <i class="fa fa-gas-pump text-primary"></i> Fuel &amp; Mileage
                </h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Miles driven per day</label>
                    <input type="number" id="milesDrivenPerDay" class="form-control form-control-sm" value="360" oninput="calculate()">
                    @unless($mpMapsKey ?? null)
                      <div class="form-text">Use the route map above when a Maps API key is configured.</div>
                    @endunless
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Miles per gallon (MPG)</label>
                    <input type="number" id="milesPerGallon" class="form-control form-control-sm" value="20" oninput="calculate()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Fuel price per gallon ($)</label>
                    <input type="number" id="fuelPricePerGallon" class="form-control form-control-sm" value="2.57" step="0.01" oninput="calculate()">
                  </div>
                </div>
              </div>

              <div class="mp-section">
                <h6 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                  <i class="fa fa-wrench text-primary"></i> Maintenance
                </h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Oil change cost per service ($)</label>
                    <input type="number" id="oilChangeCostPerService" class="form-control form-control-sm" value="32" step="0.01" oninput="calculate()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Miles between oil changes</label>
                    <input type="number" id="milesBetweenOilChanges" class="form-control form-control-sm" value="6000" oninput="calculate()">
                  </div>
                </div>
              </div>

              <div class="mp-section">
                <label class="form-label small fw-medium">Markup % / Return on Sale</label>
                <input type="number" id="markupPercent" class="form-control form-control-sm" value="27" oninput="calculate()">
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="p-3 p-md-4">
            <div class="alert alert-light border gasq-border small d-print-none mb-3" id="mp_error" style="display:none"></div>

            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="mp-stat">
                  <div class="mp-stat-label mb-2">Hourly Billable Rate</div>
                  <div class="mp-stat-value mp-mono" id="r-hourlyRate-top">$0.00</div>
                  <div class="small text-gasq-muted">Live patrol billing target</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mp-stat">
                  <div class="mp-stat-label mb-2">Annual Cost with Markup</div>
                  <div class="mp-stat-value mp-mono" id="r-annualCost-top">$0.00</div>
                  <div class="small text-gasq-muted">Updated from the shared input rail</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mp-stat">
                  <div class="mp-stat-label mb-2">Hours per Year</div>
                  <div class="mp-stat-value mp-mono" id="r-hoursPerYear-top">0</div>
                  <div class="small text-gasq-muted">Coverage hours implied by current scenario</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <div class="mp-kicker mb-1">Results Workspace</div>
                <h3 class="h5 fw-bold mb-0">Mobile Patrol Outputs</h3>
              </div>
              <div class="small text-gasq-muted">The cost stack and billing summary below update from the shared patrol inputs on the left.</div>
            </div>

            <div class="row g-3">
              <div class="col-lg-6">
                <div class="mp-panel p-3 h-100">
                  <h5 class="fw-semibold mb-3">Time and Labor</h5>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Hours per year</span>
                    <span class="fw-medium mp-mono" id="r-hoursPerYear">8,760</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-gasq-muted small">Annual wage cost (with burden)</span>
                    <span class="fw-medium mp-mono" id="r-annualWageCost">$0.00</span>
                  </div>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="mp-panel mp-panel-muted p-3 h-100">
                  <h5 class="fw-semibold mb-3">Mileage and Fuel</h5>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Miles driven per year</span>
                    <span class="fw-medium mp-mono" id="r-milesDrivenPerYear">131,400</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Fuel gallons per year</span>
                    <span class="fw-medium mp-mono" id="r-fuelGallonsPerYear">0</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-gasq-muted small">Annual fuel cost</span>
                    <span class="fw-medium mp-mono" id="r-annualFuelCost">$0.00</span>
                  </div>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="mp-panel p-3 h-100">
                  <h5 class="fw-semibold mb-3">Maintenance</h5>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Number of oil changes per year</span>
                    <span class="fw-medium mp-mono" id="r-oilChangesPerYear">0.0</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-gasq-muted small">Annual oil change cost</span>
                    <span class="fw-medium mp-mono" id="r-annualOilChangeCost">$0.00</span>
                  </div>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="mp-panel mp-panel-muted p-3 h-100">
                  <h5 class="fw-semibold mb-3">Cost Summary</h5>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Total annual pre-markup cost</span>
                    <span class="fw-medium mp-mono" id="r-preMarkupCost">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Daily cost with markup</span>
                    <span class="fw-medium mp-mono" id="r-dailyCost">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Weekly cost with markup</span>
                    <span class="fw-medium mp-mono" id="r-weeklyCost">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Monthly cost with markup</span>
                    <span class="fw-medium mp-mono" id="r-monthlyCost">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-gasq-muted small">Annual cost with markup</span>
                    <span class="fw-semibold mp-mono" id="r-annualCost">$0.00</span>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="rounded p-4 text-white text-center" style="background:var(--gasq-primary)">
                  <div class="small mb-1" style="opacity:.85">Hourly Billable Rate</div>
                  <div class="display-5 fw-bold mp-mono" id="r-hourlyRate">$0.00</div>
                  <div class="small mt-1" style="opacity:.7">per hour</div>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <x-report-actions reportType="mobile-patrol" />
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
const DEFAULTS = {
  hoursPerDay: 24, daysPerYear: 365, patrolmanHourlyWage: 30.00, payrollBurdenPercent: 24,
  vehicleAnnualFinanceCost: 7980.00, milesDrivenPerDay: 360, milesPerGallon: 20,
  fuelPricePerGallon: 2.57, annualRepairs: 4000.00, tiresAnnualCost: 1200.00,
  oilChangeCostPerService: 32, milesBetweenOilChanges: 6000, autoInsuranceAnnualCost: 1500.00,
  markupPercent: 27
};

function fmt(v, dec=2){
  return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:dec,maximumFractionDigits:dec}).format(v);
}
function fmtN(v, dec=1){
  return new Intl.NumberFormat('en-US',{minimumFractionDigits:dec,maximumFractionDigits:dec}).format(v);
}
function g(id){ return parseFloat(document.getElementById(id).value)||0; }

function setError(msg){
  const el = document.getElementById('mp_error');
  if(!el) return;
  if(!msg){ el.style.display='none'; el.textContent=''; return; }
  el.style.display='';
  el.textContent = msg;
}

let mpInflight = null;
async function calculate(){
  const payload = {
    version: 'v24',
    scenario: {
      meta: {
        hoursPerDay: g('hoursPerDay'),
        daysPerYear: g('daysPerYear'),
        patrolmanHourlyWage: g('patrolmanHourlyWage'),
        payrollBurdenPercent: g('payrollBurdenPercent'),
        vehicleAnnualFinanceCost: g('vehicleAnnualFinanceCost'),
        milesDrivenPerDay: g('milesDrivenPerDay'),
        milesPerGallon: g('milesPerGallon'),
        fuelPricePerGallon: g('fuelPricePerGallon'),
        annualRepairs: g('annualRepairs'),
        tiresAnnualCost: g('tiresAnnualCost'),
        oilChangeCostPerService: g('oilChangeCostPerService'),
        milesBetweenOilChanges: g('milesBetweenOilChanges'),
        autoInsuranceAnnualCost: g('autoInsuranceAnnualCost'),
        markupPercent: g('markupPercent')
      }
    }
  };

  try{
    setError('');
    if (mpInflight) { mpInflight.abort(); }
    mpInflight = new AbortController();
    const res = await fetch('{{ route('backend.mobile-patrol.v24.compute') }}', {
      method: 'POST',
      signal: mpInflight.signal,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });
    let data = null;
    try { data = await res.json(); } catch { data = null; }
    if(!res.ok || !data || !data.ok){
      if (data && data.error === 'insufficient_credits') {
        setError(data.message || 'Not enough credits to run this calculator.');
      } else {
        setError('Unable to calculate right now. Please try again.');
      }
      console.error(data);
      return;
    }

    const out = data.kpis || {};

    setText('r-hoursPerYear', fmtN(out.hoursPerYear||0, 0));
    setText('r-hoursPerYear-top', fmtN(out.hoursPerYear||0, 0));
    setText('r-annualWageCost', fmt(out.annualWageCost||0));
    setText('r-milesDrivenPerYear', fmtN(out.milesDrivenPerYear||0, 0));
    setText('r-fuelGallonsPerYear', fmtN(out.fuelGallonsPerYear||0, 0));
    setText('r-annualFuelCost', fmt(out.annualFuelCost||0));
    setText('r-oilChangesPerYear', fmtN(out.oilChangesPerYear||0, 1));
    setText('r-annualOilChangeCost', fmt(out.annualOilChangeCost||0));
    setText('r-preMarkupCost', fmt(out.preMarkupCost||0));
    setText('r-dailyCost', fmt(out.dailyCost||0));
    setText('r-weeklyCost', fmt(out.weeklyCost||0));
    setText('r-monthlyCost', fmt(out.monthlyCost||0));
    setText('r-annualCost', fmt(out.annualCost||0));
    setText('r-annualCost-top', fmt(out.annualCost||0));
    setText('r-hourlyRate', fmt(out.hourlyBillableRate||0));
    setText('r-hourlyRate-top', fmt(out.hourlyBillableRate||0));
  } catch(e){
    if(e?.name === 'AbortError') return;
    console.error(e);
    setError('Unable to calculate right now. Please try again.');
  }
}

function setText(id, val){ const el=document.getElementById(id); if(el) el.textContent=val; }

function onPatrolTypeChange(){
  const sel = document.getElementById('scenarioName');
  const hours = parseInt((sel.value||'').split('|')[1]||24);
  document.getElementById('hoursPerDay').value = hours;
  calculate();
}

function resetToDefaults(){
  Object.entries(DEFAULTS).forEach(([k,v])=>{ const el=document.getElementById(k); if(el) el.value=v; });
  document.getElementById('scenarioName').value='24-hour|24';
  calculate();
}

function downloadPDF(){
  window.print();
}

function sendEmail(){
  const email = document.getElementById('emailAddr').value;
  if(!email){ alert('Please enter an email address first.'); return; }
  alert('PDF report would be emailed to: ' + email);
}

let mpTimer = null;
function scheduleCalculate(){ clearTimeout(mpTimer); mpTimer = setTimeout(calculate, 350); }

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input,select,textarea').forEach(el => el.addEventListener('input', scheduleCalculate));
  calculate();
});
</script>
@if(!empty($mpMapsKey))
<script>
window.initMobilePatrolMap = function () {
  var mapEl = document.getElementById('mp-route-map');
  if (!mapEl || !window.google || !google.maps) {
    return;
  }
  var map = new google.maps.Map(mapEl, {
    center: { lat: 39.8283, lng: -98.5795 },
    zoom: 4,
  });
  var directionsRenderer = new google.maps.DirectionsRenderer({ map: map, suppressMarkers: false });
  var directionsService = new google.maps.DirectionsService();
  var inputA = document.getElementById('mp-route-origin');
  var inputB = document.getElementById('mp-route-dest');
  if (!inputA || !inputB || !google.maps.places) {
    return;
  }
  var state = { placeA: null, placeB: null };
  var acA = new google.maps.places.Autocomplete(inputA, { fields: ['formatted_address', 'geometry', 'name'] });
  var acB = new google.maps.places.Autocomplete(inputB, { fields: ['formatted_address', 'geometry', 'name'] });
  acA.addListener('place_changed', function () {
    state.placeA = acA.getPlace();
  });
  acB.addListener('place_changed', function () {
    state.placeB = acB.getPlace();
  });
  window._mpPatrolRoute = {
    map: map,
    directionsRenderer: directionsRenderer,
    directionsService: directionsService,
    state: state,
  };
};

window.applyMpRouteMiles = function () {
  var ctx = window._mpPatrolRoute;
  if (!ctx || !window.google) {
    return;
  }
  var a = ctx.state.placeA;
  var b = ctx.state.placeB;
  if (!a || !b || !a.geometry || !a.geometry.location || !b.geometry || !b.geometry.location) {
    window.alert('Select both patrol addresses from the dropdown suggestions.');
    return;
  }
  var roundTripEl = document.getElementById('mp-route-roundtrip');
  var roundTrip = roundTripEl ? roundTripEl.checked : true;
  ctx.directionsService.route(
    {
      origin: a.geometry.location,
      destination: b.geometry.location,
      travelMode: google.maps.TravelMode.DRIVING,
    },
    function (result, status) {
      var summary = document.getElementById('mp-route-summary');
      if (status !== 'OK' || !result.routes || !result.routes[0]) {
        if (summary) {
          summary.textContent = 'Could not get driving directions: ' + status + '. Enable Directions API for your key.';
        } else {
          window.alert('Could not get driving directions: ' + status);
        }
        return;
      }
      ctx.directionsRenderer.setDirections(result);
      var leg = result.routes[0].legs[0];
      var milesOneWay = leg.distance.value / 1609.344;
      var milesDay = roundTrip ? milesOneWay * 2 : milesOneWay;
      var mpInput = document.getElementById('milesDrivenPerDay');
      if (mpInput) {
        mpInput.value = milesDay.toFixed(1);
      }
      if (summary) {
        summary.textContent =
          'Route: ' +
          leg.distance.text +
          ' one-way · Applied ' +
          milesDay.toFixed(1) +
          ' mi to “Miles driven per day”' +
          (roundTrip ? ' (round trip).' : ' (one-way).');
      }
      if (typeof calculate === 'function') {
        calculate();
      }
    }
  );
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ $mpMapsKey }}&libraries=places&callback=initMobilePatrolMap" async defer></script>
@endif
@endpush
