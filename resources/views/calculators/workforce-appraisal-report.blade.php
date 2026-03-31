@extends('layouts.app')

@php
  $initialTab = $initialTab ?? 'cfo';
@endphp

@section('header_variant', 'dashboard')

@section('title', 'Workforce Capital Recovery Appraisal')

@push('styles')
<style>
  .gasq-wa-hero { background: #6b0f1a; color: #fff; border-radius: 0.5rem 0.5rem 0 0; }
  .gasq-wa-section { background: #6b0f1a; color: #fff; font-weight: 600; letter-spacing: 0.02em; }
  .gasq-wa-subbanner { background: #3d4f6b; color: #fff; }
  .gasq-wa-input { background: #fff9c4 !important; }
  .gasq-wa-peach td, .gasq-wa-peach th { background: #ffe4d4 !important; }
  .gasq-wa-table-head { background: #1e3a5f; color: #fff; }
  .gasq-wa-total-row { background: rgba(6,45,121,0.12); font-weight: 600; }
  .gasq-wa-mono { font-variant-numeric: tabular-nums; }
  @media print {
    .gasq-wa-no-print { display: none !important; }
  }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-2 px-md-3" style="background:var(--gasq-background)">
  <div class="container-xl">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 gasq-wa-no-print">
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
        <span class="text-gasq-muted small">V24 compute · <code>workforce-appraisal-report</code></span>
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>

    <div class="gasq-wa-hero p-3 p-md-4 mb-0">
      <h1 class="h4 fw-bold mb-1">GASQ Workforce-to-Post™ Capital Recovery Appraisal — Full Scope Report</h1>
      <div class="small" style="opacity:.9">Workforce Total Cost of Ownership (TCO) — vs — Vendor Outsourced Security Services</div>
    </div>
    <div class="bg-white border border-top-0 rounded-bottom shadow-sm p-3 mb-3">
      <div class="row g-2 small">
        <div class="col-md-4">
          <label class="form-label fw-medium mb-0">Prepared for</label>
          <input type="text" id="wa_prep" class="form-control form-control-sm gasq-wa-input" value="" placeholder="Client / site" oninput="scheduleCompute()">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-medium mb-0">Report date</label>
          <input type="text" id="wa_date" class="form-control form-control-sm gasq-wa-input" value="{{ date('n/j/Y') }}" oninput="scheduleCompute()">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-medium mb-0">Annual billable hours (CFO × appraisal)</label>
          <input type="number" id="wa_hours" class="form-control form-control-sm gasq-wa-input" value="21322" step="1" min="1" oninput="scheduleCompute()">
        </div>
      </div>
    </div>

    <ul class="nav nav-pills flex-nowrap gap-1 mb-3 overflow-auto gasq-wa-no-print" id="wa_tablist" role="tablist">
      <li class="nav-item"><button type="button" class="nav-link {{ $initialTab === 'cfo' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#wa-pane-cfo" id="tab-cfo"><i class="fa fa-table me-1"></i> CFO Bill Rate</button></li>
      <li class="nav-item"><button type="button" class="nav-link {{ $initialTab === 'posts' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#wa-pane-posts" id="tab-posts"><i class="fa fa-users me-1"></i> Post Position Summary</button></li>
      <li class="nav-item"><button type="button" class="nav-link {{ $initialTab === 'appraisal' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#wa-pane-appraisal" id="tab-appraisal"><i class="fa fa-balance-scale me-1"></i> Appraisal Comparison</button></li>
      <li class="nav-item"><button type="button" class="nav-link {{ $initialTab === 'price' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#wa-pane-price" id="tab-price"><i class="fa fa-chart-line me-1"></i> Price Realism</button></li>
    </ul>

    <div class="row g-3">
      <div class="col-lg-4 gasq-wa-no-print">
        <div class="card gasq-card h-100">
          <div class="card-header gasq-wa-section small text-uppercase">Appraisal drivers</div>
          <div class="card-body small">
            <div class="mb-2">
              <label class="form-label mb-0">Baseline labor ($/hr)</label>
              <input type="number" id="wa_baseL" class="form-control form-control-sm gasq-wa-input" value="30.43" step="0.01" oninput="scheduleCompute()">
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Government should-cost ($/hr)</label>
              <input type="number" id="wa_govH" class="form-control form-control-sm gasq-wa-input" value="78.25" step="0.01" oninput="scheduleCompute()">
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Vendor TCO ($/hr)</label>
              <input type="number" id="wa_vendH" class="form-control form-control-sm gasq-wa-input" value="54.78" step="0.01" oninput="scheduleCompute()">
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Weekly coverage hours</label>
              <input type="number" id="wa_wkH" class="form-control form-control-sm gasq-wa-input" value="410" step="1" oninput="scheduleCompute()">
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Monthly coverage hours</label>
              <input type="number" id="wa_moH" class="form-control form-control-sm gasq-wa-input" value="1777" step="1" oninput="scheduleCompute()">
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">FTEs required</label>
              <input type="number" id="wa_ftes" class="form-control form-control-sm gasq-wa-input" value="15" step="1" min="1" oninput="scheduleCompute()">
            </div>
            <div class="mb-2">
              <label class="form-label mb-0">Annual hrs / professional</label>
              <input type="number" id="wa_hrProf" class="form-control form-control-sm gasq-wa-input" value="1456" step="1" oninput="scheduleCompute()">
            </div>
            <hr>
            <div class="mb-2">
              <label class="form-label mb-0">Memo — training ($/hr)</label>
              <input type="number" id="wa_pr_train" class="form-control form-control-sm gasq-wa-input" value="3.47" step="0.01" oninput="scheduleCompute()">
            </div>
            <div class="mb-0">
              <label class="form-label mb-0">Reserved gov rate ($/hr)</label>
              <input type="number" id="wa_pr_res" class="form-control form-control-sm gasq-wa-input" value="38.34" step="0.01" oninput="scheduleCompute()">
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="tab-content">
          <div class="tab-pane fade {{ $initialTab === 'cfo' ? 'show active' : '' }}" id="wa-pane-cfo" role="tabpanel">
            <div class="card gasq-card">
              <div class="card-header gasq-wa-section">CFO Bill Rate Breakdown</div>
              <div class="card-body p-0">
                <p class="small text-gasq-muted px-3 pt-3 mb-2">Consolidated line-by-line build — hourly × annual billable hours = annual column.</p>
                <div id="wa_cfo_root" class="table-responsive px-3 pb-3"></div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade {{ $initialTab === 'posts' ? 'show active' : '' }}" id="wa-pane-posts" role="tabpanel">
            <div class="card gasq-card">
              <div class="card-header gasq-wa-table-head d-flex justify-content-between align-items-center">
                <span>POST POSITION SUMMARY</span>
                <span class="small fw-normal opacity-75">Yellow cells drive totals</span>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0" id="wa_post_table">
                    <thead class="table-light">
                      <tr class="small">
                        <th>Post position</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Blended pay</th>
                        <th class="text-end">Annual hrs/FTE</th>
                        <th class="text-end">Weekly hrs</th>
                        <th class="text-end">Weekly $</th>
                        <th class="text-end">Monthly hrs</th>
                        <th class="text-end">Monthly $</th>
                        <th class="text-end">Annual hrs</th>
                        <th class="text-end">Annual labor $</th>
                      </tr>
                    </thead>
                    <tbody id="wa_post_body"></tbody>
                    <tfoot>
                      <tr class="gasq-wa-total-row small" id="wa_post_foot">
                        <td>ESTIMATED TOTAL</td>
                        <td class="text-center font-monospace" id="pf_qty">0</td>
                        <td class="text-end font-monospace" id="pf_avg">$0.00</td>
                        <td></td>
                        <td class="text-end font-monospace" id="pf_wh">0</td>
                        <td class="text-end font-monospace" id="pf_wc">$0.00</td>
                        <td class="text-end font-monospace" id="pf_mh">0</td>
                        <td class="text-end font-monospace" id="pf_mc">$0.00</td>
                        <td class="text-end font-monospace" id="pf_ah">0</td>
                        <td class="text-end font-monospace" id="pf_ac">$0.00</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade {{ $initialTab === 'appraisal' ? 'show active' : '' }}" id="wa-pane-appraisal" role="tabpanel">
            <div class="card gasq-card mb-3">
              <div class="card-header gasq-wa-section">Appraisal Comparison Summary</div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-bordered mb-0" id="wa_ap_tbl">
                    <thead class="table-light">
                      <tr>
                        <th>Description</th>
                        <th class="text-end gasq-wa-mono">Internal should-cost</th>
                        <th class="text-end gasq-wa-mono">Vendor TCO</th>
                      </tr>
                    </thead>
                    <tbody id="wa_ap_body"></tbody>
                    <tbody id="wa_ap_foot"></tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="card gasq-card">
              <div class="card-header gasq-wa-section">Coverage Statement</div>
              <div class="card-body small text-gasq-muted" id="wa_coverage_text"></div>
            </div>
            <div class="text-center small text-gasq-muted mt-3 mb-2">CFO Tested · CFO Approved · (470) 633-2816 · info@getasecurityquote.com · getasecurityquotenow.com</div>
          </div>

          <div class="tab-pane fade {{ $initialTab === 'price' ? 'show active' : '' }}" id="wa-pane-price" role="tabpanel">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card gasq-card h-100">
                  <div class="card-header gasq-wa-subbanner small fw-semibold">Module feeds (memo)</div>
                  <div class="card-body p-0" id="wa_pr_left"></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card gasq-card h-100">
                  <div class="card-header gasq-wa-table-head small fw-semibold">GASQ price realism review</div>
                  <div class="card-body p-0 small" id="wa_pr_right"></div>
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
(function(){
  const POST_ROWS = 10;
  const computeUrl = @json(route('backend.standalone.v24.compute', ['type' => 'workforce-appraisal-report']));
  let debounce = null;

  const defaultPosts = [
    { positionTitle: 'Unarmed S/O', qty: 8, blendedPayRate: 19.25, annualHours: 2080 },
    { positionTitle: 'Supervisor', qty: 4, blendedPayRate: 24.50, annualHours: 2080 },
    { positionTitle: 'Roving Patrol Officer', qty: 3, blendedPayRate: 21.00, annualHours: 2496 },
  ];

  function money(n){
    return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2,maximumFractionDigits:2}).format(n||0);
  }
  function num(n){ return (n===null||n===undefined||Number.isNaN(n))?'—':Number(n).toLocaleString('en-US'); }

  function buildPostBody(){
    const tb = document.getElementById('wa_post_body');
    tb.innerHTML = '';
    for(let i=0;i<POST_ROWS;i++){
      const d = defaultPosts[i] || { positionTitle:'', qty:0, blendedPayRate:0, annualHours:0 };
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><input type="text" class="form-control form-control-sm gasq-wa-input wa-p-title" data-i="${i}" value="${d.positionTitle||''}" placeholder="Role"></td>
        <td class="text-center"><input type="number" class="form-control form-control-sm gasq-wa-input text-center wa-p-qty" data-i="${i}" value="${d.qty||''}" min="0" step="1"></td>
        <td><input type="number" class="form-control form-control-sm gasq-wa-input text-end wa-p-pay" data-i="${i}" value="${d.blendedPayRate||''}" min="0" step="0.01"></td>
        <td><input type="number" class="form-control form-control-sm gasq-wa-input text-end wa-p-ann" data-i="${i}" value="${d.annualHours||''}" min="0" step="1"></td>
        <td class="text-end font-monospace small wa-p-out wh"></td>
        <td class="text-end font-monospace small wa-p-out wc"></td>
        <td class="text-end font-monospace small wa-p-out mh"></td>
        <td class="text-end font-monospace small wa-p-out mc"></td>
        <td class="text-end font-monospace small wa-p-out ah"></td>
        <td class="text-end font-monospace small wa-p-out ac"></td>`;
      tb.appendChild(tr);
    }
    tb.querySelectorAll('input').forEach(el=> el.addEventListener('input', scheduleCompute));
  }

  function collectPosts(){
    const rows = [];
    document.querySelectorAll('#wa_post_body tr').forEach((tr)=>{
      const title = tr.querySelector('.wa-p-title')?.value?.trim()||'';
      const qty = parseInt(tr.querySelector('.wa-p-qty')?.value||'0',10)||0;
      const pay = parseFloat(tr.querySelector('.wa-p-pay')?.value||'0')||0;
      const ann = parseFloat(tr.querySelector('.wa-p-ann')?.value||'0')||0;
      if(title || qty || pay || ann){
        rows.push({ positionTitle: title||'—', qty, blendedPayRate: pay, annualHours: ann });
      }
    });
    return rows.length ? rows : null;
  }

  function buildPayload(){
    const H = parseFloat(document.getElementById('wa_hours').value)||21322;
    return {
      version: 'v24',
      scenario: {
        meta: {
          annualBillableHours: H,
          appraisal: {
            preparedFor: document.getElementById('wa_prep').value||'',
            reportDate: document.getElementById('wa_date').value||'',
            baselineLaborRate: parseFloat(document.getElementById('wa_baseL').value)||0,
            governmentShouldCostHourly: parseFloat(document.getElementById('wa_govH').value)||0,
            vendorTcoHourly: parseFloat(document.getElementById('wa_vendH').value)||0,
            totalWeeklyHours: parseFloat(document.getElementById('wa_wkH').value)||0,
            totalMonthlyHours: parseFloat(document.getElementById('wa_moH').value)||0,
            totalAnnualHours: H,
            ftesRequired: parseInt(document.getElementById('wa_ftes').value||'1',10)||1,
            hoursPerProfessionalAnnual: parseFloat(document.getElementById('wa_hrProf').value)||0,
          },
          priceRealism: {
            trainingProgramPerHour: parseFloat(document.getElementById('wa_pr_train').value)||0,
            reservedGovernmentRateHourly: parseFloat(document.getElementById('wa_pr_res').value)||0,
          },
          posts: collectPosts(),
        }
      }
    };
  }

  function renderCfo(cfo){
    const el = document.getElementById('wa_cfo_root');
    if(!cfo||!cfo.sections){ el.innerHTML='<p class="text-danger small">No data</p>'; return; }
    let html = '<table class="table table-sm table-bordered align-middle gasq-wa-mono">';
    html += `<thead><tr><th>Description</th><th class="text-end">Hourly</th><th class="text-end text-success">Annual</th></tr></thead>`;
    for(const sec of cfo.sections){
      html += `<tr><td colspan="3" class="gasq-wa-section small">${sec.title}</td></tr>`;
      for(const r of sec.rows||[]){
        const hl = r.highlight ? ' table-warning' : '';
        html += `<tr class="${hl}"><td>${r.label}</td><td class="text-end">${money(r.hourly)}</td><td class="text-end text-success">${money(r.annual)}</td></tr>`;
      }
      const st = sec.subtotal||{};
      html += `<tr class="table-warning fw-semibold"><td>${st.label}</td><td class="text-end">${money(st.hourly)}</td><td class="text-end text-success">${money(st.annual)}</td></tr>`;
      if(sec.laborPlusFringe){
        const l = sec.laborPlusFringe;
        html += `<tr class="table-warning fw-bold"><td>${l.label}</td><td class="text-end">${money(l.hourly)}</td><td class="text-end text-success">${money(l.annual)}</td></tr>`;
      }
    }
    const g = cfo.grandTotal||{};
    html += `<tr class="fw-bold table-primary"><td>${g.label}</td><td class="text-end">${money(g.hourly)}</td><td class="text-end text-success">${money(g.annual)}</td></tr>`;
    html += '</table>';
    html += `<div class="small text-gasq-muted mt-2">Annual billable hours: <span class="fw-semibold">${num(cfo.annualBillableHours)}</span></div>`;
    el.innerHTML = html;
  }

  function fillPostOut(rows, totals){
    document.querySelectorAll('#wa_post_body tr').forEach((tr,i)=>{
      const r = rows[i];
      if(!r){ return; }
      tr.querySelector('.wh').textContent = num(r.weeklyHours);
      tr.querySelector('.wc').textContent = money(r.weeklyCost);
      tr.querySelector('.mh').textContent = num(r.monthlyHours);
      tr.querySelector('.mc').textContent = money(r.monthlyCost);
      tr.querySelector('.ah').textContent = num(r.annualHours);
      tr.querySelector('.ac').textContent = money(r.annualDirectLaborCost);
    });
    const t = totals||{};
    document.getElementById('pf_qty').textContent = num(t.qty);
    document.getElementById('pf_avg').textContent = money(t.blendedPayRateAvg);
    document.getElementById('pf_wh').textContent = num(t.weeklyHours);
    document.getElementById('pf_wc').textContent = money(t.weeklyCost);
    document.getElementById('pf_mh').textContent = num(t.monthlyHours);
    document.getElementById('pf_mc').textContent = money(t.monthlyCost);
    document.getElementById('pf_ah').textContent = num(t.annualHours);
    document.getElementById('pf_ac').textContent = money(t.annualDirectLaborCost);
  }

  function renderAppraisal(a){
    const b = document.getElementById('wa_ap_body');
    const f = document.getElementById('wa_ap_foot');
    b.innerHTML = '';
    f.innerHTML = '';
    if(!a||!a.rows) return;
    for(const r of a.rows){
      const vInt = (typeof r.internal==='number') ? money(r.internal) : r.internal;
      const vVen = (typeof r.vendor==='number') ? money(r.vendor) : r.vendor;
      b.innerHTML += `<tr><td>${r.description}</td><td class="text-end gasq-wa-mono">${vInt}</td><td class="text-end gasq-wa-mono">${vVen}</td></tr>`;
    }
    for(const r of (a.footerRows||[])){
      const suf = r.suffix||'';
      let vVen = '—';
      if(r.vendor !== null && r.vendor !== undefined){
        if(r.isPercent){ vVen = `${Number(r.vendor).toFixed(0)}%`; }
        else if(typeof r.vendor === 'number'){
          vVen = r.description.includes('Payback') ? `${r.vendor}${suf}` : `${money(r.vendor)}${suf}`;
        }
      }
      f.innerHTML += `<tr class="gasq-wa-peach fw-semibold"><td>${r.description}</td><td class="text-end gasq-wa-mono">—</td><td class="text-end gasq-wa-mono">${vVen}</td></tr>`;
    }
    document.getElementById('wa_coverage_text').textContent = a.coverageStatement||'';
  }

  function renderPriceRealism(p){
    const L = document.getElementById('wa_pr_left');
    const R = document.getElementById('wa_pr_right');
    if(!p){ L.innerHTML=R.innerHTML=''; return; }
    let l = '<table class="table table-sm mb-0">';
    for(const r of (p.moduleFeeds||[])){
      l += `<tr><td>${r.label}</td><td class="text-end gasq-wa-mono">${money(r.hourly)}</td><td class="text-end text-success gasq-wa-mono">${money(r.annual)}</td></tr>`;
    }
    for(const r of (p.leftSummary||[])){
      const cl = r.rateClass||''; const ca = r.annualClass||'';
      const fw = r.strong ? ' fw-bold' : '';
      l += `<tr class="${fw}"><td class="${cl}">${r.label}</td><td class="text-end gasq-wa-mono ${cl}">${money(r.hourly)}</td><td class="text-end gasq-wa-mono ${ca}">${money(r.annual)}</td></tr>`;
    }
    l += '</table>';
    L.innerHTML = l;
    let r = '<table class="table table-sm mb-0"><thead><tr><th>Benchmark rates</th><th class="text-end">Rate</th><th class="text-end">Total</th></tr></thead>';
    for(const row of (p.benchmark||[])){
      const cl = row.rateClass||''; const ca = row.annualClass||'';
      const fw = row.strong ? ' fw-bold' : '';
      r += `<tr class="${fw}"><td class="${cl}">${row.label}</td><td class="text-end gasq-wa-mono ${cl}">${money(row.hourly)}</td><td class="text-end gasq-wa-mono ${ca}">${money(row.annual)}</td></tr>`;
    }
    r += '</table>';
    R.innerHTML = r;
  }

  async function runCompute(){
    const res = await fetch(computeUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify(buildPayload())
    });
    const data = await res.json();
    if(!res.ok||!data.ok){ console.error(data); return; }
    const k = data.kpis||{};
    renderCfo(k.cfoBillRate);
    const p = k.postPositionSummary||{};
    fillPostOut(p.rows||[], p.totals||{});
    renderAppraisal(k.appraisalComparison||{});
    renderPriceRealism(k.priceRealism||{});
  }

  window.scheduleCompute = function(){
    clearTimeout(debounce);
    debounce = setTimeout(runCompute, 260);
  };

  document.addEventListener('DOMContentLoaded', ()=>{
    buildPostBody();
    const tab = @json($initialTab);
    const map = { cfo:'tab-cfo', posts:'tab-posts', appraisal:'tab-appraisal', price:'tab-price' };
    const id = map[tab] || 'tab-cfo';
    const btn = document.getElementById(id);
    if(btn && window.bootstrap){ new bootstrap.Tab(btn).show(); }
    runCompute();
  });
})();
</script>
@endpush
