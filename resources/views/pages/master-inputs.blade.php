@extends('layouts.app')
@section('title', 'Master Inputs')
@section('header_variant', 'dashboard')

@push('styles')
<style>
/* ── Page-specific styles (uses existing gasq-theme.css variables) ── */

/* Page header bar */
.mi-page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 1.5rem;
}

.mi-shell {
  background:
    radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 26%),
    linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid var(--gasq-border);
  border-radius: 1.25rem;
  overflow: hidden;
  box-shadow: var(--gasq-shadow-card);
}

.mi-sidebar {
  background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
}

.mi-results {
  background: #fff;
}

.mi-sticky {
  position: sticky;
  top: 1.25rem;
}

.mi-kicker {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--gasq-muted);
}

.mi-stat {
  border: 1px solid rgba(6,45,121,0.08);
  border-radius: 1rem;
  padding: 1rem;
  background: #fff;
}

.mi-stat-label {
  font-size: 0.76rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--gasq-muted);
}

.mi-stat-value {
  font-size: 1.45rem;
  font-weight: 700;
  color: var(--gasq-primary);
}

/* Save status badge */
.mi-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  color: var(--gasq-muted);
  background: var(--gasq-muted-bg);
  border: 1px solid var(--gasq-border);
  border-radius: 100px;
  padding: 4px 12px;
  font-weight: 500;
}

.mi-status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--gasq-muted);
  transition: background 0.2s, box-shadow 0.2s;
  flex-shrink: 0;
}
.mi-status-dot.saving { background: var(--gasq-accent); box-shadow: 0 0 0 3px hsla(45,100%,60%,0.2); animation: mi-pulse 0.8s ease-in-out infinite; }
.mi-status-dot.saved  { background: var(--gasq-success); box-shadow: 0 0 0 3px hsla(145,70%,45%,0.2); }
.mi-status-dot.error  { background: var(--gasq-destructive); box-shadow: 0 0 0 3px hsla(0,84%,60%,0.2); }

@keyframes mi-pulse {
  0%,100% { opacity: 1; } 50% { opacity: 0.3; }
}

/* Section label divider */
.mi-section-label {
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.10em;
  text-transform: uppercase;
  color: var(--gasq-primary);
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 1.75rem 0 1rem;
}
.mi-section-label:first-of-type { margin-top: 0; }
.mi-section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--gasq-border);
}

/* Field row: label left, input right */
.mi-field-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}

.mi-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--gasq-foreground);
  margin: 0 0 2px;
  line-height: 1.3;
}
.mi-help {
  font-size: 0.75rem;
  color: var(--gasq-muted);
}

/* Number input */
.mi-number-input {
  width: 140px !important;
  min-height: 48px !important;
  font-size: 1.05rem !important;
  font-weight: 600 !important;
  font-family: ui-monospace, 'SFMono-Regular', 'Courier New', monospace !important;
  text-align: right !important;
  color: var(--gasq-foreground) !important;
  background: var(--gasq-card) !important;
  border: 1px solid var(--gasq-border) !important;
  border-radius: var(--gasq-radius) !important;
  padding: 10px 14px !important;
  transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
  appearance: auto !important;
  -webkit-appearance: auto !important;
  -moz-appearance: auto !important;
}
.mi-number-input:focus {
  outline: none !important;
  border-color: var(--gasq-primary) !important;
  box-shadow: 0 0 0 0.2rem rgba(6,45,121,0.12) !important;
  background: #fff !important;
}

.mi-unit-note {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--gasq-muted);
  margin-top: 0.45rem;
}

/* Slider */
.mi-range {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 4px;
  background: var(--gasq-border);
  border-radius: 2px;
  outline: none;
  cursor: pointer;
  --pct: 0%;
  background: linear-gradient(to right,
    var(--gasq-primary) 0%, var(--gasq-primary) var(--pct),
    var(--gasq-border) var(--pct), var(--gasq-border) 100%);
}
.mi-range::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 14px; height: 14px;
  border-radius: 50%;
  background: #fff;
  border: 2px solid var(--gasq-primary);
  box-shadow: 0 1px 4px rgba(6,45,121,0.2);
  cursor: pointer;
  transition: box-shadow 0.15s, transform 0.15s;
}
.mi-range:hover::-webkit-slider-thumb,
.mi-range:focus::-webkit-slider-thumb {
  box-shadow: 0 0 0 4px rgba(6,45,121,0.12);
  transform: scale(1.15);
}
.mi-range::-moz-range-thumb {
  width: 14px; height: 14px;
  border-radius: 50%;
  background: #fff;
  border: 2px solid var(--gasq-primary);
  box-shadow: 0 1px 4px rgba(6,45,121,0.2);
  cursor: pointer;
}
.mi-range::-moz-range-track {
  height: 4px;
  background: var(--gasq-border);
  border-radius: 2px;
}

