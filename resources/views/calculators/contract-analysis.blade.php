@extends('layouts.app')
@section('title', 'Contract Analysis')
@section('header_variant', 'dashboard')

@section('content')
<div class="py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-file-contract text-primary"></i> Contract Analysis
        </h1>
        <div class="text-gasq-muted small">Analyze security contract categories, pay rates, and margins</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="addRow()"><i class="fa fa-plus me-1"></i> Add Row</button>
    </div>
  </div>

  {{-- Tabs + body: Bootstrap 5 expects .tab-pane as direct children of .tab-content --}}
  <div class="card gasq-card">
    <div class="card-header px-3 px-md-4 pt-3 pb-0 d-print-none" style="background:transparent;border-bottom:none">
      <div class="gasq-tabs-scroll">
        <ul class="gasq-tabs-pill mb-0" role="tablist" id="caTabs">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#ca-inputs"><i class="fa fa-table me-1"></i> Category Inputs</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ca-analysis"><i class="fa fa-chart-bar me-1"></i> Per-Hour Analysis</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ca-summary"><i class="fa fa-file-alt me-1"></i> Summary</a></li>
        </ul>
      </div>
    </div>

    <div class="card-body p-4">
      <div class="tab-content">

      {{-- ===== CATEGORY INPUTS ===== --}}
      <div class="tab-pane fade show active" id="ca-inputs">
        <p class="text-gasq-muted small mb-3">Enter post/category details. Add rows for each guard position or shift type.</p>
        <div class="table-responsive mb-3">
          <table class="table table-sm align-middle" id="ca-table">
            <thead class="table-light">
              <tr>
                <th>Post / Category</th>
                <th class="text-center">Armed</th>
                <th>Weekly Hours</th>
                <th>Pay Rate $/hr</th>
                <th>Bill Rate $/hr</th>
                <th>OT Hours/wk</th>
                <th class="text-end">Weekly Revenue</th>
                <th class="text-end">Weekly Pay Cost</th>
                <th class="d-print-none"></th>
              </tr>
            </thead>
            <tbody id="ca-tbody"></tbody>
            <tfoot class="table-light fw-semibold">
              <tr>
                <td colspan="2">Totals</td>
                <td id="ft-hours">0.0</td>
                <td>—</td>
                <td>—</td>
                <td id="ft-otHours">0.0</td>
                <td class="text-end font-monospace" id="ft-revenue">$0.00</td>
                <td class="text-end font-monospace" id="ft-payCost">$0.00</td>
                <td class="d-print-none"></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm" onclick="addRow()"><i class="fa fa-plus me-1"></i> Add Row</button>
          <button class="btn btn-primary btn-sm" onclick="runAnalysis()"><i class="fa fa-play me-1"></i> Run Analysis</button>
        </div>
      </div>

      {{-- ===== PER-HOUR ANALYSIS ===== --}}
      <div class="tab-pane fade" id="ca-analysis">
        <div class="row g-4">
          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3">Per-Hour Metrics</h5>
            <div class="rounded p-3 mb-3" style="background:var(--gasq-muted-bg)">
              <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Avg Bill Rate (weighted)</span><span class="fw-medium" id="ph_avgBillRate">$0.00/hr</span></div>
              <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Avg Pay Rate (weighted)</span><span class="fw-medium" id="ph_avgPayRate">$0.00/hr</span></div>
              <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Gross Margin per Hour</span><span class="fw-medium" id="ph_gphr">$0.00/hr</span></div>
              <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Direct Labor Ratio</span><span class="fw-medium" id="ph_dlr">0.0%</span></div>
            </div>

            <h5 class="fw-semibold mb-3">Annual Projections</h5>
            <div class="rounded p-3" style="background:var(--gasq-muted-bg)">
              <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Annual Hours</span><span class="fw-medium" id="ph_annualHrs">0</span></div>
              <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Annual Revenue</span><span class="fw-medium" id="ph_annualRev">$0.00</span></div>
              <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Annual Pay Cost</span><span class="fw-medium" id="ph_annualPay">$0.00</span></div>
              <div class="d-flex justify-content-between"><span class="text-gasq-muted small fw-semibold">Annual Gross Margin</span><span class="fw-semibold text-primary" id="ph_annualGM">$0.00</span></div>
            </div>
          </div>
          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3">Category Breakdown</h5>
            <div id="ph_breakdown" class="d-flex flex-column gap-2"></div>
          </div>
        </div>
      </div>

      {{-- ===== SUMMARY ===== --}}
      <div class="tab-pane fade" id="ca-summary">
        <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
          <h5 class="fw-semibold mb-0">Contract Summary</h5>
          <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fa fa-download me-1"></i> Download PDF</button>
        </div>
        <div id="ca-summary-content">
          <div class="table-responsive rounded mb-4" style="background:var(--gasq-muted-bg)">
            <table class="table table-sm align-middle mb-0">
              <tbody id="sum-table"></tbody>
            </table>
          </div>
          <div class="row g-3">
            <div class="col-md-3">
              <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                <div class="small text-gasq-muted mb-1">Total Annual Hours</div>
                <div class="fs-5 fw-bold" id="sum_annualHrs">0</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                <div class="small text-gasq-muted mb-1">Annual Bill Revenue</div>
                <div class="fs-5 fw-bold text-primary" id="sum_annualRev">$0.00</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                <div class="small text-gasq-muted mb-1">Annual Pay Cost</div>
                <div class="fs-5 fw-bold" id="sum_annualPay">$0.00</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="rounded p-3 text-center" style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.3)">
                <div class="small text-gasq-muted mb-1">Gross Margin</div>
                <div class="fs-5 fw-bold text-success" id="sum_gm">$0.00</div>
              </div>
            </div>
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
let rows = [];
let rowId = 0;

