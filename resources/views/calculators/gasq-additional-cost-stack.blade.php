@extends('layouts.app')

@section('title', 'GASQ Additional Cost Stack')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .acs-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 26%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .acs-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .acs-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .acs-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .acs-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(6,45,121,0.08);
    color: var(--gasq-primary);
    font-size: 0.78rem;
    font-weight: 600;
  }
  .acs-control-card {
    border: 1px solid rgba(6,45,121,0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .acs-input { background:#fff9c4 !important; }
  .acs-mono { font-variant-numeric: tabular-nums; }
  .acs-summary-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .acs-benchmark-card {
    border: 1px solid rgba(6, 45, 121, 0.12);
    border-radius: 1rem;
    padding: 1rem 1.05rem;
    background:
      radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 35%),
      linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  }
  @media (max-width: 1199.98px) {
    .acs-sticky { position: static; }
  }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
  <div class="container-xl">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="{{ route('workforce-appraisal-report.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
        <div>
          <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="fa fa-list-ul text-primary"></i> GASQ Additional Cost Stack
          </h1>
          <div class="text-gasq-muted small">Manage the merged annual additional-cost stack with the same structured summary and workspace rhythm used in the Budget calculator.</div>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2 d-print-none">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      </div>
    </div>

    <div class="card gasq-card acs-shell overflow-hidden">
      <div class="card-body p-0">
        <div class="row g-0">
          <div class="col-xl-4 border-end acs-sidebar">
            <div class="p-3 p-md-4 acs-sticky">
              <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                <div>
                  <div class="acs-kicker mb-2">Shared Inputs</div>
                  <h2 class="h4 fw-bold mb-2">Additional Cost Controls</h2>
                  <p class="small text-gasq-muted mb-0">Set annual hours and module hourly costs here. The consolidated stack on the right updates instantly from these shared inputs.</p>
                </div>
                <span class="acs-chip"><i class="fa fa-bolt"></i> Live</span>
              </div>

              <div class="card gasq-card mb-3">
                <div class="card-header small text-uppercase fw-semibold">Core Driver</div>
                <div class="card-body">
                  <label class="form-label small fw-medium">Annual billable hours</label>
                  <input type="number" id="acs_hours" class="form-control acs-input" value="21322" min="1" step="1">
                  <div class="small text-gasq-muted mt-2">Annual cost values on the right are calculated from the shared hourly stack and annual billable hours.</div>
                </div>
              </div>

              <div id="acs_controls" class="d-grid gap-3">
                <div class="small text-gasq-muted">Loading module drivers…</div>
              </div>
            </div>
          </div>

          <div class="col-xl-8">
            <div class="p-3 p-md-4">
              <div class="acs-summary-card p-3 p-md-4 mb-4">
                <div class="acs-benchmark-card mb-4">
                  <div class="text-uppercase small fw-semibold text-gasq-muted mb-1">Additional Cost Workspace</div>
                  <div class="h3 fw-bold text-primary mb-1" id="acs_stat_total_a">$0.00</div>
                  <div class="small text-gasq-muted mb-2">Merged annual additional-cost stack across vehicles, uniforms, equipment, and workforce maintenance.</div>
                  <div class="d-flex justify-content-between align-items-center small gap-3">
                    <span class="text-gasq-muted">Annual billable hours</span>
                    <span class="fw-semibold" id="acs_stat_hours">0</span>
                  </div>
                  <div class="mt-2">
                    <a href="{{ route('workforce-appraisal-report.index') }}" class="small fw-semibold text-decoration-none">Open Workforce Appraisal Report</a>
                  </div>
                </div>

                <div class="row g-3 mb-0">
                  <div class="col-6 col-xl-4">
                    <div class="gasq-metric-card text-center">
                      <div class="metric-desc">Modules Included</div>
                      <div class="metric-value text-primary acs-mono" id="acs_stat_modules">0</div>
                    </div>
                  </div>
                  <div class="col-6 col-xl-4">
                    <div class="gasq-metric-card text-center">
                      <div class="metric-desc">Total Hourly Cost</div>
                      <div class="metric-value acs-mono" id="acs_stat_total_h">$0.00</div>
                    </div>
                  </div>
                  <div class="col-12 col-xl-4">
                    <div class="gasq-metric-card text-center">
                      <div class="metric-desc">Total Annual Cost</div>
                      <div class="metric-value text-primary acs-mono" id="acs_stat_total_a_card">$0.00</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                  <div class="acs-kicker mb-1">Results Workspace</div>
                  <h3 class="h5 fw-bold mb-0">Live Additional Cost Stack</h3>
                </div>
                <div class="small text-gasq-muted">The cost stack below updates from the shared input rail on the left.</div>
              </div>

              <div class="card gasq-card">
                <div class="card-body p-0">
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
                </div>
              </div>

              <div class="small text-gasq-muted mt-3" id="acs_note"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <x-report-actions reportType="gasq-additional-cost-stack" />
  </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const savedScenario = window.__gasqCalculatorState?.scenario || null;
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'gasq-additional-cost-stack']));
  let t = null;

  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const num = (n) => (n===null||n===undefined||Number.isNaN(n)) ? '—' : Number(n).toLocaleString('en-US');

  function collectHourly(){
    const o = {};
    document.querySelectorAll('#acs_controls [data-hourly-key]').forEach(el => {
      const k = el.getAttribute('data-hourly-key');
      const v = parseFloat(el.value);
      if(!Number.isNaN(v)) o[k] = v;
    });
    if(Object.keys(o).length){ return o; }
    return savedScenario?.meta?.hourly || {};
  }

  function payload(){
    const annualHoursInput = document.getElementById('acs_hours');
    return {
      version: 'v24',
      scenario: { meta: {
        annualBillableHours: parseFloat(annualHoursInput?.value || '0') || 21322,
        hourly: collectHourly(),
        totals: { hourly: 9.95, annual: 212147.34 }
      } }
    };
  }

  function renderControls(k){
    const root = document.getElementById('acs_controls');
    if(!root) return;

    root.innerHTML = (k.rows || []).map((r) => `
      <section class="acs-control-card p-3">
        <div class="small text-uppercase text-gasq-muted fw-semibold mb-1">${r.sourceTab}</div>
        <label class="form-label small fw-medium mb-1">${r.module}</label>
        <input
          type="number"
          class="form-control form-control-sm acs-input"
          data-hourly-key="${r.key}"
          value="${(r.hourlyRaw ?? r.hourly ?? 0).toFixed(6)}"
          min="0"
          step="0.000001">
      </section>
    `).join('');

    root.querySelectorAll('input').forEach(el => el.addEventListener('input', schedule));
  }

  function renderSummary(k){
    const annualHours = parseFloat(document.getElementById('acs_hours')?.value || '0') || 0;
    document.getElementById('acs_stat_hours').textContent = num(annualHours);
    document.getElementById('acs_stat_modules').textContent = num((k.rows || []).length);
    document.getElementById('acs_stat_total_h').textContent = money((k.totals || {}).hourly);
    document.getElementById('acs_stat_total_a').textContent = money((k.totals || {}).annual);
    document.getElementById('acs_stat_total_a_card').textContent = money((k.totals || {}).annual);
  }

  function hydrateSavedState(){
    const hours = savedScenario?.meta?.annualBillableHours;
    if(hours !== undefined && hours !== null){
      const el = document.getElementById('acs_hours');
      if(el) el.value = hours;
    }
  }

  function renderTable(k){
    const body = document.getElementById('acs_body');
    body.innerHTML = '';
    for(const r of (k.rows||[])){
      body.innerHTML += `
        <tr>
          <td class="fw-semibold">${r.module}</td>
          <td>${r.sourceTab}</td>
          <td class="text-end">${money(r.hourly)}</td>
          <td class="text-end text-success">${money(r.annual)}</td>
        </tr>`;
    }
    document.getElementById('acs_total_h').textContent = money((k.totals||{}).hourly);
    document.getElementById('acs_total_a').textContent = money((k.totals||{}).annual);
    document.getElementById('acs_note').textContent = k.note || '';
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
    const k = data.kpis||{};
    renderControls(k);
    renderSummary(k);
    renderTable(k);
  }

  function schedule(){ clearTimeout(t); t=setTimeout(compute, 250); }

  document.addEventListener('DOMContentLoaded', () => {
    hydrateSavedState();
    document.getElementById('acs_hours')?.addEventListener('input', schedule);
    compute();
  });
})();
</script>
@endpush
