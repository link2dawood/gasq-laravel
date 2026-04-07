@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'GASQ TCO Calculator')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">Total Cost of Ownership, Made Clear</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Compare an internal should-cost bill rate vs vendor TCO benchmark.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ route('jobs.create') }}">Post Your Job</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <x-card title="Inputs" subtitle="Instant compute">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-medium">Annual billable hours</label>
                        <input type="number" id="tco_hours" class="form-control" value="21322" step="1" min="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Vendor TCO ($/hr)</label>
                        <input type="number" id="tco_vendor" class="form-control" value="54.78" step="0.01" min="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">GASQ bill rate ($/hr)</label>
                        <input type="number" id="tco_gasq" class="form-control" value="43.67" step="0.01" min="0">
                        <div class="form-text">Defaults align with the CFO bill rate excerpt.</div>
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-lg-7">
            <div class="row g-4">
                <div class="col-md-6">
                    <x-card title="GASQ should-cost" subtitle="Annual total">
                        <div class="display-6 fw-bold font-monospace mb-1" id="tco_gasqAnnual">$0.00</div>
                        <div class="text-gasq-muted small">Hourly <span class="fw-semibold" id="tco_gasqHr">$0.00</span></div>
                    </x-card>
                </div>
                <div class="col-md-6">
                    <x-card title="Vendor TCO" subtitle="Annual total">
                        <div class="display-6 fw-bold font-monospace mb-1" id="tco_vendorAnnual">$0.00</div>
                        <div class="text-gasq-muted small">Hourly <span class="fw-semibold" id="tco_vendorHr">$0.00</span></div>
                    </x-card>
                </div>
                <div class="col-12">
                    <x-card title="Premium / (discount)" subtitle="Vendor vs GASQ">
                        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                            <div>
                                <div class="small text-gasq-muted">Hourly</div>
                                <div class="h3 fw-bold font-monospace mb-0" id="tco_premHr">$0.00</div>
                            </div>
                            <div class="text-end">
                                <div class="small text-gasq-muted">Annual</div>
                                <div class="h3 fw-bold font-monospace mb-0" id="tco_premAnnual">$0.00</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>
        </div>
    </div>

    <x-report-actions reportType="gasq-tco-calculator" />
</div>
@endsection

@push('scripts')
<script>
(() => {
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'gasq-tco-calculator']));
  let t = null;
  let inflight = null;
  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };
  const setError = (msg) => {
    let el = document.getElementById('tco_error');
    if(!el){
      el = document.createElement('div');
      el.id = 'tco_error';
      el.className = 'alert alert-light border gasq-border small mt-3';
      tco_hours.closest('.card')?.querySelector('.card-body')?.appendChild(el);
    }
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
    set('tco_gasqHr', money(s.gasqBillRateHourly) + '/hr');
    set('tco_vendorHr', money(s.vendorTcoHourly) + '/hr');
    set('tco_premHr', money(s.vendorPremiumHourly));
    set('tco_premAnnual', money(s.vendorPremiumAnnual));
    }catch(e){
      if(e?.name === 'AbortError') return;
      console.error(e);
      setError('Unable to calculate right now. Please try again.');
    }
  }

  function schedule(){ clearTimeout(t); t=setTimeout(compute, 200); }
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input').forEach(el=> el.addEventListener('input', schedule));
    compute();
  });
})();
</script>
@endpush

