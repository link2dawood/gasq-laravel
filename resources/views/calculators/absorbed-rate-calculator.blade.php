@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Absorbed Rate Calculator')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">Absorbed Rate</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Build an all-in absorbed rate from labor + burden + support and profit.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <x-card title="Inputs" subtitle="Updates instantly">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-medium">Base pay ($/hr)</label>
                        <input type="number" id="ar_base" class="form-control" value="18" step="0.01">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Benefits / burden (%)</label>
                        <input type="number" id="ar_benefits" class="form-control" value="20" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Ops support (%)</label>
                        <input type="number" id="ar_ops" class="form-control" value="10" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Overhead (%)</label>
                        <input type="number" id="ar_overhead" class="form-control" value="35" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Profit (%)</label>
                        <input type="number" id="ar_profit" class="form-control" value="15" step="0.1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Annual hours</label>
                        <input type="number" id="ar_hours" class="form-control" value="2080" step="1">
                    </div>
                </div>
                <hr class="my-3">
                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-primary btn-sm" href="{{ url('/main-menu-calculator') }}">Main Menu</a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
                </div>
            </x-card>
        </div>
        <div class="col-lg-7">
            <x-card title="Results" subtitle="Absorbed rate and annual total">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded border">
                            <div class="small text-gasq-muted">Absorbed rate</div>
                            <div class="display-6 fw-bold font-monospace" id="ar_rate">$0.00/hr</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded border">
                            <div class="small text-gasq-muted">Annual absorbed total</div>
                            <div class="display-6 fw-bold font-monospace" id="ar_annual">$0.00</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive mt-4">
                    <table class="table table-striped align-middle mb-0">
                        <tbody>
                            <tr><td class="text-gasq-muted">Benefits amount</td><td class="text-end font-monospace" id="ar_bAmt">$0.00</td></tr>
                            <tr><td class="text-gasq-muted">Burdened cost</td><td class="text-end font-monospace" id="ar_burdened">$0.00</td></tr>
                            <tr><td class="text-gasq-muted">Ops amount</td><td class="text-end font-monospace" id="ar_oAmt">$0.00</td></tr>
                            <tr><td class="text-gasq-muted">Overhead amount</td><td class="text-end font-monospace" id="ar_ohAmt">$0.00</td></tr>
                            <tr class="fw-semibold"><td>Cost with support</td><td class="text-end font-monospace" id="ar_support">$0.00</td></tr>
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
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'absorbed-rate-calculator']));
  let t = null;
  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };

  function payload(){
    return { version:'v24', scenario:{ meta:{
      basePayRate: parseFloat(ar_base.value)||0,
      benefitsPct: parseFloat(ar_benefits.value)||0,
      opsPct: parseFloat(ar_ops.value)||0,
      overheadPct: parseFloat(ar_overhead.value)||0,
      profitPct: parseFloat(ar_profit.value)||0,
      annualHours: parseFloat(ar_hours.value)||0,
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
    set('ar_rate', money(b.absorbedRate) + '/hr');
    set('ar_annual', money(b.annualAbsorbedTotal));
    set('ar_bAmt', money(b.benefitsAmt));
    set('ar_burdened', money(b.burdenedCost));
    set('ar_oAmt', money(b.opsAmt));
    set('ar_ohAmt', money(b.overheadAmt));
    set('ar_support', money(b.costWithSupport));
  }

  function schedule(){ clearTimeout(t); t=setTimeout(compute, 200); }
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input').forEach(el=> el.addEventListener('input', schedule));
    compute();
  });
})();
</script>
@endpush

