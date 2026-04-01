@extends('layouts.app')

@section('header_variant', 'dashboard')

@section('title', 'Unarmed Security Guard Services')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold mb-3">Unarmed Security Guard Services</h1>
        <p class="lead text-gasq-muted mx-auto" style="max-width: 900px;">
            Estimate weekly/monthly/annual totals for unarmed guard coverage.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            <a class="btn btn-primary btn-lg" href="{{ url('/main-menu-calculator?tab=security') }}">Open Security Cost Calculator</a>
            <a class="btn btn-outline-primary btn-lg" href="{{ url('/open-bid-offer') }}">Open Bid Offer</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <x-card title="Inputs" subtitle="Coverage + rate">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-medium">Guards</label>
                        <input type="number" id="ua_guards" class="form-control" value="1" min="0" step="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium">Hours/week per guard</label>
                        <input type="number" id="ua_hpw" class="form-control" value="40" min="0" step="1">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Bill rate ($/hr)</label>
                        <input type="number" id="ua_rate" class="form-control" value="30.63" min="0" step="0.01">
                    </div>
                </div>
            </x-card>
            <div class="mt-4">
                <x-card title="Totals" subtitle="Computed">
                    <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted">Weekly</span><span class="fw-semibold font-monospace" id="ua_weekly">$0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted">Monthly</span><span class="fw-semibold font-monospace" id="ua_monthly">$0.00</span></div>
                    <div class="d-flex justify-content-between"><span class="text-gasq-muted">Annual</span><span class="fw-semibold font-monospace" id="ua_annual">$0.00</span></div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between"><span class="text-gasq-muted">Weekly hours</span><span class="fw-semibold font-monospace" id="ua_wh">0</span></div>
                </x-card>
            </div>
        </div>

        <div class="col-lg-7">
            <x-card title="Example packages" subtitle="Quick presets">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th class="text-center">Guards</th>
                                <th class="text-end">Hours / Week</th>
                                <th class="text-end">Monthly Total</th>
                            </tr>
                        </thead>
                        <tbody id="ua_pkg_body"></tbody>
                    </table>
                </div>
                <p class="small text-gasq-muted mt-3 mb-0">Click a row to apply guards + hours/week inputs.</p>
            </x-card>
        </div>

        <div class="col-lg-12">
            <x-card title="What’s included" subtitle="Preview bullets">
                <ul class="mb-0 ps-3 text-gasq-muted">
                    <li>Unarmed presence and access control</li>
                    <li>Incident observation and site reporting</li>
                    <li>Front desk &amp; visitor management (as applicable)</li>
                    <li>Patrol routing guidance based on schedule</li>
                </ul>
            </x-card>
            <div class="mt-4">
                <x-card title="Next step" subtitle="Use functional calculators">
                    <p class="mb-0 text-gasq-muted">
                        Switch to `Main Menu` → `Security Cost` to run the functional estimate with your real inputs.
                    </p>
                </x-card>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'unarmed-security-guard-services']));
  let t = null;
  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const set = (id, v) => { const el = document.getElementById(id); if(el) el.textContent = v; };

  const packages = [
    { name:'Lobby Coverage', guards:1, hpw:40 },
    { name:'Warehouse Perimeter', guards:2, hpw:40 },
    { name:'Events & Access Control', guards:3, hpw:40 },
  ];

  function payload(){
    return { version:'v24', scenario:{ meta:{
      guards: parseInt(ua_guards.value||'0',10)||0,
      hoursPerWeekPerGuard: parseFloat(ua_hpw.value)||0,
      billRateHourly: parseFloat(ua_rate.value)||0,
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
    const k = (data.kpis||{}).kpis||{};
    set('ua_weekly', money(k.weeklyTotal));
    set('ua_monthly', money(k.monthlyTotal));
    set('ua_annual', money(k.annualTotal));
    set('ua_wh', (k.weeklyHours||0).toLocaleString('en-US'));

    const body = document.getElementById('ua_pkg_body');
    const rate = parseFloat(ua_rate.value)||0;
    body.innerHTML = packages.map((p,idx)=>{
      const weekly = p.guards*p.hpw*rate;
      const monthly = weekly*4.3333;
      return `<tr role=\"button\" data-idx=\"${idx}\">
        <td class=\"fw-semibold\">${p.name}</td>
        <td class=\"text-center fw-semibold\">${p.guards}</td>
        <td class=\"text-end font-monospace\">${(p.guards*p.hpw).toLocaleString('en-US')}</td>
        <td class=\"text-end font-monospace\">${money(monthly)}</td>
      </tr>`;
    }).join('');
    body.querySelectorAll('tr').forEach(tr=>{
      tr.addEventListener('click', ()=>{
        const p = packages[parseInt(tr.getAttribute('data-idx'),10)];
        ua_guards.value = p.guards;
        ua_hpw.value = p.hpw;
        schedule();
      });
    });
  }

  function schedule(){ clearTimeout(t); t=setTimeout(compute, 200); }
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#ua_guards,#ua_hpw,#ua_rate').forEach(el=> el.addEventListener('input', schedule));
    compute();
  });
})();
</script>
@endpush

