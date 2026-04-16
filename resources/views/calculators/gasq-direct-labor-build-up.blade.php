@extends('layouts.app')

@section('title', 'GASQ Direct Labor Build-Up')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .dlb-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 26%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .dlb-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .dlb-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .dlb-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .dlb-stat {
    border: 1px solid rgba(6,45,121,0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .dlb-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .dlb-stat-value {
    font-size: 1.45rem;
    font-weight: 700;
    color: var(--gasq-primary);
  }
  .dlb-chip {
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
  .dlb-banner { background:#1e3a5f; color:#fff; }
  .dlb-section-card {
    border: 1px solid rgba(6,45,121,0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .dlb-section-head {
    padding: 0.9rem 1rem;
    border-bottom: 1px solid rgba(6,45,121,0.08);
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #1e3a5f;
  }
  .dlb-control-row + .dlb-control-row {
    border-top: 1px solid rgba(6,45,121,0.06);
  }
  .dlb-input { background:#fff9c4 !important; }
  .dlb-mono { font-variant-numeric: tabular-nums; }
  .dlb-sub { background:#6b0f1a; color:#fff; }
  @media (max-width: 1199.98px) {
    .dlb-sticky { position: static; }
  }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
  <div class="container-xl">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('workforce-appraisal-report.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
        <span class="text-gasq-muted small">V24 compute · <code>gasq-direct-labor-build-up</code></span>
      </div>
      <button class="btn btn-outline-secondary btn-sm d-print-none" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>

    <div class="card gasq-card dlb-shell overflow-hidden">
      <div class="card-header dlb-banner py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="fw-bold">GASQ Direct Labor Build-Up</div>
          <div class="small opacity-75">Shared calculator workspace with live hourly-to-annual rollup</div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="row g-0">
          <div class="col-xl-4 border-end dlb-sidebar">
            <div class="p-3 p-md-4 dlb-sticky">
              <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                <div>
                  <div class="dlb-kicker mb-2">Shared Inputs</div>
                  <h2 class="h4 fw-bold mb-2">Direct Labor Controls</h2>
                  <p class="small text-gasq-muted mb-0">All editable hourly drivers live here. Changes apply to the full build-up on the right in real time and save through the shared calculator compute flow.</p>
                </div>
                <span class="dlb-chip"><i class="fa fa-bolt"></i> Live</span>
              </div>

              <div class="card gasq-card mb-3">
                <div class="card-header small text-uppercase fw-semibold">Core Driver</div>
                <div class="card-body">
                  <label class="form-label small fw-medium">Annual billable hours</label>
                  <input type="number" id="dlb_hours" class="form-control dlb-input" value="21322" min="1" step="1">
                  <div class="small text-gasq-muted mt-2">Annual amounts on the right are calculated as hourly amount × annual billable hours.</div>
                </div>
              </div>

              <div id="dlb_controls" class="d-grid gap-3">
                <div class="small text-gasq-muted">Loading hourly drivers…</div>
              </div>
            </div>
          </div>

          <div class="col-xl-8">
            <div class="p-3 p-md-4">
              <div class="row g-3 mb-3">
                <div class="col-md-6 col-xl-3">
                  <div class="dlb-stat">
                    <div class="dlb-stat-label mb-2">Annual Billable Hours</div>
                    <div class="dlb-stat-value dlb-mono" id="dlb_stat_hours">0</div>
                    <div class="small text-gasq-muted">Shared annual hours driver</div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3">
                  <div class="dlb-stat">
                    <div class="dlb-stat-label mb-2">Direct Labor</div>
                    <div class="dlb-stat-value dlb-mono" id="dlb_stat_direct">$0.00</div>
                    <div class="small text-gasq-muted">Hourly subtotal for direct labor</div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3">
                  <div class="dlb-stat">
                    <div class="dlb-stat-label mb-2">Labor Plus Fringe</div>
                    <div class="dlb-stat-value dlb-mono" id="dlb_stat_burdened">$0.00</div>
                    <div class="small text-gasq-muted">Combined labor and employer burden</div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3">
                  <div class="dlb-stat">
                    <div class="dlb-stat-label mb-2">Fully Loaded Rate</div>
                    <div class="dlb-stat-value dlb-mono" id="dlb_stat_total">$0.00</div>
                    <div class="small text-gasq-muted">Grand total hourly bill rate</div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                  <div class="dlb-kicker mb-1">Results Workspace</div>
                  <h3 class="h5 fw-bold mb-0">Live Direct Labor Build-Up</h3>
                </div>
                <div class="small text-gasq-muted">The table below updates from the shared input rail on the left.</div>
              </div>

              <div class="card gasq-card">
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 dlb-mono">
                      <thead class="table-light">
                        <tr>
                          <th style="min-width: 340px;">Line Item</th>
                          <th class="text-end" style="width: 160px;">Hourly Amount</th>
                          <th class="text-end" style="width: 190px;">Annual Amount</th>
                        </tr>
                      </thead>
                      <tbody id="dlb_body"></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <x-report-actions reportType="gasq-direct-labor-build-up" />

  </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const savedScenario = window.__gasqCalculatorState?.scenario || null;
  const url = @json(route('backend.standalone.v24.compute', ['type' => 'gasq-direct-labor-build-up']));
  let t = null;

  const money = (n) => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  const num = (n) => (n===null||n===undefined||Number.isNaN(n)) ? '—' : Number(n).toLocaleString('en-US');

  // Map engine keys → spreadsheet-friendly labels
  const LABELS = {
    baseBlended: 'Base Consolidated Blended Direct Labor Wage',
    localityPay: 'Locality Pay',
    laborMarketAdj: 'Labor Market Adjustment',
    hwCash: 'H&W (Cash)',
    shiftDifferential: 'Shift Differential',
    otHolidayPremium: 'OT/Holiday Premium',
    donDoff: 'DON/DOFF',
    ficaMedicare: 'FICA / Medicare',
    futa: 'FUTA',
    suta: 'SUTA',
    workersComp: 'Workers Compensation',
    healthWelfare: 'Health & Welfare',
    vacation: 'Vacation',
    paidHolidays: 'Paid Holidays',
    sickLeave: 'Sick Leave',
    recruiting: 'Recruiting / Hiring',
    training: 'Training / Certification',
    uniformsEquipment: 'Uniforms / Equipment',
    fieldSupervision: 'Field Supervision',
    contractManagement: 'Contract Management',
    qualityAssurance: 'Quality Assurance',
    vehiclesPatrol: 'Vehicles / Patrol',
    technologySystems: 'Technology / Systems',
    generalLiability: 'General Liability Insurance',
    umbrellaInsurance: 'Umbrella / Other Insurance',
    adminHrPayroll: 'Administrative / HR / Payroll',
    accountingLegal: 'Accounting / Legal',
    corporateOverhead: 'Corporate Overhead',
    ga: 'G&A',
    profitFee: 'Profit / Fee',
  };

  function extractSummary(stack){
    const sections = (stack && stack.sections) ? stack.sections : [];
    const direct = sections.find((sec) => sec.key === 'directLabor')?.subtotal?.hourly || 0;
    const laborPlusFringe = sections.find((sec) => sec.key === 'fringe')?.laborPlusFringe?.hourly || 0;
    const total = stack?.grandTotal?.hourly || 0;

    return {
      direct,
      laborPlusFringe,
      total,
    };
  }

  function payload(){
    const annualHoursInput = document.getElementById('dlb_hours');
    return {
      version: 'v24',
      scenario: {
        meta: {
          annualBillableHours: parseFloat(annualHoursInput?.value || '0') || 21322,
          cfoHourlyOverrides: collectOverrides(),
        }
      }
    };
  }

  function collectOverrides(){
    const ov = {};
    document.querySelectorAll('#dlb_controls [data-ov-key]').forEach(el => {
      const k = el.getAttribute('data-ov-key');
      const v = parseFloat(el.value);
      if(!Number.isNaN(v)) ov[k] = v;
    });
    if(Object.keys(ov).length){ return ov; }
    return savedScenario?.meta?.cfoHourlyOverrides || {};
  }

  function hydrateSavedState(){
    const hours = savedScenario?.meta?.annualBillableHours;
    if(hours !== undefined && hours !== null){
      const el = document.getElementById('dlb_hours');
      if(el) el.value = hours;
    }
  }

  function renderControls(stack){
    const root = document.getElementById('dlb_controls');
    if(!root) return;

    const sections = (stack && stack.sections) ? stack.sections : [];
    root.innerHTML = sections.map((sec) => `
      <section class="dlb-section-card">
        <div class="dlb-section-head">${sec.title}</div>
        <div class="p-0">
          ${(sec.rows || []).map((r) => `
            <div class="dlb-control-row p-3">
              <label class="form-label small fw-medium mb-1">${LABELS[r.key] || r.label}</label>
              <input
                type="number"
                class="form-control form-control-sm dlb-input"
                data-ov-key="${r.key}"
                value="${(r.hourly ?? 0).toFixed(2)}"
                min="0"
                step="0.01">
            </div>
          `).join('')}
        </div>
      </section>
    `).join('');

    root.querySelectorAll('input').forEach((el) => el.addEventListener('input', schedule));
  }

  function renderSummary(stack){
    const summary = extractSummary(stack);
    const annualHours = parseFloat(document.getElementById('dlb_hours')?.value || '0') || 0;

    document.getElementById('dlb_stat_hours').textContent = num(annualHours);
    document.getElementById('dlb_stat_direct').textContent = money(summary.direct);
    document.getElementById('dlb_stat_burdened').textContent = money(summary.laborPlusFringe);
    document.getElementById('dlb_stat_total').textContent = money(summary.total);
  }

  function renderTable(stack){
    const body = document.getElementById('dlb_body');
    body.innerHTML = '';
    const sections = (stack && stack.sections) ? stack.sections : [];
    for(const sec of sections){
      body.innerHTML += `<tr><td colspan="3" class="dlb-sub fw-semibold">${sec.title}</td></tr>`;
      for(const r of (sec.rows||[])){
        const label = LABELS[r.key] || r.label;
        body.innerHTML += `
          <tr>
            <td>${label}</td>
            <td class="text-end">${money(r.hourly)}</td>
            <td class="text-end text-success">${money(r.annual)}</td>
          </tr>`;
      }
      const st = sec.subtotal||{};
      body.innerHTML += `<tr class="table-warning fw-semibold"><td>${st.label}</td><td class="text-end">${money(st.hourly)}</td><td class="text-end text-success">${money(st.annual)}</td></tr>`;
      if(sec.laborPlusFringe){
        const l = sec.laborPlusFringe;
        body.innerHTML += `<tr class="table-warning fw-bold"><td>${l.label}</td><td class="text-end">${money(l.hourly)}</td><td class="text-end text-success">${money(l.annual)}</td></tr>`;
      }
    }
    const g = (stack && stack.grandTotal) ? stack.grandTotal : null;
    if(g){
      body.innerHTML += `<tr class="table-primary fw-bold"><td>${g.label}</td><td class="text-end">${money(g.hourly)}</td><td class="text-end text-success">${money(g.annual)}</td></tr>`;
    }
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
    const stack = (data.kpis||{}).stack;
    renderControls(stack);
    renderSummary(stack);
    renderTable(stack);
  }

  function schedule(){ clearTimeout(t); t=setTimeout(compute, 250); }

  document.addEventListener('DOMContentLoaded', () => {
    hydrateSavedState();
    document.getElementById('dlb_hours')?.addEventListener('input', schedule);
    compute();
  });
})();
</script>
@endpush
