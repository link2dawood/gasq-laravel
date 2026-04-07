@extends('layouts.app')
@section('title', 'Master Inputs')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  /* Distinct, professional “control panel” look */
  .mi-wrap{
    --mi-surface: rgba(255,255,255,0.82);
    --mi-stroke: rgba(6,45,121,0.16);
    --mi-ink: #0b1220;
    --mi-muted: rgba(11,18,32,0.62);
    --mi-accent: #062d79;
    --mi-accent2: #6b0f1a;
    --mi-shadow: 0 18px 50px rgba(2,6,23,0.08);
    --mi-radius: 14px;
  }
  .mi-bg{
    background:
      radial-gradient(900px 380px at 10% -10%, rgba(6,45,121,0.14), transparent 60%),
      radial-gradient(700px 320px at 110% 10%, rgba(107,15,26,0.10), transparent 55%),
      linear-gradient(180deg, rgba(248,250,252,0.9), rgba(248,250,252,0.7));
  }
  .mi-hero{
    border: 1px solid var(--mi-stroke);
    background: linear-gradient(135deg, rgba(6,45,121,0.08), rgba(107,15,26,0.05));
    border-radius: var(--mi-radius);
    box-shadow: var(--mi-shadow);
    padding: 18px 18px;
  }
  .mi-title{
    font-family: ui-serif, "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
    letter-spacing: -0.02em;
    color: var(--mi-ink);
  }
  .mi-sub{ color: var(--mi-muted); }
  .mi-pill{
    display:inline-flex; align-items:center; gap:8px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid rgba(6,45,121,0.18);
    background: rgba(255,255,255,0.7);
    color: rgba(6,45,121,0.85);
    font-size: 12px;
    white-space: nowrap;
  }
  .mi-card{
    border-radius: var(--mi-radius);
    border: 1px solid var(--mi-stroke);
    background: var(--mi-surface);
    box-shadow: 0 10px 30px rgba(2,6,23,0.05);
    overflow: hidden;
  }
  .mi-card .card-header{
    background: transparent !important;
    border-bottom: 1px solid rgba(6,45,121,0.10) !important;
  }
  .mi-card h5{
    font-family: ui-serif, "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
    letter-spacing: -0.01em;
  }
  .mi-kbd{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 12px;
    padding: 2px 6px;
    border: 1px solid rgba(2,6,23,0.12);
    border-bottom-width: 2px;
    border-radius: 8px;
    background: rgba(255,255,255,0.65);
  }
  .mi-field{
    padding: 10px 12px;
    border: 1px solid rgba(2,6,23,0.08);
    border-radius: 12px;
    background: rgba(255,255,255,0.60);
  }
  .mi-label{
    display:flex; align-items:baseline; justify-content:space-between;
    gap: 10px;
    margin-bottom: 8px;
  }
  .mi-label .name{ font-weight: 650; color: var(--mi-ink); }
  .mi-label .hint{ color: var(--mi-muted); font-size: 12px; }
  .mi-duo{
    display:grid;
    grid-template-columns: 140px 1fr;
    gap: 10px;
    align-items:center;
  }
  @media (max-width: 575.98px){
    .mi-duo{ grid-template-columns: 1fr; }
  }
  .mi-input{
    border-radius: 12px !important;
    border: 1px solid rgba(2,6,23,0.12) !important;
    background: rgba(255,249,196,0.55) !important;
  }
  .mi-input:focus{
    border-color: rgba(6,45,121,0.38) !important;
    box-shadow: 0 0 0 0.18rem rgba(6,45,121,0.12) !important;
  }
  .mi-range{ width: 100%; }
  /* Custom slider styling (works well in modern browsers) */
  .mi-range{
    -webkit-appearance:none;
    appearance:none;
    height: 6px;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(6,45,121,0.35), rgba(6,45,121,0.12));
    outline: none;
  }
  .mi-range::-webkit-slider-thumb{
    -webkit-appearance:none;
    appearance:none;
    width: 18px; height: 18px;
    border-radius: 999px;
    background: radial-gradient(circle at 30% 30%, #ffffff, rgba(255,255,255,0.55));
    border: 2px solid rgba(6,45,121,0.70);
    box-shadow: 0 8px 18px rgba(2,6,23,0.18);
    cursor: pointer;
  }
  .mi-range::-moz-range-thumb{
    width: 18px; height: 18px;
    border-radius: 999px;
    background: #fff;
    border: 2px solid rgba(6,45,121,0.70);
    box-shadow: 0 8px 18px rgba(2,6,23,0.18);
    cursor: pointer;
  }
  .mi-actions .btn{
    border-radius: 999px;
    padding-left: 14px;
    padding-right: 14px;
  }
  .mi-save{
    color: var(--mi-muted);
    font-size: 12px;
  }
  .mi-save strong{ color: rgba(6,45,121,0.9); font-weight: 700; }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4 mi-bg mi-wrap">
<div class="container-xl">

  <div class="mi-hero d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="mi-pill"><i class="fa fa-file-excel"></i> Master dataset</span>
        <span class="mi-pill"><i class="fa fa-rotate"></i> Autosaves</span>
        <span class="mi-pill"><i class="fa fa-layer-group"></i> Reused everywhere</span>
      </div>
      <h1 class="h3 fw-bold mb-1 mi-title d-flex align-items-center gap-2">
        <i class="fa fa-sliders" style="color:var(--mi-accent)"></i> Master Inputs
      </h1>
      <div class="mi-sub">
        These are the shared drivers behind all calculators. Percent fields are edited as <span class="mi-kbd">%</span> but saved as decimals for formula parity.
      </div>
      <div class="mi-save mt-2" id="mi_save_state">Status: <strong>Ready</strong></div>
    </div>
    <div class="d-flex flex-wrap gap-2 mi-actions">
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetInputs()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <button type="button" class="btn btn-primary btn-sm" onclick="markComplete()"><i class="fa fa-check me-1"></i> Continue</button>
    </div>
  </div>

  <div class="alert alert-danger d-none" id="mi_err" role="alert"></div>
  <div class="alert alert-success d-none" id="mi_ok" role="alert"></div>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card mi-card">
        <div class="card-header py-3 px-3 px-md-4">
          <h5 class="card-title mb-0 fw-semibold d-flex align-items-center justify-content-between gap-2">
            <span><i class="fa fa-screwdriver-wrench me-2" style="color:var(--mi-accent)"></i> Core Controls</span>
            <span class="small" style="color:var(--mi-muted)">Base pay + core premiums</span>
          </h5>
        </div>
        <div class="card-body px-3 px-md-4">
          <div class="mi-field mb-3">
            <div class="mi-label">
              <div class="name">Direct Labor Wage</div>
              <div class="hint">$ / paid hour</div>
            </div>
            <div class="mi-duo">
              <input type="number" class="form-control mi-input" id="mi_directLaborWage" step="0.01">
              <input type="range" class="mi-range" min="0" max="150" step="0.01" data-sync="mi_directLaborWage">
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Annual Paid Hours per FTE</div><div class="hint">hours</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_annualPaidHoursPerFte" step="1" min="0">
                  <input type="range" class="mi-range" min="0" max="4000" step="1" data-sync="mi_annualPaidHoursPerFte">
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Annual Productive Coverage Hours per FTE</div><div class="hint">hours</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_annualProductiveCoverageHoursPerFte" step="1" min="0">
                  <input type="range" class="mi-range" min="0" max="4000" step="1" data-sync="mi_annualProductiveCoverageHoursPerFte">
                </div>
              </div>
            </div>
          </div>
          <hr>
          <div class="row g-3">
            @php
              $pctFields = [
                ['k'=>'localityPayPct','label'=>'Locality Pay %','max'=>0.5],
                ['k'=>'shiftDifferentialPct','label'=>'Shift Differential %','max'=>0.5],
                ['k'=>'otHolidayPremiumPct','label'=>'OT/Holiday Premium %','max'=>0.5],
                ['k'=>'laborMarketAdjPct','label'=>'Labor Market Adjustment %','max'=>0.5],
              ];
            @endphp
            @foreach($pctFields as $f)
              <div class="col-md-6">
                <div class="mi-field">
                  <div class="mi-label"><div class="name">{{ $f['label'] }}</div><div class="hint">percent</div></div>
                  <div class="mi-duo">
                    <div class="input-group">
                      <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.01" min="0" max="200" data-unit="pct">
                      <span class="input-group-text">%</span>
                    </div>
                    <input type="range" class="mi-range" min="0" max="{{ $f['max']*100 }}" step="0.01" data-sync="mi_{{ $f['k'] }}">
                  </div>
                  <div class="small" style="color:var(--mi-muted)">Saved as decimal for parity (e.g. 8% → 0.08).</div>
                </div>
              </div>
            @endforeach
          </div>
          <hr>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="mi-field">
                <div class="mi-label"><div class="name">H&amp;W Cash</div><div class="hint">$ / paid hour</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_hwCashPerHour" step="0.01" min="0">
                  <input type="range" class="mi-range" min="0" max="50" step="0.01" data-sync="mi_hwCashPerHour">
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mi-field">
                <div class="mi-label"><div class="name">DON/DOFF Minutes</div><div class="hint">per 8-hour shift</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_donDoffMinutesPerShift" step="1" min="0">
                  <input type="range" class="mi-range" min="0" max="120" step="1" data-sync="mi_donDoffMinutesPerShift">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mi-card">
        <div class="card-header py-3 px-3 px-md-4">
          <h5 class="card-title mb-0 fw-semibold d-flex align-items-center justify-content-between gap-2">
            <span><i class="fa fa-shield-halved me-2" style="color:var(--mi-accent2)"></i> Burden & Corporate Controls</span>
            <span class="small" style="color:var(--mi-muted)">Percent-driven overhead stack</span>
          </h5>
        </div>
        <div class="card-body px-3 px-md-4">
          @php
            $burden = [
              ['k'=>'ficaMedicarePct','label'=>'FICA / Medicare %','max'=>0.2],
              ['k'=>'futaPct','label'=>'FUTA %','max'=>0.05],
              ['k'=>'sutaPct','label'=>'SUTA %','max'=>0.2],
              ['k'=>'workersCompPct','label'=>'Workers Compensation %','max'=>0.2],
              ['k'=>'vacationPct','label'=>'Vacation %','max'=>0.2],
              ['k'=>'paidHolidaysPct','label'=>'Paid Holidays %','max'=>0.2],
              ['k'=>'sickLeavePct','label'=>'Sick Leave %','max'=>0.2],
              ['k'=>'generalLiabilityPct','label'=>'General Liability Insurance %','max'=>0.2],
              ['k'=>'umbrellaInsurancePct','label'=>'Umbrella / Other Insurance %','max'=>0.05],
              ['k'=>'corporateOverheadPct','label'=>'Corporate Overhead %','max'=>0.3],
              ['k'=>'gaPct','label'=>'G&A %','max'=>0.3],
              ['k'=>'profitFeePct','label'=>'Profit / Fee %','max'=>1.0],
            ];
          @endphp

          <div class="row g-3">
            @foreach($burden as $f)
              <div class="col-md-6">
                <div class="mi-field">
                  <div class="mi-label"><div class="name">{{ $f['label'] }}</div><div class="hint">percent</div></div>
                  <div class="mi-duo">
                    <div class="input-group">
                      <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.01" min="0" max="200" data-unit="pct">
                      <span class="input-group-text">%</span>
                    </div>
                    <input type="range" class="mi-range" min="0" max="{{ $f['max']*100 }}" step="0.01" data-sync="mi_{{ $f['k'] }}">
                  </div>
                </div>
              </div>
            @endforeach
            <div class="col-md-6">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Health &amp; Welfare</div><div class="hint">$ / paid hour</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_healthWelfarePerHour" step="0.01" min="0">
                  <input type="range" class="mi-range" min="0" max="50" step="0.01" data-sync="mi_healthWelfarePerHour">
                </div>
              </div>
            </div>
          </div>

          <hr>
          <div class="small" style="color:var(--mi-muted)">
            Autosave is continuous. Values are reused across all calculators unless a specific calculator overrides a field.
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mi-card">
        <div class="card-header py-3 px-3 px-md-4">
          <h5 class="card-title mb-0 fw-semibold d-flex align-items-center justify-content-between gap-2">
            <span><i class="fa fa-sitemap me-2" style="color:rgba(6,45,121,0.85)"></i> Operations, Factors & Vehicle Drivers</span>
            <span class="small" style="color:var(--mi-muted)">Inputs reused everywhere</span>
          </h5>
        </div>
        <div class="card-body px-3 px-md-4">

          <div class="small text-uppercase fw-semibold mb-2" style="color:rgba(6,45,121,0.75);letter-spacing:.08em">Operations / Support</div>
          <div class="row g-3">
            @php
              $ops = [
                ['k'=>'recruitingHiringPct','label'=>'Recruiting / Hiring %','max'=>0.2],
                ['k'=>'trainingCertificationPct','label'=>'Training / Certification %','max'=>0.2],
                ['k'=>'uniformsEquipmentPct','label'=>'Uniforms / Equipment %','max'=>0.2],
                ['k'=>'fieldSupervisionPct','label'=>'Field Supervision %','max'=>0.2],
                ['k'=>'contractManagementPct','label'=>'Contract Management %','max'=>0.2],
                ['k'=>'qualityAssurancePct','label'=>'Quality Assurance %','max'=>0.2],
                ['k'=>'vehiclesPatrolPct','label'=>'Vehicles / Patrol %','max'=>0.3],
                ['k'=>'technologySystemsPct','label'=>'Technology / Systems %','max'=>0.2],
              ];
            @endphp
            @foreach($ops as $f)
              <div class="col-12">
                <div class="mi-field">
                  <div class="mi-label"><div class="name">{{ $f['label'] }}</div><div class="hint">percent</div></div>
                  <div class="mi-duo">
                    <div class="input-group">
                      <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.01" min="0" max="200" data-unit="pct">
                      <span class="input-group-text">%</span>
                    </div>
                    <input type="range" class="mi-range" min="0" max="{{ $f['max']*100 }}" step="0.01" data-sync="mi_{{ $f['k'] }}">
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          <hr class="my-4">
          <div class="small text-uppercase fw-semibold mb-2" style="color:rgba(6,45,121,0.75);letter-spacing:.08em">Vendor / Government Factors</div>
          <div class="row g-3">
            @php
              $factorsPct = [
                ['k'=>'vendorTcoFactorVsGovTco','label'=>'Vendor TCO Factor vs Gov TCO %','max'=>1.2],
                ['k'=>'vendorFloorFactorVsVendorTco','label'=>'Vendor Floor Factor vs Vendor TCO %','max'=>1.2],
                ['k'=>'governmentFullBurdenLaborShare','label'=>'Government Full Burden Labor Share %','max'=>1.0],
              ];
            @endphp
            @foreach($factorsPct as $f)
              <div class="col-12">
                <div class="mi-field">
                  <div class="mi-label"><div class="name">{{ $f['label'] }}</div><div class="hint">percent</div></div>
                  <div class="mi-duo">
                    <div class="input-group">
                      <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.1" min="0" max="200" data-unit="pct">
                      <span class="input-group-text">%</span>
                    </div>
                    <input type="range" class="mi-range" min="0" max="{{ $f['max']*100 }}" step="0.1" data-sync="mi_{{ $f['k'] }}">
                  </div>
                </div>
              </div>
            @endforeach

            <div class="col-12">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Minimum Weekly Hours for Floor Eligibility</div><div class="hint">hours</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_minWeeklyHoursForFloorEligibility" step="1" min="0">
                  <input type="range" class="mi-range" min="0" max="6000" step="1" data-sync="mi_minWeeklyHoursForFloorEligibility">
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Government Workforce Hours Basis</div><div class="hint">hours</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_governmentWorkforceHoursBasis" step="1" min="0">
                  <input type="range" class="mi-range" min="0" max="12000" step="1" data-sync="mi_governmentWorkforceHoursBasis">
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Government TCO Multiplier (Min)</div><div class="hint">x</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_governmentTcoMultiplierMin" step="0.1" min="0">
                  <input type="range" class="mi-range" min="0" max="10" step="0.1" data-sync="mi_governmentTcoMultiplierMin">
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Government TCO Multiplier (Max)</div><div class="hint">x</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_governmentTcoMultiplierMax" step="0.1" min="0">
                  <input type="range" class="mi-range" min="0" max="10" step="0.1" data-sync="mi_governmentTcoMultiplierMax">
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4">
          <div class="small text-uppercase fw-semibold mb-2" style="color:rgba(6,45,121,0.75);letter-spacing:.08em">Vehicle Tab Drivers</div>
          <div class="row g-3">
            <div class="col-12">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Vehicles Required</div><div class="hint">count</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_vehiclesRequired" step="1" min="0">
                  <input type="range" class="mi-range" min="0" max="50" step="1" data-sync="mi_vehiclesRequired">
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Avg Miles per Vehicle per Day</div><div class="hint">miles</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_avgMilesPerVehiclePerDay" step="1" min="0">
                  <input type="range" class="mi-range" min="0" max="1000" step="1" data-sync="mi_avgMilesPerVehiclePerDay">
                </div>
              </div>
            </div>
            <div class="col-12">
              <div class="mi-field">
                <div class="mi-label"><div class="name">Fuel Cost per Gallon</div><div class="hint">$</div></div>
                <div class="mi-duo">
                  <input type="number" class="form-control mi-input" id="mi_fuelCostPerGallon" step="0.01" min="0">
                  <input type="range" class="mi-range" min="0" max="15" step="0.01" data-sync="mi_fuelCostPerGallon">
                </div>
              </div>
            </div>

            @php
              $esc = [
                ['k'=>'customAnnualEscalationPct','label'=>'Custom Annual Escalation %','max'=>0.25],
                ['k'=>'lowEscalationPct','label'=>'Low Escalation %','max'=>0.25],
                ['k'=>'mediumEscalationPct','label'=>'Medium Escalation %','max'=>0.25],
                ['k'=>'highEscalationPct','label'=>'High Escalation %','max'=>0.25],
              ];
            @endphp
            @foreach($esc as $f)
              <div class="col-12">
                <div class="mi-field">
                  <div class="mi-label"><div class="name">{{ $f['label'] }}</div><div class="hint">percent</div></div>
                  <div class="mi-duo">
                    <div class="input-group">
                      <input type="number" class="form-control mi-input" id="mi_{{ $f['k'] }}" step="0.1" min="0" max="200" data-unit="pct">
                      <span class="input-group-text">%</span>
                    </div>
                    <input type="range" class="mi-range" min="0" max="{{ $f['max']*100 }}" step="0.1" data-sync="mi_{{ $f['k'] }}">
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          <hr class="my-4">
          <div class="small" style="color:var(--mi-muted)">
            Tip: You can edit these at any time. Changes propagate across calculators on the next compute run.
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