const CATEGORIES = ['Access Control Officer','Unarmed Security Officer','Armed Security Guard','Patrol Officer','Lobby Ambassador','Event Security'];

function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v);}
function fmtN(v,dec=1){return new Intl.NumberFormat('en-US',{minimumFractionDigits:dec,maximumFractionDigits:dec}).format(v);}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

function addRow(data={}){
  const id = ++rowId;
  rows.push(id);
  const tr = document.createElement('tr');
  tr.id = 'row-'+id;
  tr.innerHTML = `
    <td><input type="text" class="form-control form-control-sm" id="cat-${id}" value="${data.cat||CATEGORIES[id%CATEGORIES.length]||''}" oninput="recalc()"></td>
    <td class="text-center"><input type="checkbox" class="form-check-input" id="armed-${id}" ${data.armed?'checked':''} onchange="recalc()"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="hrs-${id}" value="${data.hrs||40}" step="0.5" oninput="recalc()"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="pay-${id}" value="${data.pay||18}" step="0.01" oninput="recalc()"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="bill-${id}" value="${data.bill||26}" step="0.01" oninput="recalc()"></td>
    <td><input type="number" class="form-control form-control-sm text-end" id="ot-${id}" value="${data.ot||0}" step="0.5" oninput="recalc()"></td>
    <td class="text-end font-monospace small" id="rev-${id}">$0.00</td>
    <td class="text-end font-monospace small" id="pc-${id}">$0.00</td>
    <td class="d-print-none"><button class="btn btn-outline-danger btn-sm py-0" onclick="removeRow(${id})"><i class="fa fa-trash fa-xs"></i></button></td>`;
  document.getElementById('ca-tbody').appendChild(tr);
  recalc();
}

function removeRow(id){
  rows = rows.filter(r=>r!==id);
  const el = document.getElementById('row-'+id);
  if(el) el.remove();
  recalc();
}

function gv(id){return parseFloat(document.getElementById(id)?.value)||0;}
function gb(id){return document.getElementById(id)?.checked||false;}

function recalc(){
  let totalHrs=0, totalOt=0, totalRev=0, totalPay=0;
  rows.forEach(id=>{
    const hrs = gv('hrs-'+id), pay = gv('pay-'+id), bill = gv('bill-'+id), ot = gv('ot-'+id);
    const otPayRate = pay * 1.5, otBillRate = bill * 1.5;
    const revW = (hrs*bill) + (ot*otBillRate);
    const payW = (hrs*pay) + (ot*otPayRate);
    setText('rev-'+id, fmt(revW));
    setText('pc-'+id, fmt(payW));
    totalHrs+=hrs; totalOt+=ot; totalRev+=revW; totalPay+=payW;
  });
  setText('ft-hours', fmtN(totalHrs,1));
  setText('ft-otHours', fmtN(totalOt,1));
  setText('ft-revenue', fmt(totalRev));
  setText('ft-payCost', fmt(totalPay));
  window._ca = { totalHrs, totalOt, totalRev, totalPay };
}

