@extends('layouts.app')

@section('header_variant', 'dashboard')
@section('title', 'Government Contract Calculator')

@push('styles')
<style>
  .gcc-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 30%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .gcc-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .gcc-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .gcc-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .gcc-section + .gcc-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .gcc-panel {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .gcc-panel-muted {
    background: rgba(6, 45, 121, 0.04);
  }
  .gcc-stat {
    border: 1px solid rgba(6, 45, 121, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .gcc-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .gcc-stat-value {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--gasq-primary);
    font-variant-numeric: tabular-nums;
  }
  .gcc-chip {
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
  .gcc-mono {
    font-variant-numeric: tabular-nums;
  }
  @media (max-width: 1199.98px) {
    .gcc-sticky {
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
          <i class="fa fa-landmark text-primary"></i> Government Contract Calculator
        </h1>
        <div class="text-gasq-muted small">Shared input rail with live government bill-rate and annual-total outputs.</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetGovernmentDefaults()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <a class="btn btn-primary btn-sm" href="{{ route('pricing') }}">Know Before You Buy</a>
      <a class="btn btn-outline-primary btn-sm" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
    </div>
  </div>

  <div class="card gasq-card gcc-shell overflow-hidden">
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-xl-4 border-end gcc-sidebar">
          <div class="p-3 p-md-4 gcc-sticky">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
              <div>
                <div class="gcc-kicker mb-2">Shared Inputs</div>
                <h2 class="h4 fw-bold mb-2">Contract Model Controls</h2>
                <p class="small text-gasq-muted mb-0">Base wage, burdens, support, overhead, fee, and annual hours all live here and update the government cost workspace on the right in real time.</p>
              </div>
              <span class="gcc-chip"><i class="fa fa-bolt"></i> Live</span>
            </div>

            <div class="gcc-section">
              <h5 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-sliders text-primary"></i> Cost Inputs
              </h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Base wage ($/hr)</label>
                  <input type="number" id="gc_base" class="form-control form-control-sm" value="20.76" step="0.01">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">H&amp;W cash ($/hr)</label>
                  <input type="number" id="gc_hw" class="form-control form-control-sm" value="4.22" step="0.01">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Locality pay (%)</label>
                  <input type="number" id="gc_loc" class="form-control form-control-sm" value="0" step="0.1">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Shift differential (%)</label>
                  <input type="number" id="gc_shift" class="form-control form-control-sm" value="0" step="0.1">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Employer burden (%)</label>
                  <input type="number" id="gc_burden" class="form-control form-control-sm" value="18.15" step="0.1">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Ops support (%)</label>
                  <input type="number" id="gc_ops" class="form-control form-control-sm" value="13.05" step="0.1">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Overhead (%)</label>
                  <input type="number" id="gc_oh" class="form-control form-control-sm" value="17.23" step="0.1">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Profit / fee (%)</label>
                  <input type="number" id="gc_fee" class="form-control form-control-sm" value="6.89" step="0.1">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-medium">Annual hours</label>
                  <input type="number" id="gc_hours" class="form-control form-control-sm" value="21322" step="1">
                </div>
              </div>
            </div>

            <div class="gcc-section">
              <div class="alert alert-light border gasq-border small mb-0">
                This model rolls direct labor, burden, operations support, and overhead into a <span class="fw-semibold">cost hourly</span>, then applies the selected <span class="fw-semibold">profit / fee</span> to produce the bill rate.
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="p-3 p-md-4">
            <div class="alert alert-light border gasq-border small d-print-none mb-3" id="gc_error" style="display:none"></div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <div class="gcc-stat">
                  <div class="gcc-stat-label mb-2">Bill Rate</div>
                  <div class="gcc-stat-value" id="gc_bill">$0.00/hr</div>
                  <div class="small text-gasq-muted">Hourly government contract bill rate</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="gcc-stat">
                  <div class="gcc-stat-label mb-2">Annual Bill Total</div>
                  <div class="gcc-stat-value" id="gc_billAnnual">$0.00</div>
                  <div class="small text-gasq-muted">Bill rate multiplied by annual hours</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="gcc-stat">
                  <div class="gcc-stat-label mb-2">Cost Hourly</div>
                  <div class="gcc-stat-value" id="gc_cost">$0.00</div>
                  <div class="small text-gasq-muted">Direct plus burden, ops, and overhead</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="gcc-stat">
                  <div class="gcc-stat-label mb-2">Annual Cost</div>
                  <div class="gcc-stat-value" id="gc_costAnnual">$0.00</div>
                  <div class="small text-gasq-muted">Cost hourly multiplied by annual hours</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <div class="gcc-kicker mb-1">Results Workspace</div>
                <h3 class="h5 fw-bold mb-0">Live Government Contract Outputs</h3>
              </div>
              <div class="small text-gasq-muted">The bill-rate summary and cost stack below update from the shared inputs on the left.</div>
            </div>

            <div class="row g-3">
              <div class="col-lg-5">
                <div class="gcc-panel gcc-panel-muted p-3 h-100">
                  <div class="small text-gasq-muted mb-1">Decision Snapshot</div>
                  <h4 class="fw-bold mb-3">Current contract pricing basis</h4>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Annual hours</span>
                    <span class="fw-medium gcc-mono" id="gc_hoursOut">0</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-gasq-muted small">Direct hourly</span>
                    <span class="fw-medium gcc-mono" id="gc_direct">$0.00</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-gasq-muted small">Burden hourly</span>
                    <span class="fw-medium gcc-mono" id="gc_bh">$0.00</span>
                  </div>
                </div>
              </div>

              <div class="col-lg-7">
                <div class="gcc-panel p-0 overflow-hidden">
                  <div class="p-3 border-bottom">
                    <h5 class="fw-semibold mb-0">Cost Stack Breakdown</h5>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                      <tbody>
                        <tr><td class="text-gasq-muted">Direct hourly</td><td class="text-end gcc-mono" id="gc_direct_table">$0.00</td></tr>
                        <tr><td class="text-gasq-muted">Burden hourly</td><td class="text-end gcc-mono" id="gc_bh_table">$0.00</td></tr>
                        <tr><td class="text-gasq-muted">Ops hourly</td><td class="text-end gcc-mono" id="gc_oph">$0.00</td></tr>
                        <tr><td class="text-gasq-muted">Overhead hourly</td><td class="text-end gcc-mono" id="gc_ohh">$0.00</td></tr>
                        <tr class="fw-semibold"><td>Cost hourly</td><td class="text-end gcc-mono" id="gc_cost_table">$0.00</td></tr>
                        <tr class="fw-semibold"><td>Annual cost</td><td class="text-end gcc-mono" id="gc_costAnnual_table">$0.00</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <x-report-actions reportType="government-contract-calculator" />
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
  const masterInputs = window.__gasqMasterInputs || {};
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'government-contract-calculator']));
  const DEFAULTS = {
    gc_base: 20.76,
    gc_hw: 4.22,
    gc_loc: 0,
    gc_shift: 0,
    gc_burden: 18.15,
    gc_ops: 13.05,
    gc_oh: 17.23,
    gc_fee: 6.89,
    gc_hours: 21322,
  };
  let t = null;
  let inflight = null;

  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const number0 = (n) => new Intl.NumberFormat('en-US',{maximumFractionDigits:0}).format(n||0);
  const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };
  const setError = (msg) => {
    const el = document.getElementById('gc_error');
    if(!el) return;
    if(!msg){ el.style.display='none'; el.textContent=''; return; }
    el.style.display='';
    el.textContent = msg;
  };

  function payload(){
    return { version:'v24', scenario:{ meta:{
      baseWage: parseFloat(gc_base.value)||0,
      localityPayPct: parseFloat(gc_loc.value)||0,
      shiftDifferentialPct: parseFloat(gc_shift.value)||0,
      healthWelfareCashPerHour: parseFloat(gc_hw.value)||0,
      employerBurdenPct: parseFloat(gc_burden.value)||0,
      opsSupportPct: parseFloat(gc_ops.value)||0,
      overheadPct: parseFloat(gc_oh.value)||0,
      profitPct: parseFloat(gc_fee.value)||0,
      annualHours: parseFloat(gc_hours.value)||0,
    } } };
  }

  function hydrateSavedState(){
    const meta = savedScenario?.meta || {};
    const map = {
      gc_base: meta.baseWage,
      gc_hw: meta.healthWelfareCashPerHour,
      gc_loc: meta.localityPayPct,
      gc_shift: meta.shiftDifferentialPct,
      gc_burden: meta.employerBurdenPct,
      gc_ops: meta.opsSupportPct,
      gc_oh: meta.overheadPct,
      gc_fee: meta.profitPct,
      gc_hours: meta.annualHours,
    };

    Object.entries(map).forEach(([id, value]) => {
      if(value === undefined || value === null) return;
      const el = document.getElementById(id);
      if(el) el.value = value;
    });

    const burdenPct = [
      masterInputs.ficaMedicarePct,
      masterInputs.futaPct,
      masterInputs.sutaPct,
      masterInputs.workersCompPct,
      masterInputs.vacationPct,
      masterInputs.paidHolidaysPct,
      masterInputs.sickLeavePct,
    ].reduce((sum, value) => sum + (Number(value) || 0), 0);

    const opsSupportPct = [
      masterInputs.recruitingHiringPct,
      masterInputs.trainingCertificationPct,
      masterInputs.uniformsEquipmentPct,
      masterInputs.fieldSupervisionPct,
      masterInputs.contractManagementPct,
      masterInputs.qualityAssurancePct,
      masterInputs.vehiclesPatrolPct,
      masterInputs.technologySystemsPct,
      masterInputs.generalLiabilityPct,
      masterInputs.umbrellaInsurancePct,
    ].reduce((sum, value) => sum + (Number(value) || 0), 0);

    const masterMap = {
      gc_base: masterInputs.directLaborWage,
      gc_hw: masterInputs.hwCashPerHour,
      gc_loc: typeof masterInputs.localityPayPct === 'number' ? masterInputs.localityPayPct * 100 : null,
      gc_shift: typeof masterInputs.shiftDifferentialPct === 'number' ? masterInputs.shiftDifferentialPct * 100 : null,
      gc_burden: burdenPct ? burdenPct * 100 : null,
      gc_ops: opsSupportPct ? opsSupportPct * 100 : null,
      gc_oh: typeof masterInputs.corporateOverheadPct === 'number' ? masterInputs.corporateOverheadPct * 100 : null,
      gc_fee: typeof masterInputs.profitFeePct === 'number' ? masterInputs.profitFeePct * 100 : null,
      gc_hours: masterInputs.governmentWorkforceHoursBasis,
    };

    Object.entries(masterMap).forEach(([id, value]) => {
      if(value === undefined || value === null) return;
      const el = document.getElementById(id);
      if(!el) return;
      const hasSavedValue = map[id] !== undefined && map[id] !== null;
      if(!hasSavedValue) {
        el.value = value;
      }
    });
  }

  async function compute(){
    try{
      setError('');
      if (inflight) { inflight.abort(); }
      inflight = new AbortController();
      const res = await fetch(url, {
        method:'POST',
        signal: inflight.signal,
        headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
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
      const b = (data.kpis||{}).breakdown||{};
      set('gc_bill', money(b.billRateHourly) + '/hr');
      set('gc_billAnnual', money(b.annualBillTotal));
      set('gc_direct', money(b.directHourly));
      set('gc_direct_table', money(b.directHourly));
      set('gc_bh', money(b.burdenHourly));
      set('gc_bh_table', money(b.burdenHourly));
      set('gc_oph', money(b.opsHourly));
      set('gc_ohh', money(b.overheadHourly));
      set('gc_cost', money(b.costHourly));
      set('gc_cost_table', money(b.costHourly));
      set('gc_costAnnual', money(b.annualCost));
      set('gc_costAnnual_table', money(b.annualCost));
      set('gc_hoursOut', number0(parseFloat(gc_hours.value) || 0));
    }catch(e){
      if(e?.name === 'AbortError') return;
      console.error(e);
      setError('Unable to calculate right now. Please try again.');
    }
  }

  function schedule(){ clearTimeout(t); t = setTimeout(compute, 200); }

  window.resetGovernmentDefaults = function(){
    Object.entries(DEFAULTS).forEach(([id, value]) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    });
    compute();
  };

  document.addEventListener('DOMContentLoaded', () => {
    hydrateSavedState();
    document.querySelectorAll('input').forEach(el=> el.addEventListener('input', schedule));
    compute();
  });
})();
</script>
@endpush
