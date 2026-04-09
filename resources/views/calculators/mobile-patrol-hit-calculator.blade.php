@extends('layouts.app')

@section('title', 'Mobile Patrol Hit Calculator')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .mphc-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 30%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .mphc-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .mphc-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .mphc-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .mphc-section + .mphc-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .mphc-panel {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .mphc-panel-muted {
    background: rgba(6, 45, 121, 0.04);
  }
  .mphc-stat {
    border: 1px solid rgba(6, 45, 121, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .mphc-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .mphc-stat-value {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--gasq-primary);
    font-variant-numeric: tabular-nums;
  }
  .mphc-chip {
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
  .mphc-mono {
    font-variant-numeric: tabular-nums;
  }
  @media (max-width: 1199.98px) {
    .mphc-sticky {
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
            <i class="fa fa-bullseye text-primary"></i> Mobile Patrol Hit Calculator
          </h1>
          <div class="text-gasq-muted small">Shared input rail with live cost-per-hit and bill-per-hit outputs.</div>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2 d-print-none">
        <button class="btn btn-outline-secondary btn-sm" onclick="resetDefaults()"><i class="fa fa-rotate me-1"></i> Reset</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      </div>
    </div>

    <div class="card gasq-card mphc-shell overflow-hidden">
      <div class="card-body p-0">
        <div class="row g-0">
          <div class="col-xl-4 border-end mphc-sidebar">
            <div class="p-3 p-md-4 mphc-sticky">
              <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                <div>
                  <div class="mphc-kicker mb-2">Shared Inputs</div>
                  <h2 class="h4 fw-bold mb-2">Hit Model Controls</h2>
                  <p class="small text-gasq-muted mb-0">Daily coverage, hits, vehicle assumptions, and labor inputs all live here and update the results workspace on the right in real time.</p>
                </div>
                <span class="mphc-chip"><i class="fa fa-bolt"></i> Live</span>
              </div>

              <div class="mphc-section">
                <h5 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                  <i class="fa fa-calendar-days text-primary"></i> Service Volume
                </h5>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Days / year</label>
                    <input type="number" id="i_days" class="form-control form-control-sm" value="365" min="1" step="1">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Hours / day</label>
                    <input type="number" id="i_hours" class="form-control form-control-sm" value="24" min="0" step="0.25">
                  </div>
                  <div class="col-12">
                    <label class="form-label small fw-medium">Hits / day</label>
                    <input type="number" id="i_hits" class="form-control form-control-sm" value="180" min="0" step="1">
                    <div class="form-text">Stops or checkpoints completed per day.</div>
                  </div>
                  <div class="col-12">
                    <label class="form-label small fw-medium">Markup %</label>
                    <input type="number" id="i_markup" class="form-control form-control-sm" value="27" min="0" step="0.1">
                  </div>
                </div>
              </div>

              <div class="mphc-section">
                <h5 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                  <i class="fa fa-route text-primary"></i> Vehicle &amp; Operating
                </h5>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Miles / day</label>
                    <input type="number" id="i_miles" class="form-control form-control-sm" value="360" min="0" step="1">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Cost / mile ($)</label>
                    <input type="number" id="i_cpm" class="form-control form-control-sm" value="0.67" min="0" step="0.01">
                  </div>
                  <div class="col-12">
                    <label class="form-label small fw-medium">Equipment / day ($)</label>
                    <input type="number" id="i_equip" class="form-control form-control-sm" value="0" min="0" step="0.01">
                  </div>
                </div>
              </div>

              <div class="mphc-section">
                <h5 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                  <i class="fa fa-user-clock text-primary"></i> Labor
                </h5>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Regular hrs/day</label>
                    <input type="number" id="i_regH" class="form-control form-control-sm" value="24" min="0" step="0.25">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">OT hrs/day</label>
                    <input type="number" id="i_otH" class="form-control form-control-sm" value="0" min="0" step="0.25">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">Regular $/hr</label>
                    <input type="number" id="i_regR" class="form-control form-control-sm" value="30.00" min="0" step="0.01">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-medium">OT $/hr</label>
                    <input type="number" id="i_otR" class="form-control form-control-sm" value="45.00" min="0" step="0.01">
                  </div>
                </div>
              </div>

              <div class="mphc-section">
                <div class="alert alert-light border gasq-border small mb-0">
                  Uses a single-day model: <span class="fw-semibold">daily cost</span> is divided by <span class="fw-semibold">hits/day</span> to estimate <span class="fw-semibold">cost per hit</span>.
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-8">
            <div class="p-3 p-md-4">
              <div class="alert alert-light border gasq-border small d-print-none mb-3" id="mphc_error" style="display:none"></div>

              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <div class="mphc-stat">
                    <div class="mphc-stat-label mb-2">Cost Per Hit</div>
                    <div class="mphc-stat-value" id="o_costPerHit">$0.0000</div>
                    <div class="small text-gasq-muted">Total daily cost divided by hits per day</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mphc-stat">
                    <div class="mphc-stat-label mb-2">Bill Per Hit</div>
                    <div class="mphc-stat-value" id="o_billPerHit">$0.0000</div>
                    <div class="small text-gasq-muted">Includes current markup assumptions</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mphc-stat">
                    <div class="mphc-stat-label mb-2">Bill Rate Per Hour</div>
                    <div class="mphc-stat-value" id="o_billHr">$0.00/hr</div>
                    <div class="small text-gasq-muted">Bill per day divided by hours per day</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mphc-stat">
                    <div class="mphc-stat-label mb-2">Annual Bill Total</div>
                    <div class="mphc-stat-value" id="o_annualBill">$0.00</div>
                    <div class="small text-gasq-muted">Bill per day multiplied by days per year</div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                  <div class="mphc-kicker mb-1">Results Workspace</div>
                  <h3 class="h5 fw-bold mb-0">Live Hit Calculator Outputs</h3>
                </div>
                <div class="small text-gasq-muted">The full daily and annual hit breakdown below updates from the shared input rail on the left.</div>
              </div>

              <div class="row g-3">
                <div class="col-lg-5">
                  <div class="mphc-panel mphc-panel-muted p-3 h-100">
                    <div class="small text-gasq-muted mb-1">Decision Snapshot</div>
                    <h4 class="fw-bold mb-3">Per-hit pricing at current patrol assumptions</h4>
                    <div class="d-flex justify-content-between mb-2">
                      <span class="text-gasq-muted small">Hits per day</span>
                      <span class="fw-medium mphc-mono" id="o_hitsDay">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                      <span class="text-gasq-muted small">Annual hits</span>
                      <span class="fw-medium mphc-mono" id="o_hitsAnnual">0</span>
                    </div>
                    <div class="d-flex justify-content-between">
                      <span class="text-gasq-muted small">Hours per day</span>
                      <span class="fw-medium mphc-mono" id="o_hoursDay">0</span>
                    </div>
                  </div>
                </div>

                <div class="col-lg-7">
                  <div class="mphc-panel p-0 overflow-hidden">
                    <div class="p-3 border-bottom">
                      <h5 class="fw-semibold mb-0">Breakdown</h5>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-striped align-middle mb-0 small">
                        <tbody>
                          <tr><td class="text-gasq-muted">Mileage cost / day</td><td class="text-end mphc-mono" id="o_mileage">$0.00</td></tr>
                          <tr><td class="text-gasq-muted">Operating cost / day</td><td class="text-end mphc-mono" id="o_operating">$0.00</td></tr>
                          <tr><td class="text-gasq-muted">Labor cost / day</td><td class="text-end mphc-mono" id="o_labor">$0.00</td></tr>
                          <tr class="fw-semibold"><td>Total cost / day</td><td class="text-end mphc-mono" id="o_total">$0.00</td></tr>
                          <tr><td class="text-gasq-muted">Bill / day (with markup)</td><td class="text-end mphc-mono text-primary" id="o_billDay">$0.00</td></tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-4">
                <x-report-actions reportType="mobile-patrol-hit-calculator" />
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
  const savedScenario = window.__gasqCalculatorState?.scenario || null;
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'mobile-patrol-hit-calculator']));
  const DEFAULTS = {
    i_days: 365,
    i_hours: 24,
    i_hits: 180,
    i_markup: 27,
    i_miles: 360,
    i_cpm: 0.67,
    i_equip: 0,
    i_regH: 24,
    i_otH: 0,
    i_regR: 30.00,
    i_otR: 45.00,
  };
  let t = null;
  let inflight = null;

  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const money4 = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:4,maximumFractionDigits:4}).format(n||0);
  const number0 = (n) => new Intl.NumberFormat('en-US',{maximumFractionDigits:0}).format(n||0);
  const number2 = (n) => new Intl.NumberFormat('en-US',{minimumFractionDigits:0,maximumFractionDigits:2}).format(n||0);
  const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };
  const setError = (msg) => {
    const el = document.getElementById('mphc_error');
    if(!el) return;
    if(!msg){ el.style.display='none'; el.textContent=''; return; }
    el.style.display='';
    el.textContent = msg;
  };

  function payload(){
    return {
      version: 'v24',
      scenario: { meta: {
        daysPerYear: parseFloat(i_days.value)||365,
        hoursPerDay: parseFloat(i_hours.value)||0,
        hitsPerDay: parseFloat(i_hits.value)||0,
        markupPct: parseFloat(i_markup.value)||0,
        milesPerDay: parseFloat(i_miles.value)||0,
        costPerMile: parseFloat(i_cpm.value)||0,
        equipmentPerDay: parseFloat(i_equip.value)||0,
        regularHoursPerDay: parseFloat(i_regH.value)||0,
        overtimeHoursPerDay: parseFloat(i_otH.value)||0,
        regularHourlyUsd: parseFloat(i_regR.value)||0,
        overtimeHourlyUsd: parseFloat(i_otR.value)||0,
      } }
    };
  }

  function hydrateSavedState(){
    const meta = savedScenario?.meta || {};
    const map = {
      i_days: meta.daysPerYear,
      i_hours: meta.hoursPerDay,
      i_hits: meta.hitsPerDay,
      i_markup: meta.markupPct,
      i_miles: meta.milesPerDay,
      i_cpm: meta.costPerMile,
      i_equip: meta.equipmentPerDay,
      i_regH: meta.regularHoursPerDay,
      i_otH: meta.overtimeHoursPerDay,
      i_regR: meta.regularHourlyUsd,
      i_otR: meta.overtimeHourlyUsd,
    };

    Object.entries(map).forEach(([id, value]) => {
      if(value === undefined || value === null) return;
      const el = document.getElementById(id);
      if(el) el.value = value;
    });
  }

  async function compute(){
    try{
      setError('');
      if(inflight){ inflight.abort(); }
      inflight = new AbortController();
      const res = await fetch(url, {
        method: 'POST',
        signal: inflight.signal,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload())
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
      const k = data.kpis || {};
      const d = k.daily || {};
      const a = k.annual || {};
      set('o_mileage', money(d.mileageCost));
      set('o_operating', money(d.operatingCost));
      set('o_labor', money(d.laborCost));
      set('o_total', money(d.totalCost));
      set('o_billDay', money(d.billPerDay));
      set('o_costPerHit', money4(d.costPerHit));
      set('o_billPerHit', money4(d.billPerHit));
      set('o_billHr', money(d.billRatePerHour) + '/hr');
      set('o_annualBill', money(a.billTotal));
      set('o_hitsAnnual', number0(a.totalHits));
      set('o_hitsDay', number0(parseFloat(i_hits.value) || 0));
      set('o_hoursDay', number2(parseFloat(i_hours.value) || 0));
    }catch(e){
      if(e?.name === 'AbortError') return;
      console.error(e);
      setError('Unable to calculate right now. Please try again.');
    }
  }

  function schedule(){
    clearTimeout(t);
    t = setTimeout(compute, 250);
  }

  window.resetDefaults = function(){
    Object.entries(DEFAULTS).forEach(([id, value]) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    });
    compute();
  };

  document.addEventListener('DOMContentLoaded', () => {
    hydrateSavedState();
    document.querySelectorAll('input').forEach(el => el.addEventListener('input', schedule));
    compute();
  });
})();
</script>
@endpush