async function runAnalysis(){
  recalc();
  const cats = rows.map(id=>({
    category: document.getElementById('cat-'+id)?.value||'',
    armed: gb('armed-'+id),
    weeklyHours: gv('hrs-'+id),
    payRate: gv('pay-'+id),
    billRate: gv('bill-'+id),
    otHours: gv('ot-'+id),
  }));

  const payload = { version:'v24', scenario:{ categories: cats } };
  const res = await fetch('{{ route('backend.contract-analysis.v24.compute') }}', {
    method:'POST',
    headers:{
      'Content-Type':'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
      'Accept':'application/json'
    },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if(!res.ok || !data || !data.ok){ console.error(data); return; }

  const ph = data.perHour||{};
  setText('ph_avgBillRate', fmt(ph.avgBillRateWeighted||0)+'/hr');
  setText('ph_avgPayRate', fmt(ph.avgPayRateWeighted||0)+'/hr');
  setText('ph_gphr', fmt(ph.grossMarginPerHour||0)+'/hr');
  setText('ph_dlr', (ph.directLaborRatioPct||0).toFixed(1)+'%');
  setText('ph_annualHrs', fmtN(ph.annualHours||0,0));
  setText('ph_annualRev', fmt(ph.annualRevenue||0));
  setText('ph_annualPay', fmt(ph.annualPayCost||0));
  setText('ph_annualGM', fmt(ph.annualGrossMargin||0));

  const bd = document.getElementById('ph_breakdown');
  if(bd){
    bd.innerHTML = (data.rows||[]).map(r=>{
      const hrs = r.weeklyHours||0, bill = r.billRate||0, pay = r.payRate||0;
      const rev = hrs*bill*52, cost = hrs*pay*52, margin = rev>0 ? ((rev-cost)/rev)*100 : 0;
      return `<div class="d-flex justify-content-between align-items-center small p-2 rounded" style="background:var(--gasq-muted-bg)">
        <span class="text-gasq-muted">${r.category||'Category'}</span>
        <span class="fw-medium">${fmtN(hrs,1)} hrs/wk · ${fmt(bill)}/hr · <span class="${margin>0?'text-success':'text-danger'}">${margin.toFixed(1)}% margin</span></span>
      </div>`;
    }).join('');
  }

  const sum = data.summary||{};
  const sumRows = [
    ['Total Weekly Hours', fmtN((data.footers||{}).totalWeeklyHours||0,1)],
    ['Total Weekly Revenue', fmt((data.footers||{}).totalWeeklyRevenue||0)],
    ['Total Weekly Pay Cost', fmt((data.footers||{}).totalWeeklyPayCost||0)],
    ['Weekly Gross Margin', fmt(((data.footers||{}).totalWeeklyRevenue||0)-((data.footers||{}).totalWeeklyPayCost||0))],
    ['—',''],
    ['Annual Hours', fmtN(sum.annualHours||0,0)],
    ['Annual Revenue', fmt(sum.annualRevenue||0)],
    ['Annual Pay Cost', fmt(sum.annualPayCost||0)],
    ['Annual Gross Margin', fmt(sum.grossMargin||0)],
    ['Avg Bill Rate', fmt(ph.avgBillRateWeighted||0)+'/hr'],
    ['Avg Pay Rate', fmt(ph.avgPayRateWeighted||0)+'/hr'],
    ['Direct Labor Ratio', (ph.directLaborRatioPct||0).toFixed(1)+'%'],
  ];
  document.getElementById('sum-table').innerHTML = sumRows.map(([k,v])=>{
    if(v==='') return `<tr><td colspan="2" class="fw-semibold small py-2 text-gasq-muted pt-3">${k}</td></tr>`;
    return `<tr><td class="small">${k}</td><td class="text-end small font-monospace">${v}</td></tr>`;
  }).join('');
  setText('sum_annualHrs', fmtN(sum.annualHours||0,0));
  setText('sum_annualRev', fmt(sum.annualRevenue||0));
  setText('sum_annualPay', fmt(sum.annualPayCost||0));
  setText('sum_gm', fmt(sum.grossMargin||0));

  document.querySelector('[href="#ca-analysis"]').click();
}

document.addEventListener('DOMContentLoaded', ()=>{
  addRow({cat:'Access Control Officer',hrs:54,pay:18,bill:26.5,ot:0});
  addRow({cat:'Unarmed Security Officer',hrs:40,pay:16,bill:24,ot:4});
});
</script>
@endpush
