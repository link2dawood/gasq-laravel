@extends('layouts.app')
@section('title', 'Master Inputs')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  /* Simple, enterprise-grade settings UI */
  .mi-page { background: var(--gasq-background); }
  .mi-sticky {
    position: sticky;
    top: 0;
    z-index: 20;
    background: rgba(248,250,252,0.92);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(2, 6, 23, 0.08);
  }
  .mi-header {
    padding: 14px 0;
  }
  .mi-title {
    letter-spacing: -0.015em;
  }
  .mi-sub { color: var(--gasq-muted); }
  .mi-status { font-size: 12px; color: var(--gasq-muted); }
  .mi-card {
    border: 1px solid rgba(2, 6, 23, 0.10);
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 10px 30px rgba(2,6,23,0.04);
  }
  .mi-field {
    padding: 12px 12px;
    border: 1px solid rgba(2,6,23,0.10);
    border-radius: 10px;
    background: rgba(248,250,252,0.60);
  }
  .mi-row {
    display: grid;
    grid-template-columns: 1fr 140px;
    gap: 12px;
    align-items: center;
  }
  @media (max-width: 575.98px){
    .mi-row { grid-template-columns: 1fr; }
  }
  .mi-label { margin: 0; font-weight: 600; }
  .mi-help { font-size: 12px; color: var(--gasq-muted); margin-top: 2px; }
  .mi-input {
    background: #fff9c4 !important;
    border-color: rgba(2,6,23,0.14) !important;
    border-radius: 10px !important;
  }
  .mi-input:focus{
    border-color: rgba(6,45,121,0.35) !important;
    box-shadow: 0 0 0 0.18rem rgba(6,45,121,0.10) !important;
  }
  .mi-slider { width: 100%; }
  .mi-section-title{
    font-weight: 700;
    font-size: 12px;
    letter-spacing: 0.08em;
    color: rgba(6,45,121,0.85);
    text-transform: uppercase;
    margin-bottom: 10px;
  }
  .accordion-button { font-weight: 650; }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4 mi-page">