/* Field card */
.mi-field-card {
  background: var(--gasq-card);
  border: 1px solid var(--gasq-border);
  border-radius: var(--gasq-radius);
  padding: 14px 16px 12px;
  box-shadow: var(--gasq-shadow-card);
  transition: border-color 0.15s, box-shadow 0.15s;
}
.mi-field-card:hover {
  border-color: rgba(6,45,121,0.18);
}
.mi-field-card:focus-within {
  border-color: rgba(6,45,121,0.35);
  box-shadow: 0 0 0 3px rgba(6,45,121,0.07), var(--gasq-shadow-card);
}

.mi-input-stack {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.mi-input-stack > [class*="col-"] {
  width: 100%;
  max-width: 100%;
  flex: 0 0 100%;
}

.mi-results-panel {
  border: 1px solid rgba(6,45,121,0.08);
  border-radius: 1rem;
  background: #fff;
}

.mi-results-head {
  padding: 0.95rem 1rem;
  border-bottom: 1px solid rgba(6,45,121,0.08);
  font-size: 0.82rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #1e3a5f;
}

.mi-results-table td,
.mi-results-table th {
  vertical-align: middle;
}

/* Panels (tab content) */
.mi-panel { display: none; }
.mi-panel.active {
  display: block;
  animation: mi-fadein 0.2s ease;
}
@keyframes mi-fadein {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: none; }
}

/* Responsive */
@media (max-width: 1199.98px) {
  .mi-sticky { position: static; }
}

@media (max-width: 575.98px) {
  .mi-field-row { flex-wrap: wrap; }
  .mi-number-input { width: 100% !important; }
}
</style>
@endpush

