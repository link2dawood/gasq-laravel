@extends('layouts.app')
@section('title', 'Master Inputs')
@section('header_variant', 'dashboard')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700&family=DM+Mono:ital,wght@0,300;0,400;0,500;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* ─── Design Tokens ──────────────────────────────────────────────── */
:root {
  --mi-bg:          #eef1f7;
  --mi-surface:     #ffffff;
  --mi-navy:        #0d1f3c;
  --mi-navy-mid:    #1a3360;
  --mi-navy-lt:     #254a8a;
  --mi-blue:        #1a56db;
  --mi-blue-lt:     #3b82f6;
  --mi-blue-glow:   rgba(26,86,219,0.12);
  --mi-accent:      #f59e0b;
  --mi-green:       #059669;
  --mi-red:         #dc2626;
  --mi-border:      #dce3f0;
  --mi-border-mid:  #c8d3ea;
  --mi-text:        #0d1f3c;
  --mi-muted:       #607290;
  --mi-dimmer:      #98a6bf;
  --mi-input-bg:    #f7f9fd;
  --mi-tag-bg:      #eef3fc;
  --mi-tag-text:    #1a3360;
  --mi-h1:          'Bricolage Grotesque', sans-serif;
  --mi-body:        'Plus Jakarta Sans', sans-serif;
  --mi-mono:        'DM Mono', monospace;
  --mi-radius:      14px;
  --mi-radius-sm:   8px;
  --mi-shadow:      0 1px 3px rgba(13,31,60,0.06), 0 4px 16px rgba(13,31,60,0.05);
  --mi-shadow-card: 0 2px 8px rgba(13,31,60,0.05), 0 8px 32px rgba(13,31,60,0.06);
}