<div class="container-xl">

  <div class="mi-sticky">
    <div class="container-xl mi-header">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h1 class="h4 fw-bold mb-1 mi-title"><i class="fa fa-sliders text-primary me-2"></i>Master Inputs</h1>
          <div class="mi-sub">Shared settings used across calculators. Percent fields are shown as % and saved as decimals automatically.</div>
          <div class="mi-status mt-1" id="mi_save_state">Status: <strong>Ready</strong></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetInputs()"><i class="fa fa-rotate me-1"></i>Reset</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="markComplete()"><i class="fa fa-check me-1"></i>Continue</button>
        </div>
      </div>
    </div>
  </div>

  <div class="alert alert-danger d-none" id="mi_err" role="alert"></div>
  <div class="alert alert-success d-none" id="mi_ok" role="alert"></div>

  <div class="row g-4 pt-3">
    <div class="col-12">
      <div class="accordion" id="miAccordion">
        <div class="accordion-item mi-card mb-3">
          <h2 class="accordion-header" id="miHeadCore">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#miCore" aria-expanded="true" aria-controls="miCore">
              Core controls
            </button>
          </h2>
          <div id="miCore" class="accordion-collapse collapse show" aria-labelledby="miHeadCore" data-bs-parent="#miAccordion">
            <div class="accordion-body">
              <div class="row g-3">
                <div class="col-lg-6">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Direct Labor Wage</label>
                        <div class="mi-help">$ / paid hour</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_directLaborWage" step="0.01">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="150" step="0.01" data-sync="mi_directLaborWage">
                  </div>
                </div>
                <div class="col-lg-3">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Annual Paid Hours per FTE</label>
                        <div class="mi-help">hours</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_annualPaidHoursPerFte" step="1" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="4000" step="1" data-sync="mi_annualPaidHoursPerFte">
                  </div>
                </div>
                <div class="col-lg-3">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Annual Productive Coverage Hours per FTE</label>
                        <div class="mi-help">hours</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_annualProductiveCoverageHoursPerFte" step="1" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="4000" step="1" data-sync="mi_annualProductiveCoverageHoursPerFte">
                  </div>
                </div>
              </div>

              <hr class="my-4">
              <div class="mi-section-title">Premiums</div>
              <div class="row g-3">
                @php
                  $pctFields = [
                    ['k'=>'localityPayPct','label'=>'Locality Pay','max'=>0.5],
                    ['k'=>'shiftDifferentialPct','label'=>'Shift Differential','max'=>0.5],
                    ['k'=>'otHolidayPremiumPct','label'=>'OT/Holiday Premium','max'=>0.5],
                    ['k'=>'laborMarketAdjPct','label'=>'Labor Market Adjustment','max'=>0.5],
                  ];
                @endphp
                @foreach($pctFields as $f)
                  <div class="col-md-6 col-lg-3">
                    <div class="mi-field">
                      <div class="mi-row">
                        <div>
                          <label class="mi-label">{{ $f['label'] }}</label>
                          <div class="mi-help">%</div>
                        </div>
                        <div class="input-group">
                          <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.01" min="0" max="200" data-unit="pct">
                          <span class="input-group-text">%</span>
                        </div>
                      </div>
                      <input type="range" class="form-range mi-slider mt-2" min="0" max="{{ $f['max']*100 }}" step="0.01" data-sync="mi_{{ $f['k'] }}">
                    </div>
                  </div>
                @endforeach
              </div>

              <hr class="my-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">H&amp;W Cash</label>
                        <div class="mi-help">$ / paid hour</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_hwCashPerHour" step="0.01" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="50" step="0.01" data-sync="mi_hwCashPerHour">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">DON/DOFF Minutes</label>
                        <div class="mi-help">per 8-hour shift</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_donDoffMinutesPerShift" step="1" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="120" step="1" data-sync="mi_donDoffMinutesPerShift">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion-item mi-card mb-3">
          <h2 class="accordion-header" id="miHeadBurden">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#miBurden" aria-expanded="false" aria-controls="miBurden">
              Fringe / burden &amp; corporate controls
            </button>
          </h2>
          <div id="miBurden" class="accordion-collapse collapse" aria-labelledby="miHeadBurden" data-bs-parent="#miAccordion">
            <div class="accordion-body">
              @php
                $burden = [
                  ['k'=>'ficaMedicarePct','label'=>'FICA / Medicare','max'=>0.2],
                  ['k'=>'futaPct','label'=>'FUTA','max'=>0.05],
                  ['k'=>'sutaPct','label'=>'SUTA','max'=>0.2],
                  ['k'=>'workersCompPct','label'=>'Workers Compensation','max'=>0.2],
                  ['k'=>'vacationPct','label'=>'Vacation','max'=>0.2],
                  ['k'=>'paidHolidaysPct','label'=>'Paid Holidays','max'=>0.2],
                  ['k'=>'sickLeavePct','label'=>'Sick Leave','max'=>0.2],
                  ['k'=>'generalLiabilityPct','label'=>'General Liability Insurance','max'=>0.2],
                  ['k'=>'umbrellaInsurancePct','label'=>'Umbrella / Other Insurance','max'=>0.05],
                  ['k'=>'corporateOverheadPct','label'=>'Corporate Overhead','max'=>0.3],
                  ['k'=>'gaPct','label'=>'G&A','max'=>0.3],
                  ['k'=>'profitFeePct','label'=>'Profit / Fee','max'=>1.0],
                ];
              @endphp

              <div class="row g-3">
                @foreach($burden as $f)
                  <div class="col-md-6 col-lg-4">
                    <div class="mi-field">
                      <div class="mi-row">
                        <div>
                          <label class="mi-label">{{ $f['label'] }}</label>
                          <div class="mi-help">%</div>
                        </div>
                        <div class="input-group">
                          <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.01" min="0" max="200" data-unit="pct">
                          <span class="input-group-text">%</span>
                        </div>
                      </div>
                      <input type="range" class="form-range mi-slider mt-2" min="0" max="{{ $f['max']*100 }}" step="0.01" data-sync="mi_{{ $f['k'] }}">
                    </div>
                  </div>
                @endforeach
                <div class="col-md-6 col-lg-4">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Health &amp; Welfare</label>
                        <div class="mi-help">$ / paid hour</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_healthWelfarePerHour" step="0.01" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="50" step="0.01" data-sync="mi_healthWelfarePerHour">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion-item mi-card">
          <h2 class="accordion-header" id="miHeadOps">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#miOps" aria-expanded="false" aria-controls="miOps">
              Operations / support + vendor/government factors + vehicle drivers
            </button>
          </h2>
          <div id="miOps" class="accordion-collapse collapse" aria-labelledby="miHeadOps" data-bs-parent="#miAccordion">
            <div class="accordion-body">
              <!-- We keep your existing third panel fields, but now in a single, simple accordion section -->
              <div class="mi-section-title">Operations / support</div>
              <div class="row g-3">
                @php
                  $ops = [
                    ['k'=>'recruitingHiringPct','label'=>'Recruiting / Hiring','max'=>0.2],
                    ['k'=>'trainingCertificationPct','label'=>'Training / Certification','max'=>0.2],
                    ['k'=>'uniformsEquipmentPct','label'=>'Uniforms / Equipment','max'=>0.2],
                    ['k'=>'fieldSupervisionPct','label'=>'Field Supervision','max'=>0.2],
                    ['k'=>'contractManagementPct','label'=>'Contract Management','max'=>0.2],
                    ['k'=>'qualityAssurancePct','label'=>'Quality Assurance','max'=>0.2],
                    ['k'=>'vehiclesPatrolPct','label'=>'Vehicles / Patrol','max'=>0.3],
                    ['k'=>'technologySystemsPct','label'=>'Technology / Systems','max'=>0.2],
                  ];
                @endphp
                @foreach($ops as $f)
                  <div class="col-md-6 col-lg-3">
                    <div class="mi-field">
                      <div class="mi-row">
                        <div>
                          <label class="mi-label">{{ $f['label'] }}</label>
                          <div class="mi-help">%</div>
                        </div>
                        <div class="input-group">
                          <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.01" min="0" max="200" data-unit="pct">
                          <span class="input-group-text">%</span>
                        </div>
                      </div>
                      <input type="range" class="form-range mi-slider mt-2" min="0" max="{{ $f['max']*100 }}" step="0.01" data-sync="mi_{{ $f['k'] }}">
                    </div>
                  </div>
                @endforeach
              </div>

              <hr class="my-4">
              <div class="mi-section-title">Vendor / government factors</div>
              <div class="row g-3">
                @php
                  $factorsPct = [
                    ['k'=>'vendorTcoFactorVsGovTco','label'=>'Vendor TCO Factor vs Gov TCO','max'=>1.2],
                    ['k'=>'vendorFloorFactorVsVendorTco','label'=>'Vendor Floor Factor vs Vendor TCO','max'=>1.2],
                    ['k'=>'governmentFullBurdenLaborShare','label'=>'Government Full Burden Labor Share','max'=>1.0],
                  ];
                @endphp
                @foreach($factorsPct as $f)
                  <div class="col-md-6 col-lg-4">
                    <div class="mi-field">
                      <div class="mi-row">
                        <div>
                          <label class="mi-label">{{ $f['label'] }}</label>
                          <div class="mi-help">%</div>
                        </div>
                        <div class="input-group">
                          <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.1" min="0" max="200" data-unit="pct">
                          <span class="input-group-text">%</span>
                        </div>
                      </div>
                      <input type="range" class="form-range mi-slider mt-2" min="0" max="{{ $f['max']*100 }}" step="0.1" data-sync="mi_{{ $f['k'] }}">
                    </div>
                  </div>
                @endforeach

                <div class="col-md-6 col-lg-4">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Minimum Weekly Hours for Floor Eligibility</label>
                        <div class="mi-help">hours</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_minWeeklyHoursForFloorEligibility" step="1" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="6000" step="1" data-sync="mi_minWeeklyHoursForFloorEligibility">
                  </div>
                </div>
                <div class="col-md-6 col-lg-4">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Government Workforce Hours Basis</label>
                        <div class="mi-help">hours</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_governmentWorkforceHoursBasis" step="1" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="12000" step="1" data-sync="mi_governmentWorkforceHoursBasis">
                  </div>
                </div>
                <div class="col-md-6 col-lg-4">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Government TCO Multiplier (Min)</label>
                        <div class="mi-help">x</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_governmentTcoMultiplierMin" step="0.1" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="10" step="0.1" data-sync="mi_governmentTcoMultiplierMin">
                  </div>
                </div>
                <div class="col-md-6 col-lg-4">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Government TCO Multiplier (Max)</label>
                        <div class="mi-help">x</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_governmentTcoMultiplierMax" step="0.1" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="10" step="0.1" data-sync="mi_governmentTcoMultiplierMax">
                  </div>
                </div>
              </div>

              <hr class="my-4">
              <div class="mi-section-title">Vehicle tab drivers</div>
              <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Vehicles Required</label>
                        <div class="mi-help">count</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_vehiclesRequired" step="1" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="50" step="1" data-sync="mi_vehiclesRequired">
                  </div>
                </div>
                <div class="col-md-6 col-lg-3">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Avg Miles per Vehicle per Day</label>
                        <div class="mi-help">miles</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_avgMilesPerVehiclePerDay" step="1" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="1000" step="1" data-sync="mi_avgMilesPerVehiclePerDay">
                  </div>
                </div>
                <div class="col-md-6 col-lg-3">
                  <div class="mi-field">
                    <div class="mi-row">
                      <div>
                        <label class="mi-label">Fuel Cost per Gallon</label>
                        <div class="mi-help">$</div>
                      </div>
                      <input type="number" class="form-control mi-input" id="mi_fuelCostPerGallon" step="0.01" min="0">
                    </div>
                    <input type="range" class="form-range mi-slider mt-2" min="0" max="15" step="0.01" data-sync="mi_fuelCostPerGallon">
                  </div>
                </div>
                @php
                  $esc = [
                    ['k'=>'customAnnualEscalationPct','label'=>'Custom Annual Escalation','max'=>0.25],
                    ['k'=>'lowEscalationPct','label'=>'Low Escalation','max'=>0.25],
                    ['k'=>'mediumEscalationPct','label'=>'Medium Escalation','max'=>0.25],
                    ['k'=>'highEscalationPct','label'=>'High Escalation','max'=>0.25],
                  ];
                @endphp
                @foreach($esc as $f)
                  <div class="col-md-6 col-lg-3">
                    <div class="mi-field">
                      <div class="mi-row">
                        <div>
                          <label class="mi-label">{{ $f['label'] }}</label>
                          <div class="mi-help">%</div>
                        </div>
                        <div class="input-group">
                          <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.1" min="0" max="200" data-unit="pct">
                          <span class="input-group-text">%</span>
                        </div>
                      </div>
                      <input type="range" class="form-range mi-slider mt-2" min="0" max="{{ $f['max']*100 }}" step="0.1" data-sync="mi_{{ $f['k'] }}">
                    </div>
                  </div>
                @endforeach
              </div>
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
(() => {
  const DEFAULTS = @json($inputs ?? []);
  const apiUrl = @json(route('api.master-inputs.update'));
  const showUrl = @json(route('api.master-inputs.show'));
  let saveT = null;
  let inflight = null;
  let lastSavedAt = null;

  const setErr = (msg) => {
    const el = document.getElementById('mi_err');
    el.textContent = msg || '';
    el.classList.toggle('d-none', !msg);
  };
  const flashOk = (msg) => {
    const el = document.getElementById('mi_ok');
    el.textContent = msg || '';
    el.classList.remove('d-none');
    setTimeout(()=> el.classList.add('d-none'), 1200);
  };

  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

  const setSaveState = (state) => {
    const el = document.getElementById('mi_save_state');
    if(!el) return;
    if(state === 'saving') el.innerHTML = 'Status: <strong>Saving…</strong>';
    if(state === 'saved') {
      const t = lastSavedAt ? lastSavedAt.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '';
      el.innerHTML = 'Status: <strong>Saved</strong>' + (t ? ` <span class="mi-sub">(${t})</span>` : '');
    }
    if(state === 'ready') el.innerHTML = 'Status: <strong>Ready</strong>';
    if(state === 'error') el.innerHTML = 'Status: <strong style="color:#b42318">Save failed</strong>';
  };

  function initSliderSync(){
    document.querySelectorAll('input[type="range"][data-sync]').forEach((rangeEl)=>{
      const id = rangeEl.getAttribute('data-sync');
      const numEl = document.getElementById(id);
      if(!numEl) return;

      const syncRangeFromNumber = () => {
        const min = parseFloat(rangeEl.min || '0');
        const max = parseFloat(rangeEl.max || '100');
        const v = parseFloat(numEl.value || rangeEl.value || '0');
        rangeEl.value = String(clamp(v, min, max));
      };
      const syncNumberFromRange = () => { numEl.value = rangeEl.value; };

      syncRangeFromNumber();

      rangeEl.addEventListener('input', () => {
        syncNumberFromRange();
        scheduleSave();
      });
      numEl.addEventListener('input', () => {
        syncRangeFromNumber();
        scheduleSave();
      });
    });
  }

  const PERCENT_KEYS = new Set([
    'localityPayPct','shiftDifferentialPct','otHolidayPremiumPct','laborMarketAdjPct',
    'ficaMedicarePct','futaPct','sutaPct','workersCompPct','vacationPct','paidHolidaysPct',
    'sickLeavePct','recruitingHiringPct','trainingCertificationPct','uniformsEquipmentPct',
    'fieldSupervisionPct','contractManagementPct','qualityAssurancePct','vehiclesPatrolPct',
    'technologySystemsPct','generalLiabilityPct','umbrellaInsurancePct',
    'adminHrPayrollPct','accountingLegalPct','corporateOverheadPct','gaPct','profitFeePct',
    'vendorTcoFactorVsGovTco','vendorFloorFactorVsVendorTco','governmentFullBurdenLaborShare',
    'customAnnualEscalationPct','lowEscalationPct','mediumEscalationPct','highEscalationPct'
  ]);

  function collectInputs(){
    const out = {};
    Object.keys(DEFAULTS).forEach((k)=>{
      const el = document.getElementById('mi_'+k);
      if(!el) return;
      const v = parseFloat(el.value);
      if(!Number.isFinite(v)) { out[k] = DEFAULTS[k]; return; }
      // Percent fields are edited as percentages (e.g. 7.65) but saved as decimals (0.0765).
      out[k] = PERCENT_KEYS.has(k) ? (v / 100.0) : v;
    });
    return out;
  }

  function fillFormFromInputs(inputs){
    Object.entries(inputs||{}).forEach(([k,v])=>{
      const el = document.getElementById('mi_'+k);
      if(!el) return;
      if(PERCENT_KEYS.has(k) && typeof v === 'number') el.value = (v * 100.0);
      else el.value = v;
    });
  }

  async function saveNow(isComplete = null){
    try{
      setErr('');
      setSaveState('saving');
      if(inflight){ inflight.abort(); }
      inflight = new AbortController();
      const body = { inputs: collectInputs() };
      if(isComplete !== null){ body.is_complete = !!isComplete; }
      const res = await fetch(apiUrl, {
        method: 'PUT',
        signal: inflight.signal,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(body),
      });
      const data = await res.json().catch(()=>null);
      if(!res.ok || !data || !data.ok){
        setErr((data && data.message) ? data.message : 'Unable to save inputs right now.');
        setSaveState('error');
        return false;
      }
      lastSavedAt = new Date();
      setSaveState('saved');
      return true;
    }catch(e){
      if(e?.name === 'AbortError') return false;
      setErr('Unable to save inputs right now.');
      setSaveState('error');
      return false;
    }
  }

  function scheduleSave(){
    clearTimeout(saveT);
    setSaveState('saving');
    saveT = setTimeout(()=>{ void saveNow(); }, 350);
  }

  async function loadFromServer(){
    try{
      const res = await fetch(showUrl, { headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(()=>null);
      if(!res.ok || !data || !data.ok) return;
      const inputs = data.inputs || {};
      fillFormFromInputs(inputs);
      setSaveState('ready');
    }catch(e){
      return;
    }
  }

  window.resetInputs = async function(){
    fillFormFromInputs(DEFAULTS);
    initSliderSync();
    const ok = await saveNow(false);
    if(ok) flashOk('Reset saved');
  };

  window.markComplete = async function(){
    const ok = await saveNow(true);
    if(ok){
      flashOk('Saved. Redirecting...');
      window.location.href = @json(route('calculator.index'));
    }
  };

  document.addEventListener('DOMContentLoaded', async ()=>{
    // Prefill from server (in case defaults changed or profile already exists)
    await loadFromServer();
    initSliderSync();
    // If not filled server-side, fall back to defaults
    Object.entries(DEFAULTS).forEach(([k,v])=>{
      const el = document.getElementById('mi_'+k);
      if(!el) return;
      if(el.value === '' || el.value === null){
        if(PERCENT_KEYS.has(k) && typeof v === 'number') el.value = (v * 100.0);
        else el.value = v;
      }
    });
    initSliderSync();
    setSaveState('ready');
  });
})();
</script>
@endpush