@section('content')
<div class="container py-4">

  {{-- ── Page Header ─────────────────────────────────────────────── --}}
  <div class="mi-page-header">
    <div>
      <h1 class="gasq-page-title mb-1">
        <i class="fa fa-sliders text-primary me-2" style="font-size:1.4rem;vertical-align:middle;"></i>Master Inputs
      </h1>
      <p class="gasq-page-subtitle mb-0">Shared settings used across all calculators. Percent fields are entered as % and saved as decimals automatically.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetInputs()">
        <i class="fa fa-rotate me-1"></i>Reset
      </button>
      <button type="button" class="btn btn-primary btn-sm" onclick="markComplete()">
        <i class="fa fa-check me-1"></i>Continue
      </button>
    </div>
  </div>

  {{-- ── Alerts ───────────────────────────────────────────────────── --}}
  <div class="alert alert-danger d-none d-flex align-items-center gap-2" id="mi_err" role="alert">
    <i class="fa fa-circle-exclamation"></i><span id="mi_err_text"></span>
  </div>
  <div class="alert alert-success d-none d-flex align-items-center gap-2" id="mi_ok" role="alert">
    <i class="fa fa-circle-check"></i><span id="mi_ok_text"></span>
  </div>

  <div class="mi-shell">
    <div class="row g-0">
      <div class="col-xl-8 border-end mi-sidebar">
        <div class="p-3 p-md-4 mi-sticky">
          <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
            <div>
              <div class="mi-kicker mb-2">Shared Inputs</div>
              <h2 class="h4 fw-bold mb-2">Master Input Controls</h2>
              <p class="small text-gasq-muted mb-0">Every field on this page is a numeric control. Adjust values on the left and the live summary on the right updates immediately.</p>
            </div>
            <div class="mi-status-badge">
              <div class="mi-status-dot ready" id="mi_save_dot"></div>
              <span id="mi_save_label">Ready</span>
            </div>
          </div>

          {{-- ── Pill Tabs ────────────────────────────────────────────────── --}}
          <div class="gasq-tabs-scroll mb-4">
            <ul class="nav gasq-tabs-pill" id="miTabs">
              <li class="nav-item">
                <button class="nav-link active" onclick="switchPanel('panel-core', this)">
                  <i class="fa fa-gauge-high me-1"></i>Core Controls
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" onclick="switchPanel('panel-burden', this)">
                  <i class="fa fa-layer-group me-1"></i>Fringe &amp; Burden
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" onclick="switchPanel('panel-ops', this)">
                  <i class="fa fa-building-shield me-1"></i>Operations &amp; Factors
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" onclick="switchPanel('panel-vehicles', this)">
                  <i class="fa fa-car me-1"></i>Vehicles &amp; Escalation
                </button>
              </li>
            </ul>
          </div>

  {{-- ════ PANEL 1: Core Controls ════ --}}
  <div class="mi-panel active" id="panel-core" data-panel-label="Core Controls">

    <div class="mi-section-label">Labor</div>
    <div class="row g-3 mi-input-stack mb-2">
      <div class="col-lg-4 col-md-6">
        @include('partials.mi-field', ['id'=>'mi_directLaborWage','label'=>'Direct Labor Wage','help'=>'$ per paid hour','unit'=>'$','unit_pos'=>'prefix','step'=>'0.01','min'=>'0','max_slider'=>'150'])
      </div>
      <div class="col-lg-4 col-md-6">
        @include('partials.mi-field', ['id'=>'mi_annualPaidHoursPerFte','label'=>'Annual Paid Hours / FTE','help'=>'hours per year','unit'=>'hrs','step'=>'1','min'=>'0','max_slider'=>'4000'])
      </div>
      <div class="col-lg-4 col-md-6">
        @include('partials.mi-field', ['id'=>'mi_annualProductiveCoverageHoursPerFte','label'=>'Productive Coverage Hours / FTE','help'=>'hours per year','unit'=>'hrs','step'=>'1','min'=>'0','max_slider'=>'4000'])
      </div>
    </div>

    <div class="mi-section-label">Premiums</div>
    <div class="row g-3 mi-input-stack mb-2">
      @php
        $pctFields = [
          ['k'=>'localityPayPct',       'label'=>'Locality Pay',            'max'=>50],
          ['k'=>'shiftDifferentialPct', 'label'=>'Shift Differential',      'max'=>50],
          ['k'=>'otHolidayPremiumPct',  'label'=>'OT / Holiday Premium',    'max'=>50],
          ['k'=>'laborMarketAdjPct',    'label'=>'Labor Market Adjustment', 'max'=>50],
        ];
      @endphp
      @foreach($pctFields as $f)
        <div class="col-md-6 col-lg-3">
          @include('partials.mi-field', ['id'=>'mi_'.$f['k'],'label'=>$f['label'],'help'=>'percentage','unit'=>'%','step'=>'0.01','min'=>'0','max_slider'=>$f['max'],'data_unit'=>'pct'])
        </div>
      @endforeach
    </div>

    <div class="mi-section-label">Benefits</div>
    <div class="row g-3 mi-input-stack">
      <div class="col-md-6">
        @include('partials.mi-field', ['id'=>'mi_hwCashPerHour','label'=>'H&W Cash','help'=>'$ per paid hour','unit'=>'$','unit_pos'=>'prefix','step'=>'0.01','min'=>'0','max_slider'=>'50'])
      </div>
      <div class="col-md-6">
        @include('partials.mi-field', ['id'=>'mi_donDoffMinutesPerShift','label'=>'DON / DOFF Time','help'=>'minutes per 8-hour shift','unit'=>'min','step'=>'1','min'=>'0','max_slider'=>'120'])
      </div>
    </div>
  </div>

  {{-- ════ PANEL 2: Fringe & Burden ════ --}}
  <div class="mi-panel" id="panel-burden" data-panel-label="Fringe &amp; Burden">

    <div class="mi-section-label">Statutory &amp; Insurance</div>
    <div class="row g-3 mi-input-stack mb-2">
      @php
        $burden = [
          ['k'=>'ficaMedicarePct',      'label'=>'FICA / Medicare',             'max'=>20],
          ['k'=>'futaPct',              'label'=>'FUTA',                        'max'=>5],
          ['k'=>'sutaPct',              'label'=>'SUTA',                        'max'=>20],
          ['k'=>'workersCompPct',       'label'=>'Workers Compensation',        'max'=>20],
          ['k'=>'generalLiabilityPct',  'label'=>'General Liability Insurance', 'max'=>20],
          ['k'=>'umbrellaInsurancePct', 'label'=>'Umbrella / Other Insurance',  'max'=>5],
        ];
      @endphp
      @foreach($burden as $f)
        <div class="col-md-6 col-lg-4">
          @include('partials.mi-field', ['id'=>'mi_'.$f['k'],'label'=>$f['label'],'help'=>'percentage','unit'=>'%','step'=>'0.01','min'=>'0','max_slider'=>$f['max'],'data_unit'=>'pct'])
        </div>
      @endforeach
    </div>

    <div class="mi-section-label">Paid Leave</div>
    <div class="row g-3 mi-input-stack mb-2">
      @php
        $leave = [
          ['k'=>'vacationPct',     'label'=>'Vacation',      'max'=>20],
          ['k'=>'paidHolidaysPct', 'label'=>'Paid Holidays', 'max'=>20],
          ['k'=>'sickLeavePct',    'label'=>'Sick Leave',     'max'=>20],
        ];
      @endphp
      @foreach($leave as $f)
        <div class="col-md-6 col-lg-4">
          @include('partials.mi-field', ['id'=>'mi_'.$f['k'],'label'=>$f['label'],'help'=>'percentage','unit'=>'%','step'=>'0.01','min'=>'0','max_slider'=>$f['max'],'data_unit'=>'pct'])
        </div>
      @endforeach
      <div class="col-md-6 col-lg-4">
        @include('partials.mi-field', ['id'=>'mi_healthWelfarePerHour','label'=>'Health &amp; Welfare','help'=>'$ per paid hour','unit'=>'$','unit_pos'=>'prefix','step'=>'0.01','min'=>'0','max_slider'=>'50'])
      </div>
    </div>

    <div class="mi-section-label">Corporate Overhead</div>
    <div class="row g-3 mi-input-stack">
      @php
        $overhead = [
          ['k'=>'corporateOverheadPct', 'label'=>'Corporate Overhead', 'max'=>30],
          ['k'=>'gaPct',                'label'=>'G &amp; A',          'max'=>30],
          ['k'=>'profitFeePct',         'label'=>'Profit / Fee',       'max'=>100],
        ];
      @endphp
      @foreach($overhead as $f)
        <div class="col-md-6 col-lg-4">
          @include('partials.mi-field', ['id'=>'mi_'.$f['k'],'label'=>$f['label'],'help'=>'percentage','unit'=>'%','step'=>'0.01','min'=>'0','max_slider'=>$f['max'],'data_unit'=>'pct'])
        </div>
      @endforeach
    </div>
  </div>

  {{-- ════ PANEL 3: Operations & Factors ════ --}}
  <div class="mi-panel" id="panel-ops" data-panel-label="Operations &amp; Factors">

    <div class="mi-section-label">Operations Support</div>
    <div class="row g-3 mi-input-stack mb-2">
      @php
        $ops = [
          ['k'=>'recruitingHiringPct',     'label'=>'Recruiting / Hiring',      'max'=>20],
          ['k'=>'trainingCertificationPct','label'=>'Training / Certification', 'max'=>20],
          ['k'=>'uniformsEquipmentPct',    'label'=>'Uniforms / Equipment',     'max'=>20],
          ['k'=>'fieldSupervisionPct',     'label'=>'Field Supervision',        'max'=>20],
          ['k'=>'contractManagementPct',   'label'=>'Contract Management',      'max'=>20],
          ['k'=>'qualityAssurancePct',     'label'=>'Quality Assurance',        'max'=>20],
          ['k'=>'vehiclesPatrolPct',       'label'=>'Vehicles / Patrol',        'max'=>30],
          ['k'=>'technologySystemsPct',    'label'=>'Technology / Systems',     'max'=>20],
        ];
      @endphp
      @foreach($ops as $f)
        <div class="col-md-6 col-lg-3">
          @include('partials.mi-field', ['id'=>'mi_'.$f['k'],'label'=>$f['label'],'help'=>'percentage','unit'=>'%','step'=>'0.01','min'=>'0','max_slider'=>$f['max'],'data_unit'=>'pct'])
        </div>
      @endforeach
    </div>

    <div class="mi-section-label">Vendor / Government Factors</div>
    <div class="row g-3 mi-input-stack">
      @php
        $gov = [
          ['k'=>'vendorTcoFactorVsGovTco',       'label'=>'Vendor TCO Factor vs Gov TCO',      'max'=>120, 'step'=>'0.1'],
          ['k'=>'vendorFloorFactorVsVendorTco',   'label'=>'Vendor Floor Factor vs Vendor TCO', 'max'=>120, 'step'=>'0.1'],
          ['k'=>'governmentFullBurdenLaborShare', 'label'=>'Gov Full Burden Labor Share',        'max'=>100, 'step'=>'0.1'],
        ];
      @endphp
      @foreach($gov as $f)
        <div class="col-md-6 col-lg-4">
          @include('partials.mi-field', ['id'=>'mi_'.$f['k'],'label'=>$f['label'],'help'=>'percentage','unit'=>'%','step'=>$f['step'],'min'=>'0','max_slider'=>$f['max'],'data_unit'=>'pct'])
        </div>
      @endforeach
      <div class="col-md-6 col-lg-4">
        @include('partials.mi-field', ['id'=>'mi_minWeeklyHoursForFloorEligibility','label'=>'Min Weekly Hours for Floor Eligibility','help'=>'hours','unit'=>'hrs','step'=>'1','min'=>'0','max_slider'=>'6000'])
      </div>
      <div class="col-md-6 col-lg-4">
        @include('partials.mi-field', ['id'=>'mi_governmentWorkforceHoursBasis','label'=>'Government Workforce Hours Basis','help'=>'hours','unit'=>'hrs','step'=>'1','min'=>'0','max_slider'=>'12000'])
      </div>
      <div class="col-md-6 col-lg-4">
        @include('partials.mi-field', ['id'=>'mi_governmentTcoMultiplierMin','label'=>'Government TCO Multiplier (Min)','help'=>'multiplier','unit'=>'×','step'=>'0.1','min'=>'0','max_slider'=>'10'])
      </div>
      <div class="col-md-6 col-lg-4">
        @include('partials.mi-field', ['id'=>'mi_governmentTcoMultiplierMax','label'=>'Government TCO Multiplier (Max)','help'=>'multiplier','unit'=>'×','step'=>'0.1','min'=>'0','max_slider'=>'10'])
      </div>
    </div>
  </div>

  {{-- ════ PANEL 4: Vehicles & Escalation ════ --}}
  <div class="mi-panel" id="panel-vehicles" data-panel-label="Vehicles &amp; Escalation">

    <div class="mi-section-label">Vehicle Fleet</div>
    <div class="row g-3 mi-input-stack mb-2">
      <div class="col-md-6 col-lg-4">
        @include('partials.mi-field', ['id'=>'mi_vehiclesRequired','label'=>'Vehicles Required','help'=>'count','unit'=>'veh','step'=>'1','min'=>'0','max_slider'=>'50'])
      </div>
      <div class="col-md-6 col-lg-4">
        @include('partials.mi-field', ['id'=>'mi_avgMilesPerVehiclePerDay','label'=>'Avg Miles per Vehicle / Day','help'=>'miles','unit'=>'mi','step'=>'1','min'=>'0','max_slider'=>'1000'])
      </div>
      <div class="col-md-6 col-lg-4">
        @include('partials.mi-field', ['id'=>'mi_fuelCostPerGallon','label'=>'Fuel Cost per Gallon','help'=>'dollars','unit'=>'$','unit_pos'=>'prefix','step'=>'0.01','min'=>'0','max_slider'=>'15'])
      </div>
    </div>

    <div class="mi-section-label">Annual Escalation Rates</div>
    <div class="row g-3 mi-input-stack">
      @php
        $esc = [
          ['k'=>'customAnnualEscalationPct','label'=>'Custom Annual Escalation','max'=>25],
          ['k'=>'lowEscalationPct',         'label'=>'Low Escalation',          'max'=>25],
          ['k'=>'mediumEscalationPct',       'label'=>'Medium Escalation',       'max'=>25],
          ['k'=>'highEscalationPct',         'label'=>'High Escalation',         'max'=>25],
        ];
      @endphp
      @foreach($esc as $f)
        <div class="col-md-6 col-lg-3">
          @include('partials.mi-field', ['id'=>'mi_'.$f['k'],'label'=>$f['label'],'help'=>'percentage','unit'=>'%','step'=>'0.1','min'=>'0','max_slider'=>$f['max'],'data_unit'=>'pct'])
        </div>
      @endforeach
    </div>
  </div>
        </div>
      </div>

      <div class="col-xl-4 mi-results">
        <div class="p-3 p-md-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
              <div class="mi-kicker mb-1">Results Workspace</div>
              <h3 class="h5 fw-bold mb-0">Live Master Input Summary</h3>
            </div>
            <div class="small text-gasq-muted">The summary below reflects the values from the active tab on the left.</div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="mi-stat">
                <div class="mi-stat-label mb-2">Loaded Hourly Base</div>
                <div class="mi-stat-value" id="mi_stat_loadedHourly">$0.00</div>
                <div class="small text-gasq-muted">Direct labor plus hourly benefits</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mi-stat">
                <div class="mi-stat-label mb-2">Burden & Leave</div>
                <div class="mi-stat-value" id="mi_stat_burdenPct">0.00%</div>
                <div class="small text-gasq-muted">Fringe, insurance, and paid leave total</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mi-stat">
                <div class="mi-stat-label mb-2">Support & Markup</div>
                <div class="mi-stat-value" id="mi_stat_supportPct">0.00%</div>
                <div class="small text-gasq-muted">Operations, overhead, G&amp;A, and fee</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mi-stat">
                <div class="mi-stat-label mb-2">Fleet Miles / Day</div>
                <div class="mi-stat-value" id="mi_stat_fleetMiles">0</div>
                <div class="small text-gasq-muted">Vehicles required × average daily miles</div>
              </div>
            </div>
          </div>

          <div class="mi-results-panel mb-3">
            <div class="mi-results-head d-flex justify-content-between align-items-center gap-3">
              <span>Active Panel Snapshot</span>
              <span class="small text-gasq-muted text-uppercase" id="mi_active_panel_name">Core Controls</span>
            </div>
            <div class="table-responsive">
              <table class="table table-sm mb-0 mi-results-table">
                <thead class="table-light">
                  <tr>
                    <th>Field</th>
                    <th class="text-end">Current Value</th>
                  </tr>
                </thead>
                <tbody id="mi_results_tbody"></tbody>
              </table>
            </div>
          </div>

          <div class="mi-results-panel">
            <div class="mi-results-head">Escalation & Fleet Snapshot</div>
            <div class="p-3 small">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-gasq-muted">Low / Medium / High escalation</span>
                <span class="fw-semibold" id="mi_escalation_band">0.00% / 0.00% / 0.00%</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-gasq-muted">Fuel cost per gallon</span>
                <span class="fw-semibold" id="mi_fuel_snapshot">$0.00</span>
              </div>
              <div class="d-flex justify-content-between mb-0">
                <span class="text-gasq-muted">Saved field count</span>
                <span class="fw-semibold" id="mi_field_count">0</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>{{-- /container --}}
