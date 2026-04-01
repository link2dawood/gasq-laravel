@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Keeps Doors Open Calculator')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">Keeps Doors Open</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Translate access-control staffing into weekly, annual, and per-day costs.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary" href="{{ url('/post-coverage-schedule') }}">Post Coverage Schedule</a>
            <a class="btn btn-outline-primary" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <x-card title="Inputs" subtitle="Staffing + rate">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-medium">Guards</label>
                        <input type="number" id="kdo_guards" class="form-control" value="2" min="0" step="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Bill rate ($/hr)</label>
                        <input type="number" id="kdo_rate" class="form-control" value="30.90" min="0" step="0.01">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-medium">Hours/day</label>
                        <input type="number" id="kdo_hpd" class="form-control" value="8" min="0" step="0.25">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-medium">Days/week</label>
                        <input type="number" id="kdo_dpw" class="form-control" value="5" min="0" step="1">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-medium">Weeks/year</label>
                        <input type="number" id="kdo_wpy" class="form-control" value="52" min="1" step="1">
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-lg-7">
            <x-card title="Results" subtitle="Weekly + annual + per day">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded border">
                            <div class="small text-gasq-muted">Weekly hours</div>
                            <div class="h2 fw-bold font-monospace mb-0" id="kdo_wh">0</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded border">
                            <div class="small text-gasq-muted">Annual cost</div>
                            <div class="h2 fw-bold font-monospace mb-0" id="kdo_annual">$0.00</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded border">
                            <div class="small text-gasq-muted">Cost per day</div>
                            <div class="h2 fw-bold font-monospace mb-0" id="kdo_cpd">$0.00</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive mt-4">
                    <table class="table table-striped align-middle mb-0">
                        <tbody>
                            <tr><td class="text-gasq-muted">Annual hours</td><td class="text-end font-monospace" id="kdo_ah">0</td></tr>
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'keeps-doors-open-calculator']));
  let t = null;
  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };

  function payload(){
    return { version:'v24', scenario:{ meta:{
      guards: parseInt(kdo_guards.value||'0',10)||0,
      billRateHourly: parseFloat(kdo_rate.value)||0,
      hoursPerDay: parseFloat(kdo_hpd.value)||0,
      daysPerWeek: parseFloat(kdo_dpw.value)||0,
      weeksPerYear: parseFloat(kdo_wpy.value)||52,
    } } };
  }

  async function compute(){
    const res = await fetch(url, {
      method:'POST',
      headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content') },
      body: JSON.stringify(payload())
    });
    const data = await res.json();
    if(!res.ok || !data.ok){ console.error(data); return; }
    const k = (data.kpis||{}).kpis || {};
    set('kdo_wh', (k.weeklyHours||0).toLocaleString('en-US'));
    set('kdo_annual', money(k.annualCost));
    set('kdo_cpd', money(k.costPerDay));
    set('kdo_ah', (k.annualHours||0).toLocaleString('en-US'));
  }

  function schedule(){ clearTimeout(t); t=setTimeout(compute, 200); }
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input').forEach(el=> el.addEventListener('input', schedule));
    compute();
  });
})();
</script>
@endpush

