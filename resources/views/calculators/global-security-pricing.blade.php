@extends('layouts.app')
@section('title', 'Global Security Pricing')
@section('header_variant', 'dashboard')

@section('content')
<div class="py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
        <i class="fa fa-earth-americas text-primary"></i> Global Security Pricing
      </h1>
      <div class="text-gasq-muted small">V24 compute matches shipped UI logic (React bundle <code>_De</code>).</div>
    </div>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('contract-analysis.index') }}">Open Contract Analysis</a>
  </div>

  <div class="card gasq-card">
    <div class="card-header px-3 px-md-4 pt-3 pb-0 d-print-none" style="background:transparent;border-bottom:none">
      <div class="gasq-tabs-scroll">
        <ul class="gasq-tabs-pill mb-0" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#gsp-categories"><i class="fa fa-users me-1"></i> Posts/Categories</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#gsp-contract"><i class="fa fa-file-contract me-1"></i> Contract Analysis</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#gsp-billrate"><i class="fa fa-percent me-1"></i> Bill Rate Analysis</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#gsp-contractsum"><i class="fa fa-list me-1"></i> Contract Summary</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#gsp-settings"><i class="fa fa-gear me-1"></i> Settings</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#gsp-benefits"><i class="fa fa-heart-pulse me-1"></i> Benefits</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#gsp-costs"><i class="fa fa-truck me-1"></i> Additional Costs</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#gsp-summary"><i class="fa fa-chart-pie me-1"></i> Summary</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#gsp-compare"><i class="fa fa-code-compare me-1"></i> Scenario Comparison</a></li>
        </ul>
      </div>
    </div>
    <div class="card-body p-4">
      <div class="d-flex justify-content-end gap-2 mb-3 d-print-none">
        <button type="button" class="btn btn-primary btn-sm" id="gsp_run"><i class="fa fa-play me-1"></i> Run pricing</button>
      </div>
      <div class="tab-content">

        <div class="tab-pane fade show active" id="gsp-categories">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <p class="text-gasq-muted small mb-0">Posts feed payroll and billing roll-ups (same shape as React <code>c</code> state).</p>
            <button type="button" class="btn btn-outline-primary btn-sm" id="gsp_addPost"><i class="fa fa-plus me-1"></i> Add post</button>
          </div>
          <div class="table-responsive">
            <table class="table table-sm align-middle" id="gsp_postsTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Armed</th>
                  <th>Position</th>
                  <th class="text-end">Weekly hrs</th>
                  <th class="text-end">ST pay</th>
                  <th class="text-end">ST bill</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="gsp_postsBody"></tbody>
            </table>
          </div>
        </div>

        <div class="tab-pane fade" id="gsp-contract">
          <div id="gsp_contractPane" class="text-gasq-muted small">Run pricing to show annual bill, labor, and contributory profit.</div>
        </div>

        <div class="tab-pane fade" id="gsp-billrate">
          <div id="gsp_billRatePane" class="text-gasq-muted small">Run pricing to load per-post bill-rate breakdown.</div>
        </div>

        <div class="tab-pane fade" id="gsp-contractsum">
          <div id="gsp_contractSumPane" class="text-gasq-muted small">Run pricing for summary totals (React hard-coded slice + computed hours).</div>
        </div>

        <div class="tab-pane fade" id="gsp-settings">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label small">County / label</label><input type="text" class="form-control form-control-sm" id="gsp_county" value="MECKLENBERG COUNTY"></div>
            <div class="col-md-6"><label class="form-label small">Contract type</label><input type="text" class="form-control form-control-sm" id="gsp_contractType" value="permanent"></div>
            <div class="col-md-4"><label class="form-label small">Holidays / year</label><input type="number" class="form-control form-control-sm" id="gsp_holidays" value="88" step="1"></div>
            <div class="col-md-4"><label class="form-label small">Vacation weeks</label><input type="number" class="form-control form-control-sm" id="gsp_vacationWeeks" value="1" step="0.5"></div>
            <div class="col-md-4"><label class="form-label small">Anticipated turnover</label><input type="number" class="form-control form-control-sm" id="gsp_turnover" value="4" step="1"></div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="gsp_useFixedHeadcount" checked>
                <label class="form-check-label small" for="gsp_useFixedHeadcount">Use fixed headcount (vs system-generated)</label>
              </div>
            </div>
            <div class="col-md-4"><label class="form-label small">Fixed headcount</label><input type="number" class="form-control form-control-sm" id="gsp_fixedHeadcount" value="262" step="1"></div>
          </div>
        </div>

        <div class="tab-pane fade" id="gsp-benefits">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label small">H&amp;W $/hr</label><input type="number" class="form-control form-control-sm" id="gsp_hwPerHour" value="4.93" step="0.01"></div>
            <div class="col-md-4"><label class="form-label small">Medical</label><input type="number" class="form-control form-control-sm" id="gsp_b_med" value="0" step="0.01"></div>
            <div class="col-md-4"><label class="form-label small">Life</label><input type="number" class="form-control form-control-sm" id="gsp_b_life" value="0" step="0.01"></div>
            <div class="col-md-4"><label class="form-label small">Dental</label><input type="number" class="form-control form-control-sm" id="gsp_b_dental" value="0" step="0.01"></div>
            <div class="col-md-4"><label class="form-label small">401k</label><input type="number" class="form-control form-control-sm" id="gsp_b_k401" value="0" step="0.01"></div>
            <div class="col-md-4"><label class="form-label small">Other</label><input type="number" class="form-control form-control-sm" id="gsp_b_other" value="0" step="0.01"></div>
          </div>
        </div>

        <div class="tab-pane fade" id="gsp-costs">
          <h6 class="fw-semibold">Equipment (values summed into totalEquipmentCost)</h6>
          <div class="row g-2 mb-3">
            <div class="col-md-4"><label class="form-label small">Communications</label><input type="number" class="form-control form-control-sm gsp-eq" data-k="communications" value="0"></div>
            <div class="col-md-4"><label class="form-label small">Guard tour</label><input type="number" class="form-control form-control-sm gsp-eq" data-k="guardTourSystem" value="0"></div>
            <div class="col-md-4"><label class="form-label small">Weapons</label><input type="number" class="form-control form-control-sm gsp-eq" data-k="weapons" value="141900"></div>
            <div class="col-md-4"><label class="form-label small">Cell phones</label><input type="number" class="form-control form-control-sm gsp-eq" data-k="cellPhones" value="3000"></div>
            <div class="col-md-4"><label class="form-label small">Other 1</label><input type="number" class="form-control form-control-sm gsp-eq" data-k="other1" value="0"></div>
            <div class="col-md-4"><label class="form-label small">Other 2</label><input type="number" class="form-control form-control-sm gsp-eq" data-k="other2" value="0"></div>
          </div>
          <h6 class="fw-semibold">Recruiting (summed)</h6>
          <div class="row g-2 mb-3">
            <div class="col-md-4"><label class="form-label small">Recruiting</label><input type="number" class="form-control form-control-sm gsp-rc" data-k="recruiting" value="0"></div>
            <div class="col-md-4"><label class="form-label small">Medical screening</label><input type="number" class="form-control form-control-sm gsp-rc" data-k="medicalScreening" value="0"></div>
            <div class="col-md-4"><label class="form-label small">Drug testing</label><input type="number" class="form-control form-control-sm gsp-rc" data-k="drugTesting" value="75"></div>
            <div class="col-md-4"><label class="form-label small">Background</label><input type="number" class="form-control form-control-sm gsp-rc" data-k="backgroundInvest" value="50"></div>
            <div class="col-md-4"><label class="form-label small">Psych</label><input type="number" class="form-control form-control-sm gsp-rc" data-k="psychTesting" value="0"></div>
            <div class="col-md-4"><label class="form-label small">Other</label><input type="number" class="form-control form-control-sm gsp-rc" data-k="other" value="0"></div>
          </div>
          <h6 class="fw-semibold">Vehicle / fleet bucket</h6>
          <div class="row g-2">
            <div class="col-md-3"><label class="form-label small">Annual cost</label><input type="number" class="form-control form-control-sm gsp-vc" data-k="annualCost" value="0"></div>
            <div class="col-md-3"><label class="form-label small">Mileage</label><input type="number" class="form-control form-control-sm gsp-vc" data-k="mileage" value="0"></div>
            <div class="col-md-3"><label class="form-label small">Allowance</label><input type="number" class="form-control form-control-sm gsp-vc" data-k="annualAllowance" value="0"></div>
            <div class="col-md-3"><label class="form-label small">Damage</label><input type="number" class="form-control form-control-sm gsp-vc" data-k="vehicleDamage" value="0"></div>
          </div>
        </div>

        <div class="tab-pane fade" id="gsp-summary">
          <div id="gsp_summaryPane" class="text-gasq-muted small">Run pricing for KPI cards and bill-rate composition.</div>
        </div>

        <div class="tab-pane fade" id="gsp-compare">
          <div class="row g-2 mb-3">
            <div class="col-md-6"><input type="text" class="form-control form-control-sm" id="gsp_snapName" placeholder="Scenario label"></div>
            <div class="col-md-6"><button type="button" class="btn btn-outline-primary btn-sm" id="gsp_snapSave"><i class="fa fa-floppy-disk me-1"></i> Save snapshot (browser)</button></div>
          </div>
          <div id="gsp_snapList" class="small text-gasq-muted">No snapshots yet.</div>
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
  let lastKpis = null;
  let postId = 0;

  function readEquipment() {
    const o = {};
    document.querySelectorAll('.gsp-eq').forEach(el => { o[el.dataset.k] = parseFloat(el.value) || 0; });
    return o;
  }
  function readRecruiting() {
    const o = {};
    document.querySelectorAll('.gsp-rc').forEach(el => { o[el.dataset.k] = parseFloat(el.value) || 0; });
    return o;
  }
  function readVehicleCosts() {
    const o = {};
    document.querySelectorAll('.gsp-vc').forEach(el => { o[el.dataset.k] = parseFloat(el.value) || 0; });
    return o;
  }

  function readPosts() {
    const rows = [];
    document.querySelectorAll('#gsp_postsBody tr').forEach(tr => {
      rows.push({
        id: parseInt(tr.dataset.pid, 10),
        armed: tr.querySelector('.gsp-post-armed').checked,
        position: tr.querySelector('.gsp-post-pos').value,
        weeklyHours: parseFloat(tr.querySelector('.gsp-post-wh').value) || 0,
        stPayRate: parseFloat(tr.querySelector('.gsp-post-pay').value) || 0,
        overtimeUnbilled: 0,
        overtimeBilled: 0,
        stBillRate: parseFloat(tr.querySelector('.gsp-post-bill').value) || 0,
      });
    });
    return rows;
  }

  function readScenario() {
    return {
      county: document.getElementById('gsp_county').value,
      contractType: document.getElementById('gsp_contractType').value,
      holidaysPerYear: parseFloat(document.getElementById('gsp_holidays').value) || 0,
      vacationWeeks: parseFloat(document.getElementById('gsp_vacationWeeks').value) || 0,
      anticipatedTurnover: parseFloat(document.getElementById('gsp_turnover').value) || 0,
      useFixedHeadcount: document.getElementById('gsp_useFixedHeadcount').checked,
      fixedHeadcount: parseFloat(document.getElementById('gsp_fixedHeadcount').value) || 0,
      benefits: {
        hwPerHour: parseFloat(document.getElementById('gsp_hwPerHour').value) || 0,
        medical: parseFloat(document.getElementById('gsp_b_med').value) || 0,
        life: parseFloat(document.getElementById('gsp_b_life').value) || 0,
        dental: parseFloat(document.getElementById('gsp_b_dental').value) || 0,
        k401: parseFloat(document.getElementById('gsp_b_k401').value) || 0,
        other: parseFloat(document.getElementById('gsp_b_other').value) || 0,
      },
      equipment: readEquipment(),
      recruiting: readRecruiting(),
      vehicleCosts: readVehicleCosts(),
      posts: readPosts(),
    };
  }

  function fmtMoney(x) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(x);
  }

  function renderPostRow(p) {
    const tr = document.createElement('tr');
    tr.dataset.pid = String(p.id);
    tr.innerHTML = `
      <td>${p.id}</td>
      <td><input type="checkbox" class="form-check-input gsp-post-armed" ${p.armed ? 'checked' : ''}></td>
      <td><input type="text" class="form-control form-control-sm gsp-post-pos" value="${(p.position||'').replace(/"/g,'&quot;')}"></td>
      <td><input type="number" class="form-control form-control-sm text-end gsp-post-wh" value="${p.weeklyHours}" step="0.5"></td>
      <td><input type="number" class="form-control form-control-sm text-end gsp-post-pay" value="${p.stPayRate}" step="0.01"></td>
      <td><input type="number" class="form-control form-control-sm text-end gsp-post-bill" value="${p.stBillRate}" step="0.01"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger gsp-rm-post"><i class="fa fa-times"></i></button></td>`;
    tr.querySelector('.gsp-rm-post').addEventListener('click', () => {
      if (document.querySelectorAll('#gsp_postsBody tr').length <= 1) { alert('Keep at least one post.'); return; }
      tr.remove(); schedule();
    });
    tr.querySelectorAll('input').forEach(i => i.addEventListener('input', schedule));
    return tr;
  }

  let t = null;
  function schedule() { clearTimeout(t); t = setTimeout(runPricing, 400); }

  async function runPricing() {
    const scenario = readScenario();
    try {
      const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'global-security-pricing']) }}', {
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
      paintAll(data.kpis);
    } catch (e) { console.error(e); }
  }

  function paintAll(k) {
    const fe = k.fe || {};
    const br = k.billRateAnalysis || {};
    const bc = k.billComponents || {};

    document.getElementById('gsp_contractPane').innerHTML = `
      <div class="row g-3">
        <div class="col-md-4"><div class="p-3 rounded" style="background:var(--gasq-muted-bg)"><div class="small text-gasq-muted">Total billing (annual)</div><div class="fs-5 fw-bold">${fmtMoney(fe.totalBilling||0)}</div></div></div>
        <div class="col-md-4"><div class="p-3 rounded" style="background:var(--gasq-muted-bg)"><div class="small text-gasq-muted">Direct labor</div><div class="fs-5 fw-bold">${fmtMoney(fe.totalDirectLabor||0)}</div></div></div>
        <div class="col-md-4"><div class="p-3 rounded" style="background:var(--gasq-muted-bg)"><div class="small text-gasq-muted">Contributory profit</div><div class="fs-5 fw-bold">${fmtMoney(fe.annualContributoryProfit||0)}</div></div></div>
        <div class="col-md-6"><div class="small text-gasq-muted">Headcount used</div><div class="fw-semibold">${fe.headcountUsed} (system would be ${fe.systemGeneratedHeadcount})</div></div>
        <div class="col-md-6"><div class="small text-gasq-muted">$ / hour (profit)</div><div class="fw-semibold">${(fe.perHourProfit||0).toFixed(4)}</div></div>
      </div>`;

    let rows = (br.analysisData||[]).map(r => `<tr>
      <td>${r.category}</td><td class="text-end">${r.weeklyHours}</td><td class="text-end">${r.stBillRate}</td>
      <td class="text-end">${r.totalRevenue}</td><td class="text-end">${r.totalWages}</td><td class="text-end">${r.contributoryProfitPercent}%</td>
    </tr>`).join('');
    document.getElementById('gsp_billRatePane').innerHTML = `<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Category</th><th class="text-end">Wk hrs</th><th class="text-end">ST bill</th><th class="text-end">Revenue</th><th class="text-end">Wages</th><th class="text-end">Contrib %</th></tr></thead><tbody>${rows}</tbody></table></div>`;

    const tot = br.totals || {};
    document.getElementById('gsp_contractSumPane').innerHTML = `<div class="table-responsive"><table class="table table-sm"><tbody>` +
      Object.keys(tot).map(key => `<tr><td class="text-gasq-muted">${key}</td><td class="text-end fw-medium">${typeof tot[key]==='number'?tot[key]:tot[key]}</td></tr>`).join('') +
      `</tbody></table></div>`;

    let compRows = (bc.components||[]).map(c => `<tr><td>${c.name}</td><td class="text-end">${c.value}</td><td class="text-end">${c.percentage}%</td></tr>`).join('');
    document.getElementById('gsp_summaryPane').innerHTML = `
      <p class="small text-gasq-muted">Static mix from React <code>de</code> memo — total ${bc.totalBillRate||''}</p>
      <div class="row g-3 mb-3">${document.getElementById('gsp_contractPane').innerHTML}</div>
      <h6 class="fw-semibold">Bill components ($/hr)</h6>
      <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Component</th><th class="text-end">$</th><th class="text-end">%</th></tr></thead><tbody>${compRows}</tbody></table></div>`;
  }

  document.getElementById('gsp_addPost').addEventListener('click', () => {
    postId += 1;
    document.getElementById('gsp_postsBody').appendChild(renderPostRow({
      id: postId, armed: false, position: '', weeklyHours: 40, stPayRate: 18, stBillRate: 26,
    }));
    schedule();
  });

  document.getElementById('gsp_run').addEventListener('click', runPricing);
  document.querySelectorAll('#gsp-settings input, #gsp-benefits input, #gsp-costs input').forEach(el => el.addEventListener('input', schedule));

  document.getElementById('gsp_snapSave').addEventListener('click', () => {
    const name = document.getElementById('gsp_snapName').value.trim() || ('Scenario ' + new Date().toISOString().slice(0,19));
    if (!lastKpis) { alert('Run pricing first'); return; }
    const key = 'gsp-snapshots';
    const list = JSON.parse(localStorage.getItem(key) || '[]');
    list.push({ name, ts: Date.now(), fe: lastKpis.fe, totalBilling: lastKpis.fe?.totalBilling });
    localStorage.setItem(key, JSON.stringify(list));
    renderSnaps();
  });

  function renderSnaps() {
    const list = JSON.parse(localStorage.getItem('gsp-snapshots') || '[]');
    const el = document.getElementById('gsp_snapList');
    if (!list.length) { el.textContent = 'No snapshots yet.'; return; }
    el.innerHTML = '<ul class="mb-0 ps-3">' + list.map(s =>
      `<li><strong>${s.name}</strong> — billing ${fmtMoney(s.totalBilling||0)} <span class="text-gasq-muted">(${new Date(s.ts).toLocaleString()})</span></li>`
    ).join('') + '</ul>';
  }

  document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('gsp_postsBody');
    postId = 3;
    [[1,true,'Package #1, VCW, Uptown',4328,33,59],[2,true,'Package #2, Libraries',873,33,59],[3,true,'Package #3, Parks',2033,33,59]].forEach(d => {
      body.appendChild(renderPostRow({ id:d[0], armed:d[1], position:d[2], weeklyHours:d[3], stPayRate:d[4], stBillRate:d[5] }));
    });
    document.querySelectorAll('.gsp-eq, .gsp-rc, .gsp-vc').forEach(el => el.addEventListener('input', schedule));
    renderSnaps();
    runPricing();
  });
})();
</script>
@endpush
