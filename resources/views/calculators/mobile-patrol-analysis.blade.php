@extends('layouts.app')
@section('title', 'Mobile Patrol Cost Analysis')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .mpa-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 30%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .mpa-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .mpa-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .mpa-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .mpa-section + .mpa-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .mpa-panel {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .mpa-panel-muted {
    background: rgba(6, 45, 121, 0.04);
  }
  .mpa-stat {
    border: 1px solid rgba(6, 45, 121, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .mpa-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .mpa-stat-value {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--gasq-primary);
    font-variant-numeric: tabular-nums;
  }
  .mpa-chip {
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
  .mpa-vehicle {
    border-radius: 1rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: #fff;
  }
  .mpa-mono {
    font-variant-numeric: tabular-nums;
  }
  @media (max-width: 1199.98px) {
    .mpa-sticky {
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
      <a href="{{ route('mobile-patrol-calculator') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-route text-primary"></i> Mobile Patrol Cost Analysis
        </h1>
        <div class="text-gasq-muted small">Shared input rail with live fleet cost analysis, dashboard metrics, and reports.</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-outline-secondary btn-sm" id="mpa_resetTop"><i class="fa fa-rotate me-1"></i> Reset</button>
      <a class="btn btn-outline-primary btn-sm" href="{{ route('mobile-patrol-calculator') }}">Open Mobile Patrol Calculator</a>
    </div>
  </div>

  <div class="card gasq-card mpa-shell overflow-hidden">
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-xl-4 border-end mpa-sidebar">
          <div class="p-3 p-md-4 mpa-sticky">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
              <div>
                <div class="mpa-kicker mb-2">Shared Inputs</div>
                <h2 class="h4 fw-bold mb-2">Fleet Analysis Controls</h2>
                <p class="small text-gasq-muted mb-0">Program settings and monthly vehicle data live here. Any change updates the analysis workspace on the right in real time.</p>
              </div>
              <span class="mpa-chip"><i class="fa fa-bolt"></i> Live</span>
            </div>

            <div class="mpa-section">
              <h5 class="fw-semibold d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-sliders text-primary"></i> Program Settings
              </h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Fiscal year</label>
                  <input type="number" class="form-control form-control-sm" id="mpa_fiscalYear" value="{{ date('Y') }}" step="1">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-medium">Currency (ISO)</label>
                  <input type="text" class="form-control form-control-sm text-uppercase" id="mpa_currency" value="USD" maxlength="3">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-medium">Company name</label>
                  <input type="text" class="form-control form-control-sm" id="mpa_co_name" value="Your Security Company">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-medium">Notes</label>
                  <textarea class="form-control form-control-sm" id="mpa_notes" rows="2"></textarea>
                </div>
              </div>
            </div>

            <div class="mpa-section">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="fw-semibold d-flex align-items-center gap-2 mb-0">
                  <i class="fa fa-truck-fast text-primary"></i> Fleet Inputs
                </h5>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-primary btn-sm" id="mpa_addVehicle"><i class="fa fa-plus me-1"></i> Add vehicle</button>
                  <button type="button" class="btn btn-outline-primary btn-sm" id="mpa_runNow"><i class="fa fa-play me-1"></i> Run</button>
                </div>
              </div>
              <div id="mpa_vehicleEditors" class="d-flex flex-column gap-4"></div>
            </div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="p-3 p-md-4">
            <div class="alert alert-light border gasq-border small d-print-none mb-3" id="mpa_error" style="display:none"></div>

            <div class="row g-3 mb-4">
              <div class="col-md-6 col-xl-3">
                <div class="mpa-stat">
                  <div class="mpa-stat-label mb-2">Annual Total</div>
                  <div class="mpa-stat-value" id="mpa_kpiAnnual">$0.00</div>
                  <div class="small text-gasq-muted">Total fleet cost including labor</div>
                </div>
              </div>
              <div class="col-md-6 col-xl-3">
                <div class="mpa-stat">
                  <div class="mpa-stat-label mb-2">Active Vehicles</div>
                  <div class="mpa-stat-value" id="mpa_kpiFleet">0</div>
                  <div class="small text-gasq-muted">Vehicles included in the analysis</div>
                </div>
              </div>
              <div class="col-md-6 col-xl-3">
                <div class="mpa-stat">
                  <div class="mpa-stat-label mb-2">Annual Hits</div>
                  <div class="mpa-stat-value" id="mpa_kpiHits">0</div>
                  <div class="small text-gasq-muted">Total stops across the fleet</div>
                </div>
              </div>
              <div class="col-md-6 col-xl-3">
                <div class="mpa-stat">
                  <div class="mpa-stat-label mb-2">Avg Cost / Stop</div>
                  <div class="mpa-stat-value" id="mpa_kpiStop">$0.00</div>
                  <div class="small text-gasq-muted">Weighted average across active vehicles</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <div class="mpa-kicker mb-1">Results Workspace</div>
                <h3 class="h5 fw-bold mb-0">Live Fleet Analysis Outputs</h3>
              </div>
              <div class="small text-gasq-muted">Dashboard tables, report tables, and export tools below all use the shared fleet inputs on the left.</div>
            </div>

            <div class="mpa-panel mpa-panel-muted p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                  <div class="small text-gasq-muted mb-1">Labor Reference</div>
                  <h4 class="fw-bold mb-1" id="mpa_laborRates">Regular 25.00 / OT 37.50 $/hr</h4>
                  <div class="small text-gasq-muted">Reference rates used by the shipped analysis engine.</div>
                </div>
                <div class="text-end">
                  <div class="small text-gasq-muted">Reference</div>
                  <div class="fw-semibold" id="mpa_reference">react_bundle:bDe</div>
                </div>
              </div>
            </div>

            <div class="gasq-tabs-scroll d-print-none mb-3">
              <ul class="gasq-tabs-pill mb-0" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#mpa-dashboard"><i class="fa fa-chart-line me-1"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#mpa-reports"><i class="fa fa-file-lines me-1"></i> Reports</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#mpa-tools"><i class="fa fa-wrench me-1"></i> Tools</a></li>
              </ul>
            </div>

            <div class="tab-content">
              <div class="tab-pane fade show active" id="mpa-dashboard">
                <div id="mpa_dashboardContent" class="text-gasq-muted small">Run analysis to populate dashboard metrics.</div>
              </div>

              <div class="tab-pane fade" id="mpa-reports">
                <div class="d-flex gap-2 mb-3 d-print-none">
                  <button type="button" class="btn btn-outline-secondary btn-sm" id="mpa_exportCsv"><i class="fa fa-download me-1"></i> Export CSV (KPIs)</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
                </div>
                <div id="mpa_reportsContent" class="text-gasq-muted small">Run analysis to generate report tables.</div>
              </div>

              <div class="tab-pane fade" id="mpa-tools">
                <div class="mpa-panel p-3">
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                      <h5 class="fw-semibold mb-1">Analysis Tools</h5>
                      <div class="small text-gasq-muted">Use exports, print, and reset actions without leaving the live analysis workspace.</div>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="mpa_reset"><i class="fa fa-rotate me-1"></i> Reset vehicles to sample</button>
                  </div>
                  <div class="small text-gasq-muted">Company and currency settings from the left rail are used across the dashboard and reports.</div>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <x-report-actions reportType="mobile-patrol-analysis" />
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
(function(){
  const savedScenario = window.__gasqCalculatorState?.scenario || null;
  const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const COLORS = ['#8884d8','#82ca9d','#ffc658','#ff7300','#00ff7f','#ff1493'];

  let lastKpis = null;
  let vehicleSeq = 0;

  function defaultMonthCell() {
    return { costPerMile: 0.09, milesDriven: 0, hitsPerMonth: 0, regularHours: 160, overtimeHours: 0, equipmentCost: 0 };
  }

  function defaultVehicle() {
    vehicleSeq += 1;
    const id = 'vehicle-' + vehicleSeq;
    const data = Array.from({length: 12}, (_, q) => ({
      costPerMile: q < 2 ? 0.09 : 0,
      milesDriven: q === 0 ? 3600 : (q === 1 ? 2400 : 0),
      hitsPerMonth: q < 2 ? 182 : 0,
      regularHours: q < 2 ? 160 : 0,
      overtimeHours: q === 9 ? 5 : 0,
      equipmentCost: q === 0 ? 745 : (q === 1 ? 500 : 0),
    }));
    return { id, name: 'Vehicle ' + vehicleSeq, color: COLORS[(vehicleSeq - 1) % COLORS.length], active: true, monthlyData: data };
  }

  function currentCurrency() {
    const raw = (document.getElementById('mpa_currency').value || 'USD').trim().toUpperCase();
    return /^[A-Z]{3}$/.test(raw) ? raw : 'USD';
  }

  function money(x) {
    const cur = currentCurrency();
    try {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: cur }).format(x || 0);
    } catch (e) {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(x || 0);
    }
  }

  function readScenario() {
    const vehicles = [];
    document.querySelectorAll('.mpa-vehicle').forEach((wrap, idx) => {
      const id = wrap.dataset.vid;
      const name = wrap.querySelector('.mpa-vh-name').value;
      const active = wrap.querySelector('.mpa-vh-active').checked;
      const monthlyData = [];
      for (let m = 0; m < 12; m++) {
        const row = wrap.querySelector('.mpa-mo-row[data-month="' + m + '"]');
        monthlyData.push({
          costPerMile: parseFloat(row.querySelector('[data-f=costPerMile]').value) || 0,
          milesDriven: parseFloat(row.querySelector('[data-f=milesDriven]').value) || 0,
          hitsPerMonth: parseFloat(row.querySelector('[data-f=hitsPerMonth]').value) || 0,
          regularHours: parseFloat(row.querySelector('[data-f=regularHours]').value) || 0,
          overtimeHours: parseFloat(row.querySelector('[data-f=overtimeHours]').value) || 0,
          equipmentCost: parseFloat(row.querySelector('[data-f=equipmentCost]').value) || 0,
        });
      }
      vehicles.push({ id, name, color: COLORS[idx % COLORS.length], active, monthlyData });
    });
    return {
      fiscalYear: parseInt(document.getElementById('mpa_fiscalYear').value, 10) || new Date().getFullYear(),
      companyInfo: {
        name: document.getElementById('mpa_co_name').value,
      },
      projectSettings: { currency: currentCurrency() },
      notes: document.getElementById('mpa_notes').value,
      vehicles,
    };
  }

  function renderVehicleEditor(v, idx) {
    const wrap = document.createElement('div');
    wrap.className = 'mpa-vehicle p-3';
    wrap.dataset.vid = v.id;
    let rows = '';
    for (let m = 0; m < 12; m++) {
      const d = v.monthlyData[m] || defaultMonthCell();
      rows += `<tr class="mpa-mo-row" data-month="${m}">
        <td class="small text-gasq-muted">${MONTHS[m]}</td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" data-f="costPerMile" value="${d.costPerMile}"></td>
        <td><input type="number" step="1" class="form-control form-control-sm" data-f="milesDriven" value="${d.milesDriven}"></td>
        <td><input type="number" step="1" class="form-control form-control-sm" data-f="hitsPerMonth" value="${d.hitsPerMonth}"></td>
        <td><input type="number" step="0.5" class="form-control form-control-sm" data-f="regularHours" value="${d.regularHours}"></td>
        <td><input type="number" step="0.5" class="form-control form-control-sm" data-f="overtimeHours" value="${d.overtimeHours}"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" data-f="equipmentCost" value="${d.equipmentCost}"></td>
      </tr>`;
    }
    wrap.innerHTML = `
      <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:${v.color}"></span>
        <input type="text" class="form-control form-control-sm mpa-vh-name" style="max-width:220px" value="${v.name.replace(/"/g,'&quot;')}">
        <div class="form-check form-switch">
          <input class="form-check-input mpa-vh-active" type="checkbox" ${v.active ? 'checked' : ''}>
          <label class="form-check-label small">Active</label>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm ms-auto mpa-remove-vh" title="Remove"><i class="fa fa-trash"></i></button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Month</th><th>$/mi</th><th>Miles</th><th>Hits</th><th>Reg hrs</th><th>OT hrs</th><th>Equip $</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;
    wrap.querySelector('.mpa-remove-vh').addEventListener('click', () => {
      if (document.querySelectorAll('.mpa-vehicle').length <= 1) { alert('At least one vehicle is required.'); return; }
      wrap.remove();
      scheduleCompute();
    });
    wrap.querySelectorAll('input').forEach(el => el.addEventListener('input', scheduleCompute));
    return wrap;
  }

  function renderAllEditors(vehicles) {
    const host = document.getElementById('mpa_vehicleEditors');
    host.innerHTML = '';
    vehicles.forEach((v, i) => host.appendChild(renderVehicleEditor(v, i)));
  }

  function hydrateSavedState() {
    const scenario = savedScenario || {};
    if (scenario.fiscalYear !== undefined) {
      const fiscalYear = document.getElementById('mpa_fiscalYear');
      if (fiscalYear) fiscalYear.value = scenario.fiscalYear;
    }

    const companyName = scenario.companyInfo?.name;
    if (companyName !== undefined) {
      const companyEl = document.getElementById('mpa_co_name');
      if (companyEl) companyEl.value = companyName;
    }

    const currency = scenario.projectSettings?.currency;
    if (currency !== undefined) {
      const currencyEl = document.getElementById('mpa_currency');
      if (currencyEl) currencyEl.value = currency;
    }

    if (scenario.notes !== undefined) {
      const notesEl = document.getElementById('mpa_notes');
      if (notesEl) notesEl.value = scenario.notes;
    }

    const vehicles = Array.isArray(scenario.vehicles) ? scenario.vehicles : null;
    if (!vehicles || !vehicles.length) {
      return [defaultVehicle(), defaultVehicle()];
    }

    vehicleSeq = vehicles.length;

    return vehicles.map((vehicle, idx) => ({
      id: vehicle.id || ('vehicle-' + (idx + 1)),
      name: vehicle.name || ('Vehicle ' + (idx + 1)),
      color: COLORS[idx % COLORS.length],
      active: vehicle.active !== false,
      monthlyData: Array.from({ length: 12 }, (_, monthIndex) => ({
        ...defaultMonthCell(),
        ...(vehicle.monthlyData?.[monthIndex] || {}),
      })),
    }));
  }

  function updateSummaryCards(k) {
    const fleet = k.fleet || [];
    const totalHits = fleet.reduce((sum, row) => sum + (row.totalHits || 0), 0);
    const weightedCostNumerator = fleet.reduce((sum, row) => sum + ((row.avgCostPerStop || 0) * (row.totalHits || 0)), 0);
    const avgCostStop = totalHits > 0 ? weightedCostNumerator / totalHits : 0;
    const laborRates = k.laborRates || {};

    document.getElementById('mpa_kpiAnnual').textContent = money(k.summaryAnnualTotal || 0);
    document.getElementById('mpa_kpiFleet').textContent = fleet.length.toLocaleString('en-US');
    document.getElementById('mpa_kpiHits').textContent = totalHits.toLocaleString('en-US');
    document.getElementById('mpa_kpiStop').textContent = money(avgCostStop);
    document.getElementById('mpa_laborRates').textContent =
      'Regular ' + (laborRates.regularHourlyUsd || 0).toFixed(2) +
      ' / OT ' + (laborRates.overtimeHourlyUsd || 0).toFixed(2) + ' $/hr';
    document.getElementById('mpa_reference').textContent = k.reference || 'react_bundle:bDe';
  }

  let t = null;
  function scheduleCompute() {
    clearTimeout(t);
    t = setTimeout(runCompute, 350);
  }

  async function runCompute() {
    const scenario = readScenario();
    try {
      const err = document.getElementById('mpa_error');
      if (err) {
        err.style.display = 'none';
        err.textContent = '';
      }
      const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'mobile-patrol-analysis']) }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ version: 'v24', scenario }),
      });
      let data = null;
      try { data = await res.json(); } catch { data = null; }
      if (!res.ok || !data || !data.ok) {
        if (err) {
          err.style.display = '';
          err.textContent = (data && data.error === 'insufficient_credits')
            ? (data.message || 'Not enough credits to run this calculator.')
            : 'Unable to calculate right now. Please try again.';
        }
        console.error(data);
        return;
      }
      lastKpis = data.kpis;
      updateSummaryCards(data.kpis);
      paintDashboard(data.kpis);
      paintReports(data.kpis);
    } catch (e) {
      console.error(e);
    }
  }

  function paintDashboard(k) {
    const el = document.getElementById('mpa_dashboardContent');
    let html = `<div class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="rounded p-3 h-100" style="background:var(--gasq-muted-bg)">
          <div class="small text-gasq-muted">Annual total (incl. labor)</div>
          <div class="fs-4 fw-bold mpa-mono">${money(k.summaryAnnualTotal || 0)}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="rounded p-3 h-100" style="background:var(--gasq-muted-bg)">
          <div class="small text-gasq-muted">Labor rates</div>
          <div class="fw-medium mpa-mono">${(k.laborRates.regularHourlyUsd || 0).toFixed(2)} / ${(k.laborRates.overtimeHourlyUsd || 0).toFixed(2)} $/hr</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="rounded p-3 h-100" style="background:var(--gasq-muted-bg)">
          <div class="small text-gasq-muted">Reference</div>
          <div class="fw-medium">${k.reference || 'react_bundle:bDe'}</div>
        </div>
      </div>
    </div>`;
    html += '<h6 class="fw-semibold mb-2">Fleet</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Vehicle</th><th class="text-end">Hits</th><th class="text-end">Miles</th><th class="text-end">Avg $/stop</th><th class="text-end">Miles/hit</th><th class="text-end">Annual</th></tr></thead><tbody>';
    (k.fleet || []).forEach(r => {
      html += `<tr><td>${r.name}</td><td class="text-end mpa-mono">${r.totalHits}</td><td class="text-end mpa-mono">${r.totalMiles}</td><td class="text-end mpa-mono">${money(r.avgCostPerStop)}</td><td class="text-end mpa-mono">${r.efficiency}</td><td class="text-end mpa-mono">${money(r.annualTotal)}</td></tr>`;
    });
    html += '</tbody></table></div>';
    html += '<h6 class="fw-semibold mb-2 mt-3">Operating cost by month (no labor)</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Month</th><th class="text-end">Total</th></tr></thead><tbody>';
    (k.monthlyChartRows || []).forEach(row => {
      html += `<tr><td>${row.month}</td><td class="text-end mpa-mono">${money(row.totalOperating)}</td></tr>`;
    });
    html += '</tbody></table></div>';
    el.innerHTML = html;
  }

  function paintReports(k) {
    const el = document.getElementById('mpa_reportsContent');
    let html = '<div class="mpa-panel p-3 mb-3"><div class="small text-gasq-muted mb-1">Report Snapshot</div>';
    html += `<div class="fw-semibold">${document.getElementById('mpa_co_name').value || 'Your Security Company'}</div>`;
    html += `<div class="small text-gasq-muted">Fiscal year ${document.getElementById('mpa_fiscalYear').value || new Date().getFullYear()} • Currency ${currentCurrency()}</div></div>`;
    html += document.getElementById('mpa_dashboardContent').innerHTML;
    el.innerHTML = html;
  }

  document.getElementById('mpa_exportCsv').addEventListener('click', () => {
    if (!lastKpis) { alert('Run analysis first'); return; }
    const rows = [['Metric','Value'],['Annual total', lastKpis.summaryAnnualTotal]];
    (lastKpis.fleet || []).forEach(f => rows.push(['Fleet: ' + f.name + ' annual', f.annualTotal]));
    const csv = rows.map(r => r.map(c => '"' + String(c).replace(/"/g,'""') + '"').join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'mobile-patrol-kpis.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  });

  document.getElementById('mpa_addVehicle').addEventListener('click', () => {
    const host = document.getElementById('mpa_vehicleEditors');
    host.appendChild(renderVehicleEditor(defaultVehicle(), host.children.length));
    scheduleCompute();
  });

  document.getElementById('mpa_runNow').addEventListener('click', runCompute);

  function resetVehicles() {
    vehicleSeq = 0;
    renderAllEditors([defaultVehicle(), defaultVehicle()]);
    scheduleCompute();
  }

  document.getElementById('mpa_reset').addEventListener('click', resetVehicles);
  document.getElementById('mpa_resetTop').addEventListener('click', resetVehicles);

  document.getElementById('mpa_fiscalYear').addEventListener('input', scheduleCompute);
  document.getElementById('mpa_co_name').addEventListener('input', scheduleCompute);
  document.getElementById('mpa_currency').addEventListener('input', scheduleCompute);
  document.getElementById('mpa_notes').addEventListener('input', scheduleCompute);

  document.addEventListener('DOMContentLoaded', () => {
    vehicleSeq = 0;
    renderAllEditors(hydrateSavedState());
    runCompute();
  });
})();
</script>
@endpush
