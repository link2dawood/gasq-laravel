@extends('layouts.app')
@section('title', 'Mobile Patrol Cost Analysis')
@section('header_variant', 'dashboard')

@section('content')
<div class="py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
        <i class="fa fa-route text-primary"></i> Mobile Patrol Cost Analysis
      </h1>
      <div class="text-gasq-muted small">Fleet cost model aligned with the shipped calculator UI (React bundle <code>bDe</code>).</div>
    </div>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('mobile-patrol-calculator') }}">Open Mobile Patrol Calculator</a>
  </div>

  <div class="card gasq-card">
    <div class="card-header px-3 px-md-4 pt-3 pb-0 d-print-none" style="background:transparent;border-bottom:none">
      <div class="gasq-tabs-scroll">
        <ul class="gasq-tabs-pill mb-0" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#mpa-calculator"><i class="fa fa-calculator me-1"></i> Calculator</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#mpa-dashboard"><i class="fa fa-chart-line me-1"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#mpa-reports"><i class="fa fa-file-lines me-1"></i> Reports</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#mpa-settings"><i class="fa fa-gear me-1"></i> Settings</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#mpa-tools"><i class="fa fa-wrench me-1"></i> Tools</a></li>
        </ul>
      </div>
    </div>
    <div class="card-body p-4">
      <div class="tab-content">
        <div class="tab-pane fade show active" id="mpa-calculator">
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label small fw-medium">Fiscal year</label>
              <input type="text" class="form-control form-control-sm" id="mpa_fiscalYear" value="{{ date('Y') }}">
            </div>
            <div class="col-md-9 d-flex align-items-end gap-2 flex-wrap">
              <button type="button" class="btn btn-primary btn-sm" id="mpa_addVehicle"><i class="fa fa-plus me-1"></i> Add vehicle</button>
              <button type="button" class="btn btn-outline-primary btn-sm" id="mpa_runNow"><i class="fa fa-play me-1"></i> Run analysis</button>
            </div>
          </div>
          <div id="mpa_vehicleEditors" class="d-flex flex-column gap-4"></div>
        </div>

        <div class="tab-pane fade" id="mpa-dashboard">
          <div id="mpa_dashboardContent" class="text-gasq-muted small">Run analysis to populate dashboard metrics.</div>
        </div>

        <div class="tab-pane fade" id="mpa-reports">
          <div class="d-flex gap-2 mb-3 d-print-none">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="mpa_exportCsv"><i class="fa fa-download me-1"></i> Export CSV (KPIs)</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
          </div>
          <div id="mpa_reportsContent" class="text-gasq-muted small">Run analysis to generate tables.</div>
        </div>

        <div class="tab-pane fade" id="mpa-settings">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-medium">Company name</label>
              <input type="text" class="form-control form-control-sm" id="mpa_co_name" value="Your Security Company">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Currency (ISO)</label>
              <input type="text" class="form-control form-control-sm" id="mpa_currency" value="USD" maxlength="3">
            </div>
            <div class="col-12">
              <label class="form-label fw-medium">Notes</label>
              <textarea class="form-control form-control-sm" id="mpa_notes" rows="2"></textarea>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="mpa-tools">
          <p class="text-gasq-muted">Use <strong>Print</strong> from Reports, or reset sample data.</p>
          <button type="button" class="btn btn-outline-danger btn-sm" id="mpa_reset"><i class="fa fa-rotate me-1"></i> Reset vehicles to sample</button>
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

  function readScenario() {
    const vehicles = [];
    document.querySelectorAll('.mpa-vehicle').forEach((wrap, idx) => {
      const id = wrap.dataset.vid;
      const name = wrap.querySelector('.mpa-vh-name').value;
      const active = wrap.querySelector('.mpa-vh-active').checked;
      const monthlyData = [];
      for (let m = 0; m < 12; m++) {
        const row = wrap.querySelector('.mpa-mo-row[data-month="'+m+'"]');
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
      fiscalYear: document.getElementById('mpa_fiscalYear').value || String(new Date().getFullYear()),
      companyInfo: {
        name: document.getElementById('mpa_co_name').value,
      },
      projectSettings: { currency: document.getElementById('mpa_currency').value || 'USD' },
      notes: document.getElementById('mpa_notes').value,
      vehicles,
    };
  }

  function renderVehicleEditor(v, idx) {
    const wrap = document.createElement('div');
    wrap.className = 'mpa-vehicle border rounded p-3 gasq-card';
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
        <table class="table table-sm align-middle">
          <thead class="table-light"><tr>
            <th>Month</th><th>$/mi</th><th>Miles</th><th>Hits</th><th>Reg hrs</th><th>OT hrs</th><th>Equip $</th>
          </tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;
    wrap.querySelector('.mpa-remove-vh').addEventListener('click', () => {
      if (document.querySelectorAll('.mpa-vehicle').length <= 1) { alert('At least one vehicle is required.'); return; }
      wrap.remove(); scheduleCompute();
    });
    wrap.querySelectorAll('input').forEach(el => el.addEventListener('input', scheduleCompute));
    return wrap;
  }

  function renderAllEditors(vehicles) {
    const host = document.getElementById('mpa_vehicleEditors');
    host.innerHTML = '';
    vehicles.forEach((v, i) => host.appendChild(renderVehicleEditor(v, i)));
  }

  let t = null;
  function scheduleCompute() {
    clearTimeout(t);
    t = setTimeout(runCompute, 350);
  }

  async function runCompute() {
    const scenario = readScenario();
    try {
      const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'mobile-patrol-analysis']) }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ version: 'v24', scenario }),
      });
      const data = await res.json();
      if (!res.ok || !data.ok) { console.error(data); return; }
      lastKpis = data.kpis;
      paintDashboard(data.kpis);
      paintReports(data.kpis);
    } catch (e) { console.error(e); }
  }

  function money(x) {
    const cur = document.getElementById('mpa_currency').value || 'USD';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: cur }).format(x);
  }

  function paintDashboard(k) {
    const el = document.getElementById('mpa_dashboardContent');
    let html = `<div class="row g-3 mb-3">
      <div class="col-md-4"><div class="rounded p-3" style="background:var(--gasq-muted-bg)"><div class="small text-gasq-muted">Annual total (incl. labor)</div><div class="fs-4 fw-bold">${money(k.summaryAnnualTotal)}</div></div></div>
      <div class="col-md-4"><div class="rounded p-3" style="background:var(--gasq-muted-bg)"><div class="small text-gasq-muted">Labor rates</div><div class="fw-medium">${k.laborRates.regularHourlyUsd} / ${k.laborRates.overtimeHourlyUsd} $/hr</div></div></div>
    </div>`;
    html += '<h6 class="fw-semibold mb-2">Fleet</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Vehicle</th><th class="text-end">Hits</th><th class="text-end">Miles</th><th class="text-end">Avg $/stop</th><th class="text-end">Miles/hit</th><th class="text-end">Annual</th></tr></thead><tbody>';
    (k.fleet || []).forEach(r => {
      html += `<tr><td>${r.name}</td><td class="text-end">${r.totalHits}</td><td class="text-end">${r.totalMiles}</td><td class="text-end">${money(r.avgCostPerStop)}</td><td class="text-end">${r.efficiency}</td><td class="text-end">${money(r.annualTotal)}</td></tr>`;
    });
    html += '</tbody></table></div>';
    html += '<h6 class="fw-semibold mb-2 mt-3">Operating cost by month (no labor)</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Month</th><th class="text-end">Total</th></tr></thead><tbody>';
    (k.monthlyChartRows || []).forEach(row => {
      html += `<tr><td>${row.month}</td><td class="text-end">${money(row.totalOperating)}</td></tr>`;
    });
    html += '</tbody></table></div>';
    el.innerHTML = html;
  }

  function paintReports(k) {
    const el = document.getElementById('mpa_reportsContent');
    paintDashboard(k);
    el.innerHTML = document.getElementById('mpa_dashboardContent').innerHTML;
  }

  document.getElementById('mpa_exportCsv').addEventListener('click', () => {
    if (!lastKpis) { alert('Run analysis first'); return; }
    const rows = [['Metric','Value'],['Annual total', lastKpis.summaryAnnualTotal]];
    (lastKpis.fleet || []).forEach(f => rows.push(['Fleet: '+f.name+' annual', f.annualTotal]));
    const csv = rows.map(r => r.map(c => '"'+String(c).replace(/"/g,'""')+'"').join(',')).join('\n');
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
  document.getElementById('mpa_reset').addEventListener('click', () => {
    vehicleSeq = 0;
    renderAllEditors([defaultVehicle()]);
    scheduleCompute();
  });

  document.getElementById('mpa_fiscalYear').addEventListener('input', scheduleCompute);
  document.getElementById('mpa_co_name').addEventListener('input', scheduleCompute);
  document.getElementById('mpa_currency').addEventListener('input', scheduleCompute);

  document.addEventListener('DOMContentLoaded', () => {
    vehicleSeq = 0;
    renderAllEditors([defaultVehicle(), defaultVehicle()]);
    runCompute();
  });
})();
</script>
@endpush
