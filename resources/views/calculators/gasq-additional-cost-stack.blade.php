@extends('layouts.app')

@section('title', 'GASQ Additional Cost Stack')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .acs-banner { background:#1e3a5f; color:#fff; }
  .acs-input { background:#fff9c4 !important; }
  .acs-mono { font-variant-numeric: tabular-nums; }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
  <div class="container-xl">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('workforce-appraisal-report.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
        <span class="text-gasq-muted small">V24 compute · <code>gasq-additional-cost-stack</code></span>
      </div>
      <button class="btn btn-outline-secondary btn-sm d-print-none" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>

    <div class="card gasq-card">
      <div class="card-header acs-banner py-3">
        <div class="fw-bold">GASQ Additional Cost Stack</div>
        <div class="small opacity-75">Merged view of Vehicle, Uniform &amp; Equipment, and Workforce Maintenance costs</div>
      </div>
      <div class="card-body">
        <div class="row g-3 align-items-end mb-3">
          <div class="col-md-4">
            <label class="form-label small fw-medium">Annual billable hours</label>
            <input type="number" id="acs_hours" class="form-control acs-input" value="21322" min="1" step="1">
          </div>
          <div class="col-md-8 small text-gasq-muted">
            Hourly Cost is editable (yellow). Annual Cost is computed as hourly × annual hours.
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0 acs-mono">
            <thead class="table-light">
              <tr>
                <th style="min-width: 240px;">Module</th>
                <th style="min-width: 220px;">Source Tab</th>
                <th class="text-end" style="width: 170px;">Hourly Cost</th>
                <th class="text-end" style="width: 190px;">Annual Cost</th>
              </tr>
            </thead>
            <tbody id="acs_body"></tbody>
            <tfoot>
              <tr class="table-success fw-semibold">
                <td>Total Additional Cost</td>
                <td></td>
                <td class="text-end" id="acs_total_h">$0.00</td>
                <td class="text-end text-success" id="acs_total_a">$0.00</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="small text-gasq-muted mt-3" id="acs_note"></div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'gasq-additional-cost-stack']));
  let t = null;

  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);

  function collectHourly(){
    const o = {};
    document.querySelectorAll('[data-hourly-key]').forEach(el => {
      const k = el.getAttribute('data-hourly-key');
      const v = parseFloat(el.value);
      if(!Number.isNaN(v)) o[k] = v;
    });
    return o;
  }

  function payload(){
    return {
      version: 'v24',
      scenario: { meta: {
        annualBillableHours: parseFloat(acs_hours.value)||21322,
        hourly: collectHourly(),
        totals: { hourly: 9.95, annual: 212147.34 }
      } }
    };
  }

  function render(k){
    const body = document.getElementById('acs_body');
    body.innerHTML = '';
    for(const r of (k.rows||[])){
      body.innerHTML += `
        <tr>
          <td class="fw-semibold">${r.module}</td>
          <td>${r.sourceTab}</td>
          <td class="text-end">
            <input type="number" class="form-control form-control-sm text-end acs-input" data-hourly-key="${r.key}"
              value="${(r.hourlyRaw ?? r.hourly ?? 0).toFixed(6)}" step="0.000001">
          </td>
          <td class="text-end text-success">${money(r.annual)}</td>
        </tr>`;
    }
    document.getElementById('acs_total_h').textContent = money((k.totals||{}).hourly);
    document.getElementById('acs_total_a').textContent = money((k.totals||{}).annual);
    document.getElementById('acs_note').textContent = k.note || '';

    body.querySelectorAll('input').forEach(el => el.addEventListener('input', schedule));
  }

  async function compute(){
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify(payload())
    });
    const data = await res.json();
    if(!res.ok || !data.ok){ console.error(data); return; }
    render(data.kpis||{});
  }

  function schedule(){ clearTimeout(t); t=setTimeout(compute, 250); }

  document.addEventListener('DOMContentLoaded', () => {
    acs_hours.addEventListener('input', schedule);
    compute();
  });
})();
</script>
@endpush

