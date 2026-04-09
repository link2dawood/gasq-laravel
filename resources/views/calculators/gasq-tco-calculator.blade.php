@extends('layouts.app')

@section('header_variant', 'dashboard')
@section('title', 'GASQ TCO Calculator')

@push('styles')
<style>
  .tco-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 30%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .tco-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .tco-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .tco-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .tco-section + .tco-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .tco-panel {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .tco-panel-muted {
    background: rgba(6, 45, 121, 0.04);
  }
  .tco-stat {
    border: 1px solid rgba(6, 45, 121, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .tco-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .tco-stat-value {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--gasq-primary);
    font-variant-numeric: tabular-nums;
  }
  .tco-chip {
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
  .tco-mono {
    font-variant-numeric: tabular-nums;
  }
  @media (max-width: 1199.98px) {
    .tco-sticky {
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
          <i class="fa fa-scale-balanced text-primary"></i> GASQ TCO Calculator
        </h1>
        <div class="text-gasq-muted small">Shared input rail with live should-cost versus vendor TCO outputs.</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetTcoDefaults()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <a class="btn btn-primary btn-sm" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
      <a class="btn btn-outline-primary btn-sm" href="{{ route('jobs.create') }}">Post Your Job</a>
    </div>
  </div>

  <div class="card gasq-card tco-shell overflow-hidden">
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-xl-4 border-end tco-sidebar">
          <div class="p-3 p-md-4 tco-sticky">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
              <div>
                <div class="tco-kicker mb-2">Shared Inputs</div>
                <h2 class="h4 fw-bold mb-2">TCO Model Controls</h2>
                <p class="small text-gasq-muted mb-0">Use the annual hours, vendor benchmark, and GASQ should-cost bill rate inputs here to update the comparison workspace on the right in real time.</p>
              </div>
              <span class="tco-chip"><i class="fa fa-bolt"></i> Live</span>
            </div>

            <div class="tco-section">
              <h5 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-sliders text-primary"></i> Cost Inputs
              </h5>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-medium">Annual billable hours</label>
                  <input type="number" id="tco_hours" class="form-control form-control-sm" value="21322" step="1" min="1">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-medium">Vendor TCO ($/hr)</label>
                  <input type="number" id="tco_vendor" class="form-control form-control-sm" value="54.78" step="0.01" min="0">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-medium">GASQ bill rate ($/hr)</label>
                  <input type="number" id="tco_gasq" class="form-control form-control-sm" value="43.67" step="0.01" min="0">
                  <div class="form-text">Defaults align with the CFO bill rate excerpt.</div>
                </div>
              </div>
            </div>

            <div class="tco-section">
              <div class="alert alert-light border gasq-border small mb-0">
                The calculator compares a <span class="fw-semibold">GASQ should-cost annual total</span> against a <span class="fw-semibold">vendor TCO annual benchmark</span> using the same annual billable hours basis.
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="p-3 p-md-4">
            <div class="alert alert-light border gasq-border small d-print-none mb-3" id="tco_error" style="display:none"></div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <div class="tco-stat">
                  <div class="tco-stat-label mb-2">GASQ Annual Total</div>
                  <div class="tco-stat-value" id="tco_gasqAnnual">$0.00</div>
                  <div class="small text-gasq-muted">Current should-cost annual output</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="tco-stat">
                  <div class="tco-stat-label mb-2">Vendor Annual Total</div>
                  <div class="tco-stat-value" id="tco_vendorAnnual">$0.00</div>
                  <div class="small text-gasq-muted">Current vendor TCO annual output</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="tco-stat">
                  <div class="tco-stat-label mb-2">Premium / Discount Per Hour</div>
                  <div class="tco-stat-value" id="tco_premHr">$0.00</div>
                  <div class="small text-gasq-muted">Vendor compared to GASQ</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="tco-stat">
                  <div class="tco-stat-label mb-2">Premium / Discount Annual</div>
                  <div class="tco-stat-value" id="tco_premAnnual">$0.00</div>
                  <div class="small text-gasq-muted">Annual difference at current hours</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <div class="tco-kicker mb-1">Results Workspace</div>
                <h3 class="h5 fw-bold mb-0">Live TCO Comparison Outputs</h3>
              </div>
              <div class="small text-gasq-muted">The comparison summary below updates from the shared TCO inputs on the left.</div>
            </div>

            <div class="row g-3">
              <div class="col-lg-5">
                <div class="tco-panel tco-panel-muted p-3 h-100">
                  <div class="small text-gasq-muted mb-1">Decision Signal</div>
                  <h4 class="fw-bold mb-3" id="tco_signalHeading">Vendor premium over GASQ</h4>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Annual hours</span>
                    <span class="fw-medium tco-mono" id="tco_hoursOut">0</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">GASQ hourly</span>
                    <span class="fw-medium tco-mono" id="tco_gasqHr">$0.00/hr</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-gasq-muted small">Vendor hourly</span>
                    <span class="fw-medium tco-mono" id="tco_vendorHr">$0.00/hr</span>
                  </div>
                </div>
              </div>

              <div class="col-lg-7">
                <div class="tco-panel p-3 h-100">
                  <h5 class="fw-semibold mb-3">Comparison Breakdown</h5>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">GASQ should-cost annual total</span>
                    <span class="fw-medium tco-mono" id="tco_gasqAnnual_breakdown">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Vendor TCO annual total</span>
                    <span class="fw-medium tco-mono" id="tco_vendorAnnual_breakdown">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Hourly difference</span>
                    <span class="fw-medium tco-mono" id="tco_premHr_breakdown">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-gasq-muted small">Annual difference</span>
                    <span class="fw-semibold tco-mono" id="tco_premAnnual_breakdown">$0.00</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <x-report-actions reportType="gasq-tco-calculator" />
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
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'gasq-tco-calculator']));
  const DEFAULTS = {
    tco_hours: 21322,
    tco_vendor: 54.78,
    tco_gasq: 43.67,
  };
  let t = null;
  let inflight = null;

  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const number0 = (n) => new Intl.NumberFormat('en-US',{maximumFractionDigits:0}).format(n||0);
  const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };
  const setError = (msg) => {
    const el = document.getElementById('tco_error');
    if(!el) return;
    if(!msg){ el.style.display='none'; el.textContent=''; return; }
    el.style.display='';
    el.textContent = msg;
  };

  function payload(){
    return { version:'v24', scenario:{ meta:{
      annualBillableHours: parseFloat(tco_hours.value)||21322,
      vendorTcoHourly: parseFloat(tco_vendor.value)||0,
      gasqBillRateHourly: parseFloat(tco_gasq.value)||0,
      includeReport: false
    } } };
  }

  function updateSignal(summary) {
    const annual = summary.vendorPremiumAnnual || 0;
    const heading = document.getElementById('tco_signalHeading');
    if (annual > 0) {
      heading.textContent = 'Vendor premium over GASQ';
    } else if (annual < 0) {
      heading.textContent = 'Vendor discount versus GASQ';
    } else {
      heading.textContent = 'Vendor and GASQ are currently matched';
    }
  }

  async function compute(){
    try{
      setError('');
      if(inflight){ inflight.abort(); }
      inflight = new AbortController();
      const res = await fetch(url, {
        method:'POST',
        signal: inflight.signal,
        headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
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
      const s = (data.kpis||{}).summary || {};
      set('tco_gasqAnnual', money(s.gasqAnnualTotal));
      set('tco_vendorAnnual', money(s.vendorAnnualTotal));
      set('tco_gasqAnnual_breakdown', money(s.gasqAnnualTotal));
      set('tco_vendorAnnual_breakdown', money(s.vendorAnnualTotal));
      set('tco_gasqHr', money(s.gasqBillRateHourly) + '/hr');
      set('tco_vendorHr', money(s.vendorTcoHourly) + '/hr');
      set('tco_premHr', money(s.vendorPremiumHourly));
      set('tco_premHr_breakdown', money(s.vendorPremiumHourly));
      set('tco_premAnnual', money(s.vendorPremiumAnnual));
      set('tco_premAnnual_breakdown', money(s.vendorPremiumAnnual));
      set('tco_hoursOut', number0(parseFloat(tco_hours.value) || 0));
      updateSignal(s);
    }catch(e){
      if(e?.name === 'AbortError') return;
      console.error(e);
      setError('Unable to calculate right now. Please try again.');
    }
  }

  function schedule(){ clearTimeout(t); t = setTimeout(compute, 200); }

  window.resetTcoDefaults = function(){
    Object.entries(DEFAULTS).forEach(([id, value]) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    });
    compute();
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input').forEach(el => el.addEventListener('input', schedule));
    compute();
  });
})();
</script>
@endpush