/* ─── Page Shell ─────────────────────────────────────────────────── */
.mi-shell {
  font-family: var(--mi-body);
  background: var(--mi-bg);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* ─── Top Bar ────────────────────────────────────────────────────── */
.mi-topbar {
  background: var(--mi-navy);
  border-bottom: 1px solid rgba(255,255,255,0.07);
  padding: 0 2rem;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 50;
  backdrop-filter: blur(12px);
}

.mi-topbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.mi-topbar-icon {
  width: 36px; height: 36px;
  background: rgba(26,86,219,0.22);
  border: 1px solid rgba(59,130,246,0.3);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #60a5fa;
  font-size: 15px;
}

.mi-topbar-title {
  font-family: var(--mi-h1);
  font-size: 17px;
  font-weight: 600;
  color: #e8eef8;
  letter-spacing: -0.01em;
  margin: 0;
}

.mi-topbar-sub {
  font-family: var(--mi-mono);
  font-size: 11px;
  color: #5e7499;
  letter-spacing: 0.04em;
  margin-top: 1px;
}

.mi-topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Save pill */
.mi-save-pill {
  display: flex;
  align-items: center;
  gap: 7px;
  font-family: var(--mi-mono);
  font-size: 11.5px;
  color: #7a92b8;
  letter-spacing: 0.03em;
  padding: 5px 12px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 100px;
}

.mi-save-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #3b82f6;
  transition: background 0.25s, box-shadow 0.25s;
}
.mi-save-dot.saving  { background: #f59e0b; box-shadow: 0 0 6px rgba(245,158,11,0.6); animation: mi-blink 0.6s ease-in-out infinite; }
.mi-save-dot.saved   { background: #059669; box-shadow: 0 0 6px rgba(5,150,105,0.5); }
.mi-save-dot.error   { background: #dc2626; box-shadow: 0 0 6px rgba(220,38,38,0.5); }
.mi-save-dot.ready   { background: #3b82f6; }

@keyframes mi-blink {
  0%,100% { opacity: 1; } 50% { opacity: 0.35; }
}

/* Action buttons */
.mi-btn-reset {
  font-family: var(--mi-body);
  font-size: 13px; font-weight: 500;
  color: #98a6bf;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  padding: 7px 16px;
  cursor: pointer;
  transition: all 0.18s;
  display: flex; align-items: center; gap: 7px;
  letter-spacing: 0.01em;
}
.mi-btn-reset:hover { color: #e2eaf8; background: rgba(255,255,255,0.10); border-color: rgba(255,255,255,0.18); }

.mi-btn-continue {
  font-family: var(--mi-body);
  font-size: 13px; font-weight: 600;
  color: #fff;
  background: var(--mi-blue);
  border: 1px solid transparent;
  border-radius: 8px;
  padding: 7px 20px;
  cursor: pointer;
  transition: all 0.18s;
  display: flex; align-items: center; gap: 7px;
  letter-spacing: 0.005em;
  box-shadow: 0 2px 10px rgba(26,86,219,0.35);
}
.mi-btn-continue:hover { background: #1d4ed8; box-shadow: 0 4px 16px rgba(26,86,219,0.45); transform: translateY(-1px); }

/* ─── Main Layout ────────────────────────────────────────────────── */
.mi-layout {
  display: flex;
  flex: 1;
  max-width: 1440px;
  margin: 0 auto;
  width: 100%;
  padding: 0 1.5rem;
  gap: 0;
}

/* ─── Sidebar ────────────────────────────────────────────────────── */
.mi-sidebar {
  width: 228px;
  flex-shrink: 0;
  padding: 28px 0 40px;
  position: sticky;
  top: 64px;
  height: calc(100vh - 64px);
  overflow-y: auto;
  scrollbar-width: none;
}
.mi-sidebar::-webkit-scrollbar { display: none; }

.mi-nav-header {
  font-family: var(--mi-mono);
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.14em;
  color: var(--mi-dimmer);
  text-transform: uppercase;
  padding: 0 4px;
  margin-bottom: 10px;
}

.mi-nav-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.16s;
  margin-bottom: 3px;
  border: 1px solid transparent;
  text-decoration: none;
}
.mi-nav-item:hover {
  background: #fff;
  border-color: var(--mi-border);
  box-shadow: var(--mi-shadow);
}
.mi-nav-item.active {
  background: #fff;
  border-color: rgba(26,86,219,0.18);
  box-shadow: 0 2px 12px rgba(26,86,219,0.08);
}

.mi-nav-dot {
  width: 28px; height: 28px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px;
  flex-shrink: 0;
  margin-top: 1px;
  transition: all 0.16s;
}
.mi-nav-item .mi-nav-dot { background: #f0f4fb; color: var(--mi-muted); border: 1px solid var(--mi-border); }
.mi-nav-item.active .mi-nav-dot { background: var(--mi-blue); color: #fff; border-color: var(--mi-blue); box-shadow: 0 3px 8px rgba(26,86,219,0.35); }

.mi-nav-label {
  font-family: var(--mi-body);
  font-size: 13px;
  font-weight: 500;
  color: var(--mi-muted);
  line-height: 1.3;
  transition: color 0.16s;
}
.mi-nav-count {
  font-family: var(--mi-mono);
  font-size: 10px;
  color: var(--mi-dimmer);
  margin-top: 2px;
}
.mi-nav-item.active .mi-nav-label { color: var(--mi-navy); }
.mi-nav-item.active .mi-nav-count { color: var(--mi-blue-lt); }
.mi-nav-item:hover .mi-nav-label { color: var(--mi-navy); }

/* Sidebar divider */
.mi-nav-divider {
  height: 1px;
  background: var(--mi-border);
  margin: 14px 4px;
}

/* ─── Content Area ───────────────────────────────────────────────── */
.mi-content {
  flex: 1;
  padding: 28px 0 56px 28px;
  min-width: 0;
}

/* ─── Section Panel ──────────────────────────────────────────────── */
.mi-panel {
  display: none;
}
.mi-panel.active {
  display: block;
  animation: mi-fadein 0.25s ease;
}
@keyframes mi-fadein {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: none; }
}

/* Panel header */
.mi-panel-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 28px;
}

.mi-panel-title {
  font-family: var(--mi-h1);
  font-size: 26px;
  font-weight: 700;
  color: var(--mi-navy);
  letter-spacing: -0.025em;
  margin: 0 0 4px;
  line-height: 1.1;
}

.mi-panel-desc {
  font-family: var(--mi-body);
  font-size: 13.5px;
  color: var(--mi-muted);
  line-height: 1.55;
  max-width: 520px;
}

/* Section label */
.mi-section-label {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--mi-mono);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--mi-muted);
  margin: 32px 0 16px;
}
.mi-section-label:first-child { margin-top: 0; }
.mi-section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--mi-border);
}

/* ─── Field Card ─────────────────────────────────────────────────── */
.mi-field {
  background: var(--mi-surface);
  border: 1px solid var(--mi-border);
  border-radius: var(--mi-radius);
  padding: 18px 20px 16px;
  transition: border-color 0.18s, box-shadow 0.18s;
  box-shadow: var(--mi-shadow);
  position: relative;
}

.mi-field:hover {
  border-color: var(--mi-border-mid);
  box-shadow: var(--mi-shadow-card);
}

.mi-field:focus-within {
  border-color: rgba(26,86,219,0.35);
  box-shadow: 0 0 0 3px rgba(26,86,219,0.07), var(--mi-shadow-card);
}

/* Field top row: label | input */
.mi-field-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 12px;
}