@endsection

@push('scripts')
<script>
(() => {
  const DEFAULTS = @json($inputs ?? []);
  const apiUrl   = @json(route('api.master-inputs.update'));
  const showUrl  = @json(route('api.master-inputs.show'));
  let saveT    = null;
  let inflight = null;
  let lastSavedAt = null;

  /* ── Save state UI ─────────────────────────────────────────── */
  const setSaveState = (state) => {
    const dot   = document.getElementById('mi_save_dot');
    const label = document.getElementById('mi_save_label');
    if(!dot || !label) return;
    dot.className = 'mi-status-dot ' + state;
    if(state === 'saving') label.textContent = 'Saving…';
    if(state === 'saved'){
      const t = lastSavedAt
        ? lastSavedAt.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})
        : '';
      label.textContent = t ? 'Saved ' + t : 'Saved';
    }
    if(state === 'ready') label.textContent = 'Ready';
    if(state === 'error') label.textContent = 'Save failed';
  };

  const setErr = (msg) => {
    const el = document.getElementById('mi_err');
    const tx = document.getElementById('mi_err_text');
    if(!el || !tx) return;
    tx.textContent = msg || '';
    el.classList.toggle('d-none', !msg);
    el.classList.toggle('d-flex', !!msg);
  };

  const flashOk = (msg) => {
    const el = document.getElementById('mi_ok');
    const tx = document.getElementById('mi_ok_text');
    if(!el || !tx) return;
    tx.textContent = msg || '';
    el.classList.remove('d-none');
    el.classList.add('d-flex');
    setTimeout(()=>{ el.classList.add('d-none'); el.classList.remove('d-flex'); }, 2000);
  };

  /* ── Slider fill ───────────────────────────────────────────── */
  const updateSliderFill = (rangeEl) => {
    const min = parseFloat(rangeEl.min || '0');
    const max = parseFloat(rangeEl.max || '100');
    const val = parseFloat(rangeEl.value || '0');
    const pct = ((val - min) / (max - min)) * 100;
    rangeEl.style.setProperty('--pct', Math.max(0, Math.min(100, pct)).toFixed(1) + '%');
  };

  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

  const currency = (n) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(n || 0);

  const number = (n, decimals = 2) => new Intl.NumberFormat('en-US', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(n || 0);

  const integer = (n) => new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(n || 0);

  const fieldValue = (key) => {
    const el = document.getElementById('mi_' + key);
    return el ? (parseFloat(el.value) || 0) : 0;
  };

  /* ── Slider ↔ number sync ──────────────────────────────────── */
  function initSliderSync() {
    document.querySelectorAll('input[type="range"][data-sync]').forEach((rangeEl) => {
      const id    = rangeEl.getAttribute('data-sync');
      const numEl = document.getElementById(id);
      if(!numEl) return;

      const syncFromNumber = () => {
        const min = parseFloat(rangeEl.min || '0');
        const max = parseFloat(rangeEl.max || '100');
        rangeEl.value = String(clamp(parseFloat(numEl.value || '0'), min, max));
        updateSliderFill(rangeEl);
      };
      const syncFromRange = () => {
        numEl.value = rangeEl.value;
        updateSliderFill(rangeEl);
      };

      syncFromNumber();
      if(rangeEl.dataset.bound === '1') return;
      rangeEl.dataset.bound = '1';

      rangeEl.addEventListener('input', () => {
        syncFromRange();
        updateResultsWorkspace();
        scheduleSave();
      });
      numEl.addEventListener('input', () => {
        syncFromNumber();
        updateResultsWorkspace();
        scheduleSave();
      });
    });
  }

  function bindDirectInputPersistence() {
    document.querySelectorAll('.mi-number-input').forEach((inputEl) => {
      if (inputEl.dataset.persistBound === '1') return;
      inputEl.dataset.persistBound = '1';

      inputEl.addEventListener('change', () => {
        updateResultsWorkspace();
        scheduleSave();
      });
      inputEl.addEventListener('blur', () => {
        updateResultsWorkspace();
        scheduleSave();
      });
    });
  }

  /* ── Percent keys ──────────────────────────────────────────── */
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

  function collectInputs() {
    const out = {};
    Object.keys(DEFAULTS).forEach((k) => {
      const el = document.getElementById('mi_' + k);
      if(!el) return;
      const v = parseFloat(el.value);
      if(!Number.isFinite(v)) { out[k] = DEFAULTS[k]; return; }
      out[k] = PERCENT_KEYS.has(k) ? (v / 100.0) : v;
    });
    return out;
  }

  function fillFormFromInputs(inputs) {
    Object.entries(inputs || {}).forEach(([k, v]) => {
      const el = document.getElementById('mi_' + k);
      if(!el) return;
      if(PERCENT_KEYS.has(k) && typeof v === 'number') el.value = (v * 100.0);
      else el.value = v;
    });
  }

  function formatFieldValue(inputEl) {
    const raw = parseFloat(inputEl.value || '0') || 0;
    const unit = inputEl.dataset.unit || '';
    const isPct = inputEl.dataset.isPct === '1';

    if (isPct) {
      return number(raw, 2) + '%';
    }

    if (unit === '$') {
      return currency(raw);
    }

    if (unit === 'hrs') {
      return integer(raw) + ' hrs';
    }

    if (unit === 'veh') {
      return integer(raw) + ' vehicles';
    }

    if (unit === 'mi') {
      return integer(raw) + ' mi';
    }

    if (unit === 'min') {
      return integer(raw) + ' min';
    }

    if (unit === '×') {
      return number(raw, 2) + 'x';
    }

    return number(raw, 2);
  }

  function updateResultsWorkspace() {
    const loadedHourly =
      fieldValue('directLaborWage') +
      fieldValue('hwCashPerHour') +
      fieldValue('healthWelfarePerHour');

    const burdenPct = [
      'ficaMedicarePct','futaPct','sutaPct','workersCompPct',
      'generalLiabilityPct','umbrellaInsurancePct','vacationPct',
      'paidHolidaysPct','sickLeavePct'
    ].reduce((sum, key) => sum + fieldValue(key), 0);

    const supportPct = [
      'recruitingHiringPct','trainingCertificationPct','uniformsEquipmentPct',
      'fieldSupervisionPct','contractManagementPct','qualityAssurancePct',
      'vehiclesPatrolPct','technologySystemsPct','adminHrPayrollPct',
      'accountingLegalPct','corporateOverheadPct','gaPct','profitFeePct'
    ].reduce((sum, key) => sum + fieldValue(key), 0);

    const fleetMiles = fieldValue('vehiclesRequired') * fieldValue('avgMilesPerVehiclePerDay');

    const setText = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    };

    setText('mi_stat_loadedHourly', currency(loadedHourly));
    setText('mi_stat_burdenPct', number(burdenPct, 2) + '%');
    setText('mi_stat_supportPct', number(supportPct, 2) + '%');
    setText('mi_stat_fleetMiles', integer(fleetMiles));
    setText(
      'mi_escalation_band',
      [fieldValue('lowEscalationPct'), fieldValue('mediumEscalationPct'), fieldValue('highEscalationPct')]
        .map((value) => number(value, 2) + '%')
        .join(' / ')
    );
    setText('mi_fuel_snapshot', currency(fieldValue('fuelCostPerGallon')));
    setText('mi_field_count', integer(document.querySelectorAll('[id^="mi_"][type="number"]').length));

    const activePanel = document.querySelector('.mi-panel.active');
    const activePanelName = activePanel?.dataset.panelLabel || 'Core Controls';
    setText('mi_active_panel_name', activePanelName);

    const tbody = document.getElementById('mi_results_tbody');
    if (!tbody) return;

    const rows = Array.from(activePanel?.querySelectorAll('input[type="number"]') || []).map((inputEl) => {
      const label = inputEl.dataset.label || inputEl.id;
      const help = inputEl.dataset.help || '';
      return `<tr>
        <td>
          <div class="fw-medium">${label}</div>
          <div class="small text-gasq-muted">${help}</div>
        </td>
        <td class="text-end fw-semibold">${formatFieldValue(inputEl)}</td>
      </tr>`;
    });

    tbody.innerHTML = rows.join('');
  }

  async function saveNow(isComplete = null) {
    try {
      setErr('');
      setSaveState('saving');
      if(inflight) { inflight.abort(); }
      inflight = new AbortController();
      const body = { inputs: collectInputs() };
      if(isComplete !== null) { body.is_complete = !!isComplete; }
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
      const data = await res.json().catch(() => null);
      if(!res.ok || !data || !data.ok) {
        setErr((data && data.message) ? data.message : 'Unable to save inputs right now.');
        setSaveState('error');
        return false;
      }
      lastSavedAt = new Date();
      setSaveState('saved');
      return true;
    } catch(e) {
      if(e?.name === 'AbortError') return false;
      setErr('Unable to save inputs right now.');
      setSaveState('error');
      return false;
    }
  }

  function scheduleSave() {
    clearTimeout(saveT);
    setSaveState('saving');
    saveT = setTimeout(() => { void saveNow(); }, 350);
  }

  async function loadFromServer() {
    try {
      const res  = await fetch(showUrl, { headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(() => null);
      if(!res.ok || !data || !data.ok) return;
      fillFormFromInputs(data.inputs || {});
      updateResultsWorkspace();
      setSaveState('ready');
    } catch(e) { return; }
  }

  /* ── Public API ────────────────────────────────────────────── */
  window.resetInputs = async function() {
    fillFormFromInputs(DEFAULTS);
    initSliderSync();
    bindDirectInputPersistence();
    updateResultsWorkspace();
    const ok = await saveNow(false);
    if(ok) flashOk('Reset to defaults');
  };

  window.markComplete = async function() {
    const ok = await saveNow(true);
    if(ok) {
      flashOk('Saved — redirecting…');
      setTimeout(() => { window.location.href = @json(route('calculator.index')); }, 700);
    }
  };

  window.switchPanel = function(panelId, navEl) {
    document.querySelectorAll('.mi-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('#miTabs .nav-link').forEach(n => n.classList.remove('active'));
    const panel = document.getElementById(panelId);
    if(panel) panel.classList.add('active');
    if(navEl) navEl.classList.add('active');
    initSliderSync();
    bindDirectInputPersistence();
    updateResultsWorkspace();
  };

  /* ── Boot ──────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', async () => {
    await loadFromServer();
    initSliderSync();
    bindDirectInputPersistence();
    Object.entries(DEFAULTS).forEach(([k, v]) => {
      const el = document.getElementById('mi_' + k);
      if(!el || el.value !== '') return;
      if(PERCENT_KEYS.has(k) && typeof v === 'number') el.value = (v * 100.0);
      else el.value = v;
    });
    initSliderSync();
    bindDirectInputPersistence();
    updateResultsWorkspace();
    setSaveState('ready');
  });
})();
</script>
@endpush
