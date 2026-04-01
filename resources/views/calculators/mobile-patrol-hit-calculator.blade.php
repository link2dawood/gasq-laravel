@extends('layouts.app')

@section('title', 'Mobile Patrol Hit Calculator')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .mphc-kpi { background: rgba(6,45,121,0.04); border: 1px solid rgba(6,45,121,0.12); }
  .mphc-kpi .label { font-size: 0.78rem; color: var(--gasq-muted); }
  .mphc-kpi .value { font-variant-numeric: tabular-nums; }
  .mphc-mono { font-variant-numeric: tabular-nums; }
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
          <div class="text-gasq-muted small">Estimate cost per stop (“hit”) and billable per hit.</div>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2 d-print-none">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="card gasq-card">
          <div class="card-header d-flex align-items-center gap-2 py-3">
            <i class="fa fa-sliders text-primary"></i>
            <h5 class="card-title mb-0 fw-semibold">Inputs</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label small fw-medium">Days / year</label>
                <input type="number" id="i_days" class="form-control form-control-sm" value="365" min="1" step="1">
              </div>
              <div class="col-6">
                <label class="form-label small fw-medium">Hours / day</label>
                <input type="number" id="i_hours" class="form-control form-control-sm" value="24" min="0" step="0.25">
              </div>
              <div class="col-6">
                <label class="form-label small fw-medium">Hits / day</label>
                <input type="number" id="i_hits" class="form-control form-control-sm" value="180" min="0" step="1">
                <div class="form-text">Stops / checkpoints completed per day.</div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-medium">Markup %</label>
                <input type="number" id="i_markup" class="form-control form-control-sm" value="27" min="0" step="0.1">
              </div>
            </div>

            <hr class="my-3">

            <h6 class="fw-semibold d-flex align-items-center gap-2 mb-2">
              <i class="fa fa-route text-primary"></i> Vehicle & operating
            </h6>
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label small fw-medium">Miles / day</label>
                <input type="number" id="i_miles" class="form-control form-control-sm" value="360" min="0" step="1">
              </div>
              <div class="col-6">
                <label class="form-label small fw-medium">Cost / mile ($)</label>
                <input type="number" id="i_cpm" class="form-control form-control-sm" value="0.67" min="0" step="0.01">
              </div>
              <div class="col-12">
                <label class="form-label small fw-medium">Equipment / day ($)</label>
                <input type="number" id="i_equip" class="form-control form-control-sm" value="0" min="0" step="0.01">
              </div>
            </div>

            <hr class="my-3">

            <h6 class="fw-semibold d-flex align-items-center gap-2 mb-2">
              <i class="fa fa-user-clock text-primary"></i> Labor
            </h6>
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label small fw-medium">Regular hrs/day</label>
                <input type="number" id="i_regH" class="form-control form-control-sm" value="24" min="0" step="0.25">
              </div>
              <div class="col-6">
                <label class="form-label small fw-medium">OT hrs/day</label>
                <input type="number" id="i_otH" class="form-control form-control-sm" value="0" min="0" step="0.25">
              </div>
              <div class="col-6">
                <label class="form-label small fw-medium">Regular $/hr</label>
                <input type="number" id="i_regR" class="form-control form-control-sm" value="30.00" min="0" step="0.01">
              </div>
              <div class="col-6">
                <label class="form-label small fw-medium">OT $/hr</label>
                <input type="number" id="i_otR" class="form-control form-control-sm" value="45.00" min="0" step="0.01">
              </div>
            </div>

            <div class="alert alert-light border gasq-border small mt-3 mb-0">
              Uses a single-day model: <span class="fw-semibold">Daily cost</span> is split by <span class="fw-semibold">hits/day</span> to estimate <span class="fw-semibold">cost per hit</span>.
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="rounded p-3 mphc-kpi">
              <div class="label">Cost per hit (daily)</div>
              <div class="h2 fw-bold mb-0 value mphc-mono" id="o_costPerHit">$0.0000</div>
              <div class="small text-gasq-muted">Total daily cost ÷ hits/day</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="rounded p-3 mphc-kpi">
              <div class="label">Bill per hit (daily)</div>
              <div class="h2 fw-bold mb-0 value mphc-mono text-primary" id="o_billPerHit">$0.0000</div>
              <div class="small text-gasq-muted">Includes markup</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="rounded p-3 mphc-kpi">
              <div class="label">Bill rate per hour</div>
              <div class="h2 fw-bold mb-0 value mphc-mono" id="o_billHr">$0.00</div>
              <div class="small text-gasq-muted">Bill/day ÷ hours/day</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="rounded p-3 mphc-kpi">
              <div class="label">Annual bill total</div>
              <div class="h2 fw-bold mb-0 value mphc-mono" id="o_annualBill">$0.00</div>
              <div class="small text-gasq-muted">Bill/day × days/year</div>
            </div>
          </div>
        </div>

        <div class="card gasq-card">
          <div class="card-header d-flex align-items-center gap-2 py-3">
            <i class="fa fa-list-check text-primary"></i>
            <h5 class="card-title mb-0 fw-semibold">Breakdown</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped align-middle mb-0 small">
                <tbody>
                  <tr><td class="text-gasq-muted">Mileage cost / day</td><td class="text-end font-monospace" id="o_mileage">$0.00</td></tr>
                  <tr><td class="text-gasq-muted">Operating cost / day</td><td class="text-end font-monospace" id="o_operating">$0.00</td></tr>
                  <tr><td class="text-gasq-muted">Labor cost / day</td><td class="text-end font-monospace" id="o_labor">$0.00</td></tr>
                  <tr class="fw-semibold"><td>Total cost / day</td><td class="text-end font-monospace" id="o_total">$0.00</td></tr>
                  <tr><td class="text-gasq-muted">Bill / day (with markup)</td><td class="text-end font-monospace text-primary" id="o_billDay">$0.00</td></tr>
                  <tr><td class="text-gasq-muted">Annual hits</td><td class="text-end font-monospace" id="o_hitsAnnual">0</td></tr>
                </tbody>
              </table>
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
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'mobile-patrol-hit-calculator']));
  let t = null;

  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const money4 = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:4,maximumFractionDigits:4}).format(n||0);
  const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };

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

  async function compute(){
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
      },
      body: JSON.stringify(payload())
    });
    const data = await res.json();
    if(!res.ok || !data.ok){ console.error(data); return; }
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
    set('o_hitsAnnual', (a.totalHits||0).toLocaleString('en-US'));
  }

  function schedule(){
    clearTimeout(t);
    t = setTimeout(compute, 250);
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input').forEach(el => el.addEventListener('input', schedule));
    compute();
  });
})();
</script>
@endpush