.mi-label-group {
  flex: 1;
  min-width: 0;
}

.mi-label {
  font-family: var(--mi-body);
  font-size: 13.5px;
  font-weight: 600;
  color: var(--mi-navy);
  margin: 0 0 3px;
  line-height: 1.3;
  letter-spacing: -0.005em;
}

.mi-help {
  font-family: var(--mi-mono);
  font-size: 11px;
  color: var(--mi-dimmer);
  letter-spacing: 0.04em;
}

/* Input + unit wrapper */
.mi-input-wrap {
  display: flex;
  align-items: center;
  gap: 0;
  flex-shrink: 0;
}

/* Number input */
.mi-input {
  width: 92px !important;
  font-family: var(--mi-mono) !important;
  font-size: 15px !important;
  font-weight: 500 !important;
  color: var(--mi-navy) !important;
  background: var(--mi-input-bg) !important;
  border: 1.5px solid var(--mi-border) !important;
  border-radius: var(--mi-radius-sm) 0 0 var(--mi-radius-sm) !important;
  padding: 7px 10px !important;
  text-align: right !important;
  transition: border-color 0.18s, background 0.18s !important;
  outline: none !important;
  box-shadow: none !important;
  -moz-appearance: textfield !important;
}
.mi-input::-webkit-inner-spin-button,
.mi-input::-webkit-outer-spin-button { -webkit-appearance: none; }

.mi-input:focus {
  border-color: var(--mi-blue) !important;
  background: #fff !important;
  box-shadow: none !important;
}

/* standalone input (no unit tag) */
.mi-input.standalone {
  border-radius: var(--mi-radius-sm) !important;
  width: 110px !important;
}

/* Unit tag */
.mi-unit {
  font-family: var(--mi-mono);
  font-size: 11.5px;
  font-weight: 500;
  color: var(--mi-muted);
  background: #f0f4fb;
  border: 1.5px solid var(--mi-border);
  border-left: none;
  padding: 0 10px;
  height: 36px;
  display: flex; align-items: center;
  border-radius: 0 var(--mi-radius-sm) var(--mi-radius-sm) 0;
  letter-spacing: 0.04em;
  white-space: nowrap;
  min-width: 36px;
  justify-content: center;
}

/* ─── Custom Slider ──────────────────────────────────────────────── */
.mi-slider-track {
  position: relative;
  height: 20px;
  display: flex;
  align-items: center;
}

.mi-range {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 4px;
  background: transparent;
  outline: none;
  cursor: pointer;
  position: relative;
  z-index: 1;
  margin: 0;
}

/* Track fill line */
.mi-slider-track::before {
  content: '';
  position: absolute;
  left: 0; right: 0; top: 50%;
  height: 4px;
  transform: translateY(-50%);
  background: var(--mi-border);
  border-radius: 2px;
  pointer-events: none;
}

/* Webkit track */
.mi-range::-webkit-slider-runnable-track {
  height: 4px;
  background: var(--mi-border);
  border-radius: 2px;
}

/* Webkit thumb */
.mi-range::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px; height: 16px;
  border-radius: 50%;
  background: #fff;
  border: 2px solid var(--mi-blue);
  box-shadow: 0 0 0 3px rgba(26,86,219,0.14), 0 2px 6px rgba(13,31,60,0.12);
  cursor: pointer;
  margin-top: -6px;
  transition: box-shadow 0.16s, transform 0.16s;
}
.mi-range:hover::-webkit-slider-thumb,
.mi-range:focus::-webkit-slider-thumb {
  box-shadow: 0 0 0 5px rgba(26,86,219,0.18), 0 2px 8px rgba(13,31,60,0.16);
  transform: scale(1.1);
}

/* Firefox */
.mi-range::-moz-range-track {
  height: 4px;
  background: var(--mi-border);
  border-radius: 2px;
}
.mi-range::-moz-range-thumb {
  width: 16px; height: 16px;
  border-radius: 50%;
  background: #fff;
  border: 2px solid var(--mi-blue);
  box-shadow: 0 0 0 3px rgba(26,86,219,0.14);
  cursor: pointer;
}

/* Slider progress fill via JS-set custom property */
.mi-range {
  --pct: 0%;
  background: linear-gradient(to right,
    rgba(26,86,219,0.5) 0%, rgba(26,86,219,0.5) var(--pct),
    transparent var(--pct), transparent 100%) !important;
}

