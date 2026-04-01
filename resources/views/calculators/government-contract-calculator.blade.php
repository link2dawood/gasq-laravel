@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Government Contract Calculator')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">Government Contract</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Estimate a government contract bill rate from wage controls, burdens, and fee.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/gasq-instant-estimator') }}">Try Instant Estimator</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <x-card title="Inputs" subtitle="V24-style controls">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-medium">Base wage ($/hr)</label>
                        <input type="number" id="gc_base" class="form-control" value="20.76" step="0.01">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">H&amp;W cash ($/hr)</label>
                        <input type="number" id="gc_hw" class="form-control" value="4.22" step="0.01">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Locality pay (%)</label>
                        <input type="number" id="gc_loc" class="form-control" value="0" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Shift differential (%)</label>
                        <input type="number" id="gc_shift" class="form-control" value="0" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Employer burden (%)</label>
                        <input type="number" id="gc_burden" class="form-control" value="18.15" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Ops support (%)</label>
                        <input type="number" id="gc_ops" class="form-control" value="13.05" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Overhead (%)</label>
                        <input type="number" id="gc_oh" class="form-control" value="17.23" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Profit / fee (%)</label>
                        <input type="number" id="gc_fee" class="form-control" value="6.89" step="0.1">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Annual hours</label>
                        <input type="number" id="gc_hours" class="form-control" value="21322" step="1">
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-lg-7">
            <x-card title="Results" subtitle="Hourly and annual totals">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded border">
                            <div class="small text-gasq-muted">Bill rate (hourly)</div>
                            <div class="display-6 fw-bold font-monospace" id="gc_bill">$0.00/hr</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded border">
                            <div class="small text-gasq-muted">Annual bill total</div>
                            <div class="display-6 fw-bold font-monospace" id="gc_billAnnual">$0.00</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive mt-4">
                    <table class="table table-striped align-middle mb-0">
                        <tbody>
                            <tr><td class="text-gasq-muted">Direct hourly</td><td class="text-end font-monospace" id="gc_direct">$0.00</td></tr>
                            <tr><td class="text-gasq-muted">Burden hourly</td><td class="text-end font-monospace" id="gc_bh">$0.00</td></tr>
                            <tr><td class="text-gasq-muted">Ops hourly</td><td class="text-end font-monospace" id="gc_oph">$0.00</td></tr>
                            <tr><td class="text-gasq-muted">Overhead hourly</td><td class="text-end font-monospace" id="gc_ohh">$0.00</td></tr>
                            <tr class="fw-semibold"><td>Cost hourly</td><td class="text-end font-monospace" id="gc_cost">$0.00</td></tr>
                            <tr class="fw-semibold"><td>Annual cost</td><td class="text-end font-monospace" id="gc_costAnnual">$0.00</td></tr>
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
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'government-contract-calculator']));
  let t = null;
  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };

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

  async function compute(){
    const res = await fetch(url, {
      method:'POST',
      headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content') },
      body: JSON.stringify(payload())
    });
    const data = await res.json();
    if(!res.ok || !data.ok){ console.error(data); return; }
    const b = (data.kpis||{}).breakdown||{};
    set('gc_bill', money(b.billRateHourly) + '/hr');
    set('gc_billAnnual', money(b.annualBillTotal));
    set('gc_direct', money(b.directHourly));
    set('gc_bh', money(b.burdenHourly));
    set('gc_oph', money(b.opsHourly));
    set('gc_ohh', money(b.overheadHourly));
    set('gc_cost', money(b.costHourly));
    set('gc_costAnnual', money(b.annualCost));
  }

  function schedule(){ clearTimeout(t); t=setTimeout(compute, 200); }
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input').forEach(el=> el.addEventListener('input', schedule));
    compute();
  });
})();
</script>
@endpush