/* ─── Alerts ─────────────────────────────────────────────────────── */
.mi-alert {
  border-radius: var(--mi-radius-sm);
  padding: 12px 16px;
  font-size: 13px;
  font-family: var(--mi-body);
  margin-bottom: 16px;
  border: 1px solid;
  display: none;
}
.mi-alert.show { display: flex; align-items: center; gap: 10px; }
.mi-alert-err { background: #fef2f2; border-color: #fca5a5; color: #991b1b; }
.mi-alert-ok  { background: #f0fdf4; border-color: #86efac; color: #166534; }

/* ─── Responsive ─────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .mi-sidebar { display: none; }
  .mi-content { padding-left: 0; }
  .mi-topbar  { padding: 0 1rem; }
  .mi-topbar-sub { display: none; }
}

@media (max-width: 600px) {
  .mi-field-row { flex-wrap: wrap; }
  .mi-input     { width: 80px !important; }
  .mi-panel-title { font-size: 21px; }
  .mi-topbar-title { font-size: 15px; }
}
</style>
@endpush

@section('content')
<div class="mi-shell">

  {{-- ── Top Bar ───────────────────────────────────────────────── --}}
  <div class="mi-topbar">
    <div class="mi-topbar-left">
      <div class="mi-topbar-icon">
        <i class="fa fa-sliders"></i>
      </div>
      <div>
        <div class="mi-topbar-title">Master Inputs</div>
        <div class="mi-topbar-sub">Shared parameters · auto-saves on change</div>
      </div>
    </div>
    <div class="mi-topbar-right">
      <div class="mi-save-pill">
        <div class="mi-save-dot ready" id="mi_save_dot"></div>
        <span id="mi_save_label">Ready</span>
      </div>
      <button class="mi-btn-reset" onclick="resetInputs()">
        <i class="fa fa-rotate" style="font-size:11px;"></i> Reset
      </button>
      <button class="mi-btn-continue" onclick="markComplete()">
        Continue <i class="fa fa-arrow-right" style="font-size:11px;"></i>
      </button>
    </div>
  </div>

  {{-- ── Alerts ────────────────────────────────────────────────── --}}
  <div style="max-width:1440px;margin:0 auto;width:100%;padding:0 1.5rem;">
    <div class="mi-alert mi-alert-err" id="mi_err" role="alert">
      <i class="fa fa-circle-exclamation"></i>
      <span id="mi_err_text"></span>
    </div>
    <div class="mi-alert mi-alert-ok" id="mi_ok" role="alert">
      <i class="fa fa-circle-check"></i>
      <span id="mi_ok_text"></span>
    </div>
  </div>

  {{-- ── Layout ────────────────────────────────────────────────── --}}
  <div class="mi-layout">

    {{-- ── Sidebar Nav ─────────────────────────────────────────── --}}
    <nav class="mi-sidebar" aria-label="Section navigation">
      <div class="mi-nav-header">Sections</div>

      <a class="mi-nav-item active" href="#" data-panel="panel-core" onclick="switchPanel('panel-core', this); return false;">
        <div class="mi-nav-dot"><i class="fa fa-gauge-high" style="font-size:12px;"></i></div>
        <div>
          <div class="mi-nav-label">Core Controls</div>
          <div class="mi-nav-count">9 fields</div>
        </div>
      </a>

      <a class="mi-nav-item" href="#" data-panel="panel-burden" onclick="switchPanel('panel-burden', this); return false;">
        <div class="mi-nav-dot"><i class="fa fa-layer-group" style="font-size:12px;"></i></div>
        <div>
          <div class="mi-nav-label">Fringe &amp; Burden</div>
          <div class="mi-nav-count">13 fields</div>
        </div>
      </a>

      <a class="mi-nav-item" href="#" data-panel="panel-ops" onclick="switchPanel('panel-ops', this); return false;">
        <div class="mi-nav-dot"><i class="fa fa-building-shield" style="font-size:12px;"></i></div>
        <div>
          <div class="mi-nav-label">Operations &amp; Factors</div>
          <div class="mi-nav-count">18 fields</div>
        </div>
      </a>

      <div class="mi-nav-divider"></div>
      <div class="mi-nav-header">Reference</div>

      <a class="mi-nav-item" href="#" data-panel="panel-vehicles" onclick="switchPanel('panel-vehicles', this); return false;">
        <div class="mi-nav-dot"><i class="fa fa-car" style="font-size:12px;"></i></div>
        <div>
          <div class="mi-nav-label">Vehicles &amp; Escalation</div>
          <div class="mi-nav-count">7 fields</div>
        </div>
      </a>
    </nav>

    {{-- ── Content Panels ──────────────────────────────────────── --}}
    <main class="mi-content">

      {{-- ════ PANEL 1: Core Controls ════ --}}
      <div class="mi-panel active" id="panel-core">
        <div class="mi-panel-head">
          <div>
            <h2 class="mi-panel-title">Core Controls</h2>
            <p class="mi-panel-desc">Base wage and time parameters that flow into every calculator across the platform.</p>
          </div>
        </div>

        <div class="mi-section-label">Labor</div>
        <div class="row g-3">
          <div class="col-lg-4 col-md-6">
            @include('partials.mi-field', [
              'id'    => 'mi_directLaborWage',
              'label' => 'Direct Labor Wage',
              'help'  => '$ / paid hour',
              'unit'  => '$',
              'unit_pos' => 'prefix',
              'step'  => '0.01',
              'min'   => '0',
              'max_slider' => '150',
            ])
          </div>
          <div class="col-lg-4 col-md-6">
            @include('partials.mi-field', [
              'id'    => 'mi_annualPaidHoursPerFte',
              'label' => 'Annual Paid Hours / FTE',
              'help'  => 'hours per year',
              'unit'  => 'hrs',
              'step'  => '1',
              'min'   => '0',
              'max_slider' => '4000',
            ])
          </div>
          <div class="col-lg-4 col-md-6">
            @include('partials.mi-field', [
              'id'    => 'mi_annualProductiveCoverageHoursPerFte',
              'label' => 'Productive Coverage Hours / FTE',
              'help'  => 'hours per year',
              'unit'  => 'hrs',
              'step'  => '1',
              'min'   => '0',
              'max_slider' => '4000',
            ])
          </div>
        </div>

        <div class="mi-section-label">Premiums</div>
        <div class="row g-3">
          @php
            $pctFields = [
              ['k'=>'localityPayPct',       'label'=>'Locality Pay',              'max'=>50],
              ['k'=>'shiftDifferentialPct', 'label'=>'Shift Differential',        'max'=>50],
              ['k'=>'otHolidayPremiumPct',  'label'=>'OT / Holiday Premium',      'max'=>50],
              ['k'=>'laborMarketAdjPct',    'label'=>'Labor Market Adjustment',   'max'=>50],
            ];
          @endphp
          @foreach($pctFields as $f)
            <div class="col-md-6 col-lg-3">
              @include('partials.mi-field', [
                'id'    => 'mi_'.$f['k'],
                'label' => $f['label'],
                'help'  => 'percentage',
                'unit'  => '%',
                'step'  => '0.01',
                'min'   => '0',
                'max_slider' => $f['max'],
                'data_unit' => 'pct',
              ])
            </div>
          @endforeach
        </div>

        <div class="mi-section-label">Benefits</div>
        <div class="row g-3">
          <div class="col-md-6">
            @include('partials.mi-field', [
              'id'    => 'mi_hwCashPerHour',
              'label' => 'H&W Cash',
              'help'  => '$ / paid hour',
              'unit'  => '$',
              'unit_pos' => 'prefix',
              'step'  => '0.01',
              'min'   => '0',
              'max_slider' => '50',
            ])
          </div>
          <div class="col-md-6">
            @include('partials.mi-field', [
              'id'    => 'mi_donDoffMinutesPerShift',
              'label' => 'DON / DOFF Time',
              'help'  => 'minutes per 8-hour shift',
              'unit'  => 'min',
              'step'  => '1',
              'min'   => '0',
              'max_slider' => '120',
            ])
          </div>
        </div>
      </div>

      {{-- ════ PANEL 2: Fringe & Burden ════ --}}
      <div class="mi-panel" id="panel-burden">
        <div class="mi-panel-head">
          <div>
            <h2 class="mi-panel-title">Fringe &amp; Burden</h2>
            <p class="mi-panel-desc">Statutory payroll taxes, insurance costs, paid leave, and corporate overhead percentages.</p>
          </div>
        </div>

        <div class="mi-section-label">Statutory &amp; Insurance</div>
        <div class="row g-3">
          @php
            $burden = [
              ['k'=>'ficaMedicarePct',       'label'=>'FICA / Medicare',                  'max'=>20],
              ['k'=>'futaPct',               'label'=>'FUTA',                             'max'=>5],
              ['k'=>'sutaPct',               'label'=>'SUTA',                             'max'=>20],
              ['k'=>'workersCompPct',        'label'=>'Workers Compensation',             'max'=>20],
              ['k'=>'generalLiabilityPct',   'label'=>'General Liability Insurance',      'max'=>20],
              ['k'=>'umbrellaInsurancePct',  'label'=>'Umbrella / Other Insurance',       'max'=>5],
            ];
          @endphp
          @foreach($burden as $f)
            <div class="col-md-6 col-lg-4">
              @include('partials.mi-field', [
                'id'    => 'mi_'.$f['k'],
                'label' => $f['label'],
                'help'  => 'percentage',
                'unit'  => '%',
                'step'  => '0.01',
                'min'   => '0',
                'max_slider' => $f['max'],
                'data_unit' => 'pct',
              ])
            </div>
          @endforeach
        </div>

        <div class="mi-section-label">Paid Leave</div>
        <div class="row g-3">
          @php
            $leave = [
              ['k'=>'vacationPct',     'label'=>'Vacation',      'max'=>20],
              ['k'=>'paidHolidaysPct', 'label'=>'Paid Holidays', 'max'=>20],
              ['k'=>'sickLeavePct',    'label'=>'Sick Leave',     'max'=>20],
            ];
          @endphp
          @foreach($leave as $f)
            <div class="col-md-6 col-lg-4">
              @include('partials.mi-field', [
                'id'    => 'mi_'.$f['k'],
                'label' => $f['label'],
                'help'  => 'percentage',
                'unit'  => '%',
                'step'  => '0.01',
                'min'   => '0',
                'max_slider' => $f['max'],
                'data_unit' => 'pct',
              ])
            </div>
          @endforeach
          <div class="col-md-6 col-lg-4">
            @include('partials.mi-field', [
              'id'    => 'mi_healthWelfarePerHour',
              'label' => 'Health &amp; Welfare',
              'help'  => '$ / paid hour',
              'unit'  => '$',
              'unit_pos' => 'prefix',
              'step'  => '0.01',
              'min'   => '0',
              'max_slider' => '50',
            ])
          </div>
        </div>

        <div class="mi-section-label">Corporate Overhead</div>
        <div class="row g-3">
          @php
            $overhead = [
              ['k'=>'corporateOverheadPct', 'label'=>'Corporate Overhead', 'max'=>30],
              ['k'=>'gaPct',                'label'=>'G &amp; A',          'max'=>30],
              ['k'=>'profitFeePct',         'label'=>'Profit / Fee',       'max'=>100],
            ];
          @endphp
          @foreach($overhead as $f)
            <div class="col-md-6 col-lg-4">
              @include('partials.mi-field', [
                'id'    => 'mi_'.$f['k'],
                'label' => $f['label'],
                'help'  => 'percentage',
                'unit'  => '%',
                'step'  => '0.01',
                'min'   => '0',
                'max_slider' => $f['max'],
                'data_unit' => 'pct',
              ])
            </div>
          @endforeach
        </div>
      </div>

      {{-- ════ PANEL 3: Operations & Factors ════ --}}
      <div class="mi-panel" id="panel-ops">
        <div class="mi-panel-head">
          <div>
            <h2 class="mi-panel-title">Operations &amp; Factors</h2>
            <p class="mi-panel-desc">Operational cost drivers, vendor comparison factors, and government contracting parameters.</p>
          </div>
        </div>

        <div class="mi-section-label">Operations Support</div>
        <div class="row g-3">
          @php
            $ops = [
              ['k'=>'recruitingHiringPct',      'label'=>'Recruiting / Hiring',      'max'=>20],
              ['k'=>'trainingCertificationPct',  'label'=>'Training / Certification', 'max'=>20],
              ['k'=>'uniformsEquipmentPct',      'label'=>'Uniforms / Equipment',     'max'=>20],
              ['k'=>'fieldSupervisionPct',       'label'=>'Field Supervision',        'max'=>20],
              ['k'=>'contractManagementPct',     'label'=>'Contract Management',      'max'=>20],
              ['k'=>'qualityAssurancePct',       'label'=>'Quality Assurance',        'max'=>20],
              ['k'=>'vehiclesPatrolPct',         'label'=>'Vehicles / Patrol',        'max'=>30],
              ['k'=>'technologySystemsPct',      'label'=>'Technology / Systems',     'max'=>20],
            ];
          @endphp
          @foreach($ops as $f)
            <div class="col-md-6 col-lg-3">
              @include('partials.mi-field', [
                'id'    => 'mi_'.$f['k'],
                'label' => $f['label'],
                'help'  => 'percentage',
                'unit'  => '%',
                'step'  => '0.01',
                'min'   => '0',
                'max_slider' => $f['max'],
                'data_unit' => 'pct',
              ])
            </div>
          @endforeach
        </div>

        <div class="mi-section-label">Vendor / Government Factors</div>
        <div class="row g-3">
          @php
            $gov = [
              ['k'=>'vendorTcoFactorVsGovTco',        'label'=>'Vendor TCO Factor vs Gov TCO',        'max'=>120, 'step'=>'0.1'],
              ['k'=>'vendorFloorFactorVsVendorTco',    'label'=>'Vendor Floor Factor vs Vendor TCO',   'max'=>120, 'step'=>'0.1'],
              ['k'=>'governmentFullBurdenLaborShare',  'label'=>'Gov Full Burden Labor Share',          'max'=>100, 'step'=>'0.1'],
            ];
          @endphp
          @foreach($gov as $f)
            <div class="col-md-6 col-lg-4">
              @include('partials.mi-field', [
                'id'    => 'mi_'.$f['k'],
                'label' => $f['label'],
                'help'  => 'percentage',
                'unit'  => '%',
                'step'  => $f['step'],
                'min'   => '0',
                'max_slider' => $f['max'],
                'data_unit' => 'pct',
              ])
            </div>
          @endforeach

          <div class="col-md-6 col-lg-4">
            @include('partials.mi-field', [
              'id'    => 'mi_minWeeklyHoursForFloorEligibility',
              'label' => 'Min Weekly Hours for Floor Eligibility',
              'help'  => 'hours',
              'unit'  => 'hrs',
              'step'  => '1',
              'min'   => '0',
              'max_slider' => '6000',
            ])
          </div>
          <div class="col-md-6 col-lg-4">
            @include('partials.mi-field', [
              'id'    => 'mi_governmentWorkforceHoursBasis',
              'label' => 'Government Workforce Hours Basis',
              'help'  => 'hours',
              'unit'  => 'hrs',
              'step'  => '1',
              'min'   => '0',
              'max_slider' => '12000',
            ])
          </div>
          <div class="col-md-6 col-lg-4">
            @include('partials.mi-field', [
              'id'    => 'mi_governmentTcoMultiplierMin',
              'label' => 'Government TCO Multiplier (Min)',
              'help'  => 'multiplier',
              'unit'  => '×',
              'step'  => '0.1',
              'min'   => '0',
              'max_slider' => '10',
            ])
          </div>
          <div class="col-md-6 col-lg-4">
            @include('partials.mi-field', [
              'id'    => 'mi_governmentTcoMultiplierMax',
              'label' => 'Government TCO Multiplier (Max)',
              'help'  => 'multiplier',
              'unit'  => '×',
              'step'  => '0.1',
              'min'   => '0',
              'max_slider' => '10',
            ])
          </div>
        </div>
      </div>

      {{-- ════ PANEL 4: Vehicles & Escalation ════ --}}
      <div class="mi-panel" id="panel-vehicles">
        <div class="mi-panel-head">
          <div>
            <h2 class="mi-panel-title">Vehicles &amp; Escalation</h2>
            <p class="mi-panel-desc">Fleet parameters and annual cost escalation rates for multi-year projections.</p>
          </div>
        </div>

        <div class="mi-section-label">Vehicle Fleet</div>
        <div class="row g-3">
          <div class="col-md-6 col-lg-4">
            @include('partials.mi-field', [
              'id'    => 'mi_vehiclesRequired',
              'label' => 'Vehicles Required',
              'help'  => 'count',
              'unit'  => 'veh',
              'step'  => '1',
              'min'   => '0',
              'max_slider' => '50',
            ])
          </div>
          <div class="col-md-6 col-lg-4">
            @include('partials.mi-field', [
              'id'    => 'mi_avgMilesPerVehiclePerDay',
              'label' => 'Avg Miles per Vehicle / Day',
              'help'  => 'miles',
              'unit'  => 'mi',
              'step'  => '1',
              'min'   => '0',
              'max_slider' => '1000',
            ])
          </div>
          <div class="col-md-6 col-lg-4">
            @include('partials.mi-field', [
              'id'    => 'mi_fuelCostPerGallon',
              'label' => 'Fuel Cost per Gallon',
              'help'  => 'dollars',
              'unit'  => '$',
              'unit_pos' => 'prefix',
              'step'  => '0.01',
              'min'   => '0',
              'max_slider' => '15',
            ])
          </div>
        </div>

        <div class="mi-section-label">Annual Escalation Rates</div>
        <div class="row g-3">
          @php
            $esc = [
              ['k'=>'customAnnualEscalationPct', 'label'=>'Custom Annual Escalation', 'max'=>25],
              ['k'=>'lowEscalationPct',          'label'=>'Low Escalation',           'max'=>25],
              ['k'=>'mediumEscalationPct',        'label'=>'Medium Escalation',        'max'=>25],
              ['k'=>'highEscalationPct',          'label'=>'High Escalation',          'max'=>25],
            ];
          @endphp
          @foreach($esc as $f)
            <div class="col-md-6 col-lg-3">
              @include('partials.mi-field', [
                'id'    => 'mi_'.$f['k'],
                'label' => $f['label'],
                'help'  => 'percentage',
                'unit'  => '%',
                'step'  => '0.1',
                'min'   => '0',
                'max_slider' => $f['max'],
                'data_unit' => 'pct',
              ])
            </div>
          @endforeach
        </div>
      </div>

    </main>
  </div>{{-- /mi-layout --}}
</div>{{-- /mi-shell --}}
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

  /* ── Save state UI ──────────────────────────────────────────── */
  const setSaveState = (state) => {
    const dot   = document.getElementById('mi_save_dot');
    const label = document.getElementById('mi_save_label');
    if(!dot || !label) return;
    dot.className = 'mi-save-dot ' + state;
    if(state === 'saving') label.textContent = 'Saving…';
    if(state === 'saved'){
      const t = lastSavedAt ? lastSavedAt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}) : '';
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
    el.classList.toggle('show', !!msg);
  };

  const flashOk = (msg) => {
    const el = document.getElementById('mi_ok');
    const tx = document.getElementById('mi_ok_text');
    if(!el || !tx) return;
    tx.textContent = msg || '';
    el.classList.add('show');
    setTimeout(()=> el.classList.remove('show'), 2000);
  };

  /* ── Slider fill progress (CSS custom prop) ─────────────────── */
  const updateSliderFill = (rangeEl) => {
    const min = parseFloat(rangeEl.min || '0');
    const max = parseFloat(rangeEl.max || '100');
    const val = parseFloat(rangeEl.value || '0');
    const pct = Math.round(((val - min) / (max - min)) * 1000) / 10;
    rangeEl.style.setProperty('--pct', pct + '%');
  };

  /* ── Slider ↔ number sync ───────────────────────────────────── */
  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

  function initSliderSync(){
    document.querySelectorAll('input[type="range"][data-sync]').forEach((rangeEl) => {
      const id    = rangeEl.getAttribute('data-sync');
      const numEl = document.getElementById(id);
      if(!numEl) return;

      const syncRangeFromNumber = () => {
        const min = parseFloat(rangeEl.min || '0');
        const max = parseFloat(rangeEl.max || '100');
        const v   = parseFloat(numEl.value || rangeEl.value || '0');
        rangeEl.value = String(clamp(v, min, max));
        updateSliderFill(rangeEl);
      };
      const syncNumberFromRange = () => {
        numEl.value = rangeEl.value;
        updateSliderFill(rangeEl);
      };

      syncRangeFromNumber();

      rangeEl.addEventListener('input', () => { syncNumberFromRange(); scheduleSave(); });
      numEl.addEventListener('input',   () => { syncRangeFromNumber(); scheduleSave(); });
    });
  }

  /* ── Percent key set ────────────────────────────────────────── */
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
    Object.keys(DEFAULTS).forEach((k) => {
      const el = document.getElementById('mi_'+k);
      if(!el) return;
      const v = parseFloat(el.value);
      if(!Number.isFinite(v)){ out[k] = DEFAULTS[k]; return; }
      out[k] = PERCENT_KEYS.has(k) ? (v / 100.0) : v;
    });
    return out;
  }

  function fillFormFromInputs(inputs){
    Object.entries(inputs||{}).forEach(([k,v]) => {
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
      const res  = await fetch(showUrl, { headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(()=>null);
      if(!res.ok || !data || !data.ok) return;
      fillFormFromInputs(data.inputs || {});
      setSaveState('ready');
    }catch(e){ return; }
  }

  window.resetInputs = async function(){
    fillFormFromInputs(DEFAULTS);
    initSliderSync();
    const ok = await saveNow(false);
    if(ok) flashOk('Reset to defaults');
  };

  window.markComplete = async function(){
    const ok = await saveNow(true);
    if(ok){
      flashOk('Saved — redirecting…');
      setTimeout(() => { window.location.href = @json(route('calculator.index')); }, 700);
    }
  };

  /* ── Panel switching ────────────────────────────────────────── */
  window.switchPanel = function(panelId, navEl){
    document.querySelectorAll('.mi-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.mi-nav-item').forEach(n => n.classList.remove('active'));
    const panel = document.getElementById(panelId);
    if(panel) panel.classList.add('active');
    if(navEl) navEl.classList.add('active');
    // Re-init sliders so fill is correct for newly visible panel
    initSliderSync();
  };

  /* ── Boot ───────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', async () => {
    await loadFromServer();
    initSliderSync();
    // Fallback: fill from PHP defaults for any still-empty inputs
    Object.entries(DEFAULTS).forEach(([k,v]) => {
      const el = document.getElementById('mi_'+k);
      if(!el || el.value !== '') return;
      if(PERCENT_KEYS.has(k) && typeof v === 'number') el.value = (v * 100.0);
      else el.value = v;
    });
    initSliderSync();
    setSaveState('ready');
  });
})();
</script>
@endpush
