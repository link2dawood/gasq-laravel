@extends('layouts.app')
@section('title', 'Bill Rate Breakdown')
@section('header_variant', 'dashboard')

@php
  use App\Services\V24\Standalone\BillRateBreakdownEngine;
  $brbDefaults = BillRateBreakdownEngine::defaults();
  $cur = \App\Support\Currency::symbol();

  // Layer 1-6 sections: [id, number, title, blurb]. Layer 7 lives on the Five-Year tab.
  $layers = [
    ['l1', 1, 'Direct Labor', 'Coverage, manpower, positions, wages and the weighted direct-labor wage.'],
    ['l2', 2, 'Employer Labor Burden', 'Converts the weighted wage into the employer full-burden labor cost.'],
    ['l3', 3, 'Workforce Maintenance & Final Bill Rate', 'Paid-but-not-protective time and the approved baseline Final Bill Rate.'],
    ['l4', 4, 'Other Direct Costs', 'Contract-specific operating expenses, normalised to $/hour.'],
    ['l5', 5, 'G&A / Operating Contingency', 'Allocated company operating costs, normalised to $/hour.'],
    ['l6', 6, 'Profit', 'Profit-margin gross-up — not a markup on cost.'],
  ];
@endphp

@push('styles')
<style>
  .brb-kicker { font-size:.72rem; text-transform:uppercase; letter-spacing:.12em; color:var(--gasq-muted); }
  .brb-mono { font-variant-numeric: tabular-nums; }
  .brb-sticky { position: sticky; top: 1.25rem; }
  @media (max-width: 1199.98px) { .brb-sticky { position: static; } }

  .brb-mode { display:flex; gap:.5rem; flex-wrap:wrap; }
  .brb-mode button { flex:1 1 220px; text-align:left; border:1px solid rgba(6,45,121,.18); background:#fff; border-radius:.9rem; padding:.75rem 1rem; }
  .brb-mode button.active { border-color:var(--gasq-primary); background:rgba(6,45,121,.06); box-shadow:inset 0 0 0 1px var(--gasq-primary); }
  .brb-mode .t { font-weight:700; color:var(--gasq-primary); }
  .brb-mode .d { font-size:.8rem; color:var(--gasq-muted); }

  .brb-layers .accordion-item { border:1px solid rgba(15,23,42,.1); border-radius:1rem !important; overflow:hidden; margin-bottom:.75rem; }
  .brb-layers .accordion-button { gap:.9rem; padding:.9rem 1.1rem; background:#fff; }
  .brb-layers .accordion-button:not(.collapsed) { background:rgba(6,45,121,.05); color:inherit; box-shadow:none; }
  .brb-num { width:2rem; height:2rem; border-radius:50%; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; background:var(--gasq-primary); color:#fff; font-weight:700; font-size:.85rem; }
  .brb-head-title { font-weight:700; }
  .brb-head-blurb { font-size:.78rem; color:var(--gasq-muted); }
  .brb-head-value { margin-left:auto; margin-right:.75rem; text-align:right; font-weight:700; color:var(--gasq-primary); white-space:nowrap; }
  .brb-badge { font-size:.68rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; border-radius:999px; padding:.2rem .55rem; }
  .brb-badge.embedded { background:rgba(6,45,121,.1); color:var(--gasq-primary); }
  .brb-badge.additive { background:rgba(234,88,12,.12); color:#c2410c; }
  .brb-badge.calc { background:rgba(22,163,74,.12); color:#15803d; }
  .brb-badge.over { background:rgba(234,88,12,.12); color:#c2410c; }

  .brb-panel { border:1px solid rgba(15,23,42,.08); border-radius:.9rem; background:#fff; padding:1rem; }
  .brb-panel-muted { background:rgba(6,45,121,.04); }
  .brb-kv { display:flex; justify-content:space-between; gap:1rem; padding:.3rem 0; font-size:.88rem; }
  .brb-kv > span:first-child { color:var(--gasq-muted); }
  .brb-kv > span:last-child { font-weight:600; font-variant-numeric:tabular-nums; }
  .brb-kv.total { border-top:1px solid rgba(15,23,42,.12); margin-top:.3rem; padding-top:.5rem; }
  .brb-kv.total > span { color:inherit; font-weight:700; }

  .brb-table { font-size:.84rem; margin-bottom:0; }
  .brb-table th { font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:var(--gasq-muted); font-weight:600; white-space:nowrap; background:rgba(6,45,121,.04); }
  .brb-table td { vertical-align:middle; }
  .brb-table td:first-child { white-space:nowrap; }
  .brb-table .form-control, .brb-table .form-select { font-size:.84rem; padding:.25rem .45rem; min-width:70px; }
  .brb-table .form-select { min-width:135px; padding-right:1.8rem; }
  .brb-table td.calc { font-variant-numeric:tabular-nums; white-space:nowrap; text-align:right; }
  .brb-table tfoot td { font-weight:700; background:rgba(6,45,121,.04); }
  .brb-table tr.off td:not(:first-child) { opacity:.5; }

  .brb-how summary { font-size:.8rem; color:var(--gasq-primary); cursor:pointer; font-weight:600; }
  .brb-how .f { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.78rem; background:rgba(6,45,121,.04); border-radius:.6rem; padding:.6rem .8rem; margin-top:.5rem; line-height:1.7; }

  .brb-note { border-left:3px solid var(--gasq-primary); background:rgba(6,45,121,.05); padding:.6rem .8rem; border-radius:0 .6rem .6rem 0; font-size:.82rem; }
  .brb-note.warn { border-color:#d97706; background:rgba(217,119,6,.07); }

  .brb-summary { border-radius:1rem; overflow:hidden; border:1px solid rgba(6,45,121,.15); background:#fff; }
  .brb-summary-hero { background:var(--gasq-primary); color:#fff; padding:1.1rem 1.2rem; }
  .brb-summary-hero .v { font-size:2.2rem; font-weight:800; line-height:1.1; font-variant-numeric:tabular-nums; }
  .brb-summary-body { padding:.9rem 1.2rem 1.1rem; }

  .brb-warnings .item { font-size:.84rem; padding:.45rem .7rem; border-radius:.55rem; margin-bottom:.35rem; display:flex; gap:.5rem; }
  .brb-warnings .item.error { background:rgba(220,38,38,.08); color:#991b1b; }
  .brb-warnings .item.warning { background:rgba(217,119,6,.09); color:#92400e; }
  .brb-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.72rem; font-weight:700; opacity:.7; margin-right:.3rem; }

  .brb-stat { border:1px solid rgba(6,45,121,.1); border-radius:.9rem; padding:.8rem 1rem; background:#fff; height:100%; }
  .brb-stat .l { font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; color:var(--gasq-muted); }
  .brb-stat .v { font-size:1.3rem; font-weight:700; color:var(--gasq-primary); font-variant-numeric:tabular-nums; }
  @media print { .brb-layers .collapse { display:block !important; } }
</style>
@endpush

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  {{-- ── Header ─────────────────────────────────────────── --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-layer-group text-primary"></i> Bill Rate Breakdown
        </h1>
        <div class="text-gasq-muted small">Seven-layer pricing &amp; five-year forecast engine</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button type="button" class="btn btn-outline-secondary btn-sm" id="brb_reset"><i class="fa fa-rotate-left me-1"></i> Reset to GASQ baseline</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
  </div>

  {{-- ── Pricing mode ───────────────────────────────────── --}}
  <div class="card gasq-card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="brb-kicker">Pricing Mode</div>
        <div class="small text-gasq-muted" id="brb_mode_note"></div>
      </div>
      <div class="brb-mode" id="brb_mode" role="radiogroup" aria-label="Pricing mode">
        <button type="button" data-mode="approved" role="radio">
          <div class="t">Approved Final Rate / Allocation</div>
          <div class="d">The Layer 3 rate is the Final Bill Rate. Layers 4-6 are shown as embedded allocations and never added on top.</div>
        </button>
        <button type="button" data-mode="buildup" role="radio">
          <div class="t">Full Cost Build-Up</div>
          <div class="d">Additive: Layer 3 labor subtotal + Other Direct Costs + G&amp;A, then grossed up for profit margin.</div>
        </button>
      </div>
    </div>
  </div>

  <div class="brb-warnings mb-3" id="brb_warnings" hidden></div>
  <div class="alert alert-light border gasq-border small d-print-none mb-3" id="br_error" style="display:none"></div>

  <div class="row g-4">
    {{-- ── Workspace ──────────────────────────────────── --}}
    <div class="col-xl-8">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="gasq-tabs-scroll d-print-none">
          <ul class="gasq-tabs-pill mb-0" role="tablist" id="brTabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#brb-current"><i class="fa fa-calendar-day me-1"></i> Current Year</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#brb-forecast"><i class="fa fa-chart-line me-1"></i> Five-Year Plan</a></li>
          </ul>
        </div>
        <div class="d-flex gap-2 d-print-none">
          <button type="button" class="btn btn-link btn-sm p-0" data-expand="1">Expand all</button>
          <span class="text-gasq-muted">·</span>
          <button type="button" class="btn btn-link btn-sm p-0" data-expand="0">Collapse all</button>
        </div>
      </div>

      <div class="tab-content">
        {{-- ════════ CURRENT YEAR ════════ --}}
        <div class="tab-pane fade show active" id="brb-current">
          <div class="accordion brb-layers" id="brb_layers">
            @foreach($layers as [$id, $n, $title, $blurb])
              <div class="accordion-item" id="brb_{{ $id }}">
                <h2 class="accordion-header">
                  <button class="accordion-button {{ $n === 1 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#brb_{{ $id }}_body" aria-expanded="{{ $n === 1 ? 'true' : 'false' }}">
                    <span class="brb-num">{{ $n }}</span>
                    <span>
                      <span class="brb-head-title d-block">Layer {{ $n }} — {{ $title }}</span>
                      <span class="brb-head-blurb d-none d-md-block">{{ $blurb }}</span>
                    </span>
                    <span class="brb-head-value">
                      <span class="d-block brb-mono" id="brb_{{ $id }}_head"></span>
                      @if($n >= 4)<span class="brb-badge embedded" data-treatment></span>@endif
                    </span>
                  </button>
                </h2>
                <div id="brb_{{ $id }}_body" class="accordion-collapse collapse {{ $n === 1 ? 'show' : '' }}">
                  <div class="accordion-body">
                    @include('calculators.bill-rate._layer-' . $n)
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          {{-- Buyer benchmark + capital recovery sit outside the vendor layers --}}
          @include('calculators.bill-rate._benchmark')
        </div>

        {{-- ════════ FIVE-YEAR PLAN ════════ --}}
        <div class="tab-pane fade" id="brb-forecast">
          @include('calculators.bill-rate._layer-7')
        </div>
      </div>
    </div>

    {{-- ── Pricing summary card ───────────────────────── --}}
    <div class="col-xl-4">
      <div class="brb-sticky d-flex flex-column gap-3">
        <div class="brb-summary">
          <div class="brb-summary-hero">
            <div class="brb-kicker" style="color:rgba(255,255,255,.75)">Final Bill Rate</div>
            <div class="v"><span data-out="summary.finalBillRate" data-fmt="money"></span><span style="font-size:1rem;opacity:.75">/hr</span></div>
            <div class="small" style="opacity:.8" id="brb_rate_status"></div>
          </div>
          <div class="brb-summary-body">
            <div class="brb-kv"><span>Weighted Wage</span><span data-out="summary.weightedWage" data-fmt="money"></span></div>
            <div class="brb-kv"><span>Employer Full-Burden Cost</span><span data-out="summary.fullBurden" data-fmt="money"></span></div>
            <div class="brb-kv"><span>Cost to Protect</span><span data-out="summary.costToProtect" data-fmt="money"></span></div>
            <div class="brb-kv" data-show="cro"><span>Capital Recovery Opportunity</span><span><span data-out="summary.croHourly" data-fmt="money"></span>/hr</span></div>
            <div class="brb-kv" data-show="cro"><span>Annual Capital Recovery</span><span data-out="summary.annualCro" data-fmt="money"></span></div>
            <div class="brb-kv" data-show="premium" hidden><span>Premium Above Cost to Protect</span><span><span data-out="summary.premiumAboveCtp" data-fmt="money"></span>/hr</span></div>
            <div class="brb-kv total"><span>Annual Contract Value</span><span data-out="summary.annualContractValue" data-fmt="money0"></span></div>
            <hr class="my-2">
            <div class="brb-kv"><span>Required Manpower</span><span data-out="summary.requiredManpower" data-fmt="int"></span></div>
            <div class="brb-kv"><span>Equivalent 24/7 Posts</span><span data-out="summary.equivalentPosts" data-fmt="dec3"></span></div>
            <div class="brb-kv"><span>Manpower per Post</span><span data-out="summary.manpowerPerPost" data-fmt="dec2"></span></div>
            <div class="brb-kv"><span>Staffing Reserve Capacity</span><span><span data-out="summary.staffingReserveCapacity" data-fmt="int"></span> hrs</span></div>
            <div class="brb-kv"><span>Pricing Mode</span><span id="brb_mode_label"></span></div>
          </div>
        </div>

        {{-- Rate reconciliation --}}
        <div class="brb-panel">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0">Rate Reconciliation</h6>
            <span class="brb-badge embedded" id="brb_recon_badge"></span>
          </div>
          <div class="brb-kv"><span id="brb_recon_labor_label">Employer full-burden labor (L1-2)</span><span data-out="reconciliation.laborBase" data-fmt="money"></span></div>
          <div class="brb-kv"><span>Other Direct Costs (L4)</span><span data-out="reconciliation.odc" data-fmt="money"></span></div>
          <div class="brb-kv"><span>G&amp;A (L5)</span><span data-out="reconciliation.ga" data-fmt="money"></span></div>
          <div class="brb-kv"><span>Profit (L6)</span><span data-out="reconciliation.profit" data-fmt="money"></span></div>
          <div class="brb-kv total"><span id="brb_recon_total_label">Embedded Allocation Total</span><span data-out="reconciliation.embeddedTotal" data-fmt="money"></span></div>
          <div class="brb-kv"><span>Final Bill Rate</span><span data-out="reconciliation.finalBillRate" data-fmt="money"></span></div>
          <div class="brb-kv" id="brb_recon_remaining_row"><span id="brb_recon_remaining_label">Remaining</span><span id="brb_recon_remaining" class="brb-mono"></span></div>
          <div class="small text-gasq-muted mt-2" id="brb_recon_note"></div>
        </div>
      </div>
    </div>
  </div>

  <x-report-actions reportType="bill-rate-analysis" />

</div>
</div>
@endsection

@push('scripts')
<script>
const BRB_DEFAULTS = {{ \Illuminate\Support\Js::from($brbDefaults) }};
const BRB_POSITION_CATALOGUE = [
  { name: 'Dedicated Field Supervisor', type: 'supervision', hint: 'When dedicated to the account or required by scope' },
  { name: 'Dedicated Account Manager', type: 'support', hint: 'When substantially dedicated to the contract' },
  { name: 'Dispatcher / Control Room Operator', type: 'specialized', hint: 'When dispatch, CCTV, alarm, or communications support is required' },
  { name: 'Mobile Patrol Officer', type: 'core', hint: 'When mobile patrol is part of the scope' },
  { name: 'Armed Officer', type: 'specialized', hint: 'When armed services are required' },
  { name: 'Screening / Access-Control Officer', type: 'specialized', hint: 'When screening/access control is a distinct labor classification' },
  { name: 'Dedicated Training Officer', type: 'support', hint: 'Only when dedicated to the account' },
  { name: 'Custom Position', type: 'custom', hint: 'Any contract-specific labor classification' },
];
const BRB_TYPE_LABELS = { core:'Core Coverage', relief:'Relief', supervision:'Supervision', specialized:'Specialized', support:'Dedicated Support', custom:'Custom' };
const BRB_ODC_BASES = { hour:'$ / hour', employee:'$ / employee / yr', post:'$ / post / yr', month:'$ / month', year:'$ / year', contract:'Fixed contract amount' };
const BRB_GA_METHODS = { pct:'% of subtotal', hourly:'$ / hour', annual:'Annual $ allocated' };
const BRB_PROVIDERS = { vendor:'Vendor', buyer:'Buyer' };
const BRB_RECOVERY = { recovered:'Recovered', absorbed:'Vendor absorbed', buyer_provided:'Buyer provided', not_applicable:'Not applicable' };
const BRB_BURDEN_METHODS = { pct:'% of employer cost', hourly:'$ / hour' };

// Spec 4.1 / 5.1 print a balancing final row so a rounded column still sums to
// its total. Round every row but the last, then give the last the remainder.
function balancedColumn(values, total, dp = 2){
  const f = 10 ** dp;
  const rounded = values.map(v => Math.round((Number(v) || 0) * f) / f);
  const head = rounded.slice(0, -1);
  if(rounded.length) rounded[rounded.length - 1] = Math.round((total - head.reduce((a, b) => a + b, 0)) * f) / f;
  return rounded;
}

const clone = (o) => JSON.parse(JSON.stringify(o));
let state = clone(BRB_DEFAULTS);
let result = null;

// ── Formatting ───────────────────────────────────────────
const CUR = window.GASQ_CURRENCY || {};
function fmtMoney(v, dp = 2){
  return new Intl.NumberFormat(CUR.locale || 'en-US', { style:'currency', currency: CUR.code || 'USD', minimumFractionDigits: dp, maximumFractionDigits: dp })
    .format((Number(v) || 0) * (CUR.rate || 1));
}
function fmtNum(v, dp = 0){ return new Intl.NumberFormat('en-US', { minimumFractionDigits: dp, maximumFractionDigits: dp }).format(Number(v) || 0); }
function fmtPct(v, dp = 2){ return fmtNum((Number(v) || 0) * 100, dp) + '%'; }
function fmtAs(kind, v){
  switch(kind){
    case 'money': return fmtMoney(v, 2);
    case 'money0': return fmtMoney(v, 0);
    case 'int': return fmtNum(v, 0);
    case 'dec2': return fmtNum(v, 2);
    case 'dec3': return fmtNum(v, 3);
    case 'dec4': return fmtNum(v, 4);
    case 'pct': return fmtPct(v, 2);
    default: return String(v ?? '');
  }
}
function esc(s){ return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function getPath(obj, path){ return path.split('.').reduce((o, k) => (o == null ? undefined : o[k]), obj); }
function setPath(obj, path, value){
  const keys = path.split('.');
  let o = obj;
  keys.slice(0, -1).forEach((k, i) => {
    if(o[k] == null) o[k] = /^\d+$/.test(keys[i + 1]) ? [] : {};
    o = o[k];
  });
  o[keys[keys.length - 1]] = value;
}
function setText(id, v){ const el = document.getElementById(id); if(el) el.textContent = v; }

// ── Input binding (data-path on any input) ──────────────
function readInput(el){
  if(el.type === 'checkbox') return el.checked;
  if(el.dataset.kind === 'text' || el.tagName === 'SELECT' && !el.dataset.kind) return el.value;
  if(el.value.trim() === '') return el.dataset.nullable ? null : 0;
  return parseFloat(el.value);
}
function writeInputs(root = document){
  root.querySelectorAll('[data-path]').forEach(el => {
    const v = getPath(state, el.dataset.path);
    if(el.type === 'checkbox') el.checked = !!v;
    else if(el.type === 'radio') el.checked = String(v) === el.value;
    else el.value = v ?? '';
  });
}
document.addEventListener('input', onEdit);
document.addEventListener('change', onEdit);
function onEdit(e){
  const el = e.target.closest('[data-path]');
  if(!el) return;
  if(el.type === 'radio'){ if(!el.checked) return; setPath(state, el.dataset.path, el.value); }
  else setPath(state, el.dataset.path, readInput(el));
  if(el.dataset.rerender){ renderStructure(); }
  scheduleCompute();
}

// ── Structural renders (rows that users add/remove) ─────
function inputCell(path, opts = {}){
  const kind = opts.kind || 'number';
  const attrs = `data-path="${path}"${opts.nullable ? ' data-nullable="1"' : ''}${opts.rerender ? ' data-rerender="1"' : ''}`;
  if(kind === 'text') return `<input type="text" class="form-control" data-kind="text" ${attrs} placeholder="${esc(opts.placeholder || '')}">`;
  if(kind === 'check') return `<input type="checkbox" class="form-check-input" ${attrs}>`;
  if(kind === 'select'){
    return `<select class="form-select" data-kind="text" ${attrs}>${Object.entries(opts.options).map(([v, l]) => `<option value="${v}">${esc(l)}</option>`).join('')}</select>`;
  }
  return `<input type="number" class="form-control text-end" step="${opts.step || 'any'}" min="0" ${attrs} placeholder="${esc(opts.placeholder || '')}">`;
}

function renderPositions(){
  const tb = document.getElementById('brb_positions_body');
  tb.innerHTML = state.positions.map((p, i) => `
    <tr class="${p.status === 'inactive' ? 'off' : ''}">
      <td style="min-width:210px">${inputCell(`positions.${i}.name`, { kind:'text' })}</td>
      <td>${inputCell(`positions.${i}.type`, { kind:'select', options: BRB_TYPE_LABELS })}</td>
      <td style="width:80px">${inputCell(`positions.${i}.employees`, { step:1 })}</td>
      <td style="width:95px">${inputCell(`positions.${i}.weeklyPaidHours`, { step:1 })}</td>
      <td style="width:95px">${inputCell(`positions.${i}.hourlyWage`, { step:0.01 })}</td>
      <td style="width:90px">${inputCell(`positions.${i}.localityPay`, { step:0.01 })}</td>
      <td style="width:90px">${inputCell(`positions.${i}.healthWelfareCash`, { step:0.01 })}</td>
      <td style="width:90px">${inputCell(`positions.${i}.shiftDifferential`, { step:0.01 })}</td>
      <td style="width:90px">${inputCell(`positions.${i}.positionPremium`, { step:0.01 })}</td>
      <td class="calc" data-pcol="cashWage" data-fmt="money"></td>
      <td class="calc" data-pcol="annualPaidHours" data-fmt="int"></td>
      <td class="calc" data-pcol="annualPayroll" data-fmt="money0"></td>
      <td class="calc" data-pcol="wageShare" data-fmt="pct"></td>
      <td class="calc" data-pcol="positionBillRate" data-fmt="money"></td>
      <td class="calc" data-pcol="protectiveCapacity" data-fmt="int"></td>
      <td class="text-center">${inputCell(`positions.${i}.countsTowardCoverage`, { kind:'check' })}</td>
      <td>${inputCell(`positions.${i}.status`, { kind:'select', options:{ active:'Active', inactive:'Inactive' }, rerender:true })}</td>
      <td style="min-width:140px">${inputCell(`positions.${i}.notes`, { kind:'text', placeholder:'Notes' })}</td>
      <td><button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove="positions" data-index="${i}" title="Remove position"><i class="fa fa-trash"></i></button></td>
    </tr>`).join('');
}

function renderBurdenItems(){
  document.getElementById('brb_burden_body').innerHTML = state.burden.items.map((b, i) => `
    <tr>
      <td>${esc(b.label)}</td>
      <td style="width:160px">${inputCell(`burden.items.${i}.method`, { kind:'select', options: BRB_BURDEN_METHODS })}</td>
      <td style="width:110px">${inputCell(`burden.items.${i}.value`, { step:0.01 })}</td>
      <td class="calc" data-bshare="${i}"></td>
      <td class="calc" data-bcol="${i}"></td>
    </tr>`).join('');
}

function renderWmc(){
  document.getElementById('brb_wmc_body').innerHTML = state.wmcCategories.map((c, i) => `
    <tr>
      <td>${esc(c.label)}</td>
      <td style="width:120px">${inputCell(`wmcCategories.${i}.hours`, { step:1 })}</td>
      <td class="calc" data-wshare="${i}"></td>
      <td class="calc" data-wcol="${i}"></td>
      <td class="small text-gasq-muted">${esc(c.treatment || '')}</td>
    </tr>`).join('');
}

function renderOdc(){
  document.getElementById('brb_odc_body').innerHTML = state.odc.map((o, i) => `
    <tr class="${o.enabled ? '' : 'off'}">
      <td class="text-center">${inputCell(`odc.${i}.enabled`, { kind:'check', rerender:true })}</td>
      <td style="min-width:170px">${o.custom ? inputCell(`odc.${i}.label`, { kind:'text' }) : esc(o.label) + (o.optional ? ' <span class="text-gasq-muted small">(optional)</span>' : '')}</td>
      <td style="width:170px">${inputCell(`odc.${i}.basis`, { kind:'select', options: BRB_ODC_BASES })}</td>
      <td style="width:120px">${inputCell(`odc.${i}.amount`, { step:0.01 })}</td>
      <td style="width:120px">${inputCell(`odc.${i}.provider`, { kind:'select', options: BRB_PROVIDERS, rerender:true })}</td>
      <td style="width:160px">${inputCell(`odc.${i}.recoveryStatus`, { kind:'select', options: BRB_RECOVERY, rerender:true })}</td>
      <td class="calc" data-ocol="annual" data-i="${i}"></td>
      <td class="calc" data-ocol="hourly" data-i="${i}"></td>
      <td>${o.custom ? `<button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove="odc" data-index="${i}"><i class="fa fa-trash"></i></button>` : ''}</td>
    </tr>`).join('');
}

function renderGa(){
  document.getElementById('brb_ga_body').innerHTML = state.ga.map((g, i) => `
    <tr class="${g.enabled ? '' : 'off'}">
      <td class="text-center">${inputCell(`ga.${i}.enabled`, { kind:'check', rerender:true })}</td>
      <td style="min-width:170px">${g.custom ? inputCell(`ga.${i}.label`, { kind:'text' }) : esc(g.label)}</td>
      <td style="width:170px">${inputCell(`ga.${i}.method`, { kind:'select', options: BRB_GA_METHODS })}</td>
      <td style="width:120px">${inputCell(`ga.${i}.value`, { step:0.01 })}</td>
      <td class="calc" data-gcol="hourly" data-i="${i}"></td>
      <td>${g.custom ? `<button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove="ga" data-index="${i}"><i class="fa fa-trash"></i></button>` : ''}</td>
    </tr>`).join('');
}

function ensureForecastYears(){
  const f = state.forecast;
  f.termYears = Math.max(1, Math.min(10, parseInt(f.termYears) || 5));
  const byYear = Object.fromEntries((f.years || []).map(y => [y.year, y]));
  f.years = [];
  for(let y = 2; y <= f.termYears; y++){
    f.years.push(Object.assign({ year: y, wageEscPct: null, rateEscPct: null, priceLock: false, rateOverride: null, annualHours: null }, byYear[y] || {}));
  }
}

function renderForecastInputs(){
  ensureForecastYears();
  const custom = state.forecast.escalationMode === 'custom';
  document.getElementById('brb_years_body').innerHTML = state.forecast.years.map((y, i) => `
    <tr>
      <td class="fw-semibold">Year ${y.year}</td>
      <td style="width:105px">${custom ? inputCell(`forecast.years.${i}.wageEscPct`, { step:0.01, nullable:true, placeholder: state.forecast.wageEscPct }) : `<span class="text-gasq-muted">${fmtNum(state.forecast.wageEscPct, 2)}%</span>`}</td>
      <td style="width:105px">${custom ? inputCell(`forecast.years.${i}.rateEscPct`, { step:0.01, nullable:true, placeholder: state.forecast.rateEscPct }) : `<span class="text-gasq-muted">${fmtNum(state.forecast.rateEscPct, 2)}%</span>`}</td>
      <td class="text-center">${inputCell(`forecast.years.${i}.priceLock`, { kind:'check' })}</td>
      <td style="width:120px">${inputCell(`forecast.years.${i}.annualHours`, { step:1, nullable:true, placeholder:'Same as Y1' })}</td>
      <td style="width:120px">${inputCell(`forecast.years.${i}.rateOverride`, { step:0.01, nullable:true, placeholder:'Calculated' })}</td>
      <td class="text-nowrap" data-ystatus="${y.year}"></td>
    </tr>`).join('');
}

function renderStructure(){
  renderPositions(); renderBurdenItems(); renderWmc(); renderOdc(); renderGa(); renderForecastInputs();
  writeInputs();
  applyVisibility();
  if(result) renderResults();
}

// Toggle sections whose visibility depends on inputs.
function applyVisibility(){
  const schedule = state.coverage.mode === 'schedule';
  document.getElementById('brb_schedule_fields').hidden = !schedule;
  const lineItems = state.burden.method === 'detailed';
  document.getElementById('brb_burden_ratio').hidden = lineItems;
  document.getElementById('brb_burden_items').hidden = !lineItems;
  const approved = state.pricingMode !== 'buildup';
  document.getElementById('brb_rate_override').hidden = !approved;
  document.querySelectorAll('#brb_mode button').forEach(b => {
    const on = b.dataset.mode === (approved ? 'approved' : 'buildup');
    b.classList.toggle('active', on);
    b.setAttribute('aria-checked', on ? 'true' : 'false');
  });
  const modeLabel = approved ? 'Approved Final Rate / Allocation' : 'Full Cost Build-Up';
  setText('brb_mode_label', modeLabel);
  setText('brb_mode_note', approved
    ? 'Layers 4-6 are embedded in the Final Bill Rate — analysis only, not added again.'
    : 'Layers 4-6 are additive — each one raises the Final Bill Rate.');
  document.querySelectorAll('[data-treatment]').forEach(b => {
    b.textContent = approved ? 'Embedded' : 'Additive';
    b.className = 'brb-badge ' + (approved ? 'embedded' : 'additive');
  });
  document.querySelectorAll('[data-mode-only]').forEach(el => { el.hidden = el.dataset.modeOnly !== (approved ? 'approved' : 'buildup'); });
}

// ── Actions: mode switch, add/remove rows, expand, reset ─
document.getElementById('brb_mode').addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-mode]');
  if(!btn || btn.dataset.mode === state.pricingMode) return;
  const to = btn.dataset.mode === 'buildup' ? 'Full Cost Build-Up' : 'Approved Final Rate / Allocation';
  if(!confirm(`Switch pricing mode to ${to}? Every dependent output will be recalculated.`)) return;
  state.pricingMode = btn.dataset.mode;
  applyVisibility();
  scheduleCompute(0);
});

document.addEventListener('click', (e) => {
  const rm = e.target.closest('[data-remove]');
  if(rm){
    state[rm.dataset.remove].splice(parseInt(rm.dataset.index), 1);
    renderStructure(); scheduleCompute();
    return;
  }
  const add = e.target.closest('[data-add-position]');
  if(add){
    const tpl = BRB_POSITION_CATALOGUE[parseInt(add.dataset.addPosition)];
    state.positions.push({ name: tpl.type === 'custom' ? '' : tpl.name, type: tpl.type, employees: 1, weeklyPaidHours: 40, hourlyWage: 0,
      countsTowardCoverage: tpl.type !== 'support', status: 'active', notes: '' });
    renderStructure(); scheduleCompute();
    return;
  }
  const addLine = e.target.closest('[data-add-line]');
  if(addLine){
    const list = addLine.dataset.addLine;
    const key = 'custom_' + Date.now();
    state[list].push(list === 'odc'
      ? { key, label: 'Custom direct cost', custom: true, optional: true, enabled: true, basis: 'hour', amount: 0 }
      : { key, label: 'Custom G&A item', custom: true, enabled: true, method: 'pct', value: 0 });
    renderStructure(); scheduleCompute();
    return;
  }
  const exp = e.target.closest('[data-expand]');
  if(exp){
    // Bootstrap is bundled without a global, so drive the headers' own toggles.
    const open = exp.dataset.expand === '1';
    document.querySelectorAll('#brb_layers .accordion-button').forEach(btn => {
      if(btn.classList.contains('collapsed') === open) btn.click();
    });
    return;
  }
  const reset = e.target.closest('[data-reset-year]');
  if(reset){
    const row = state.forecast.years.find(y => y.year === parseInt(reset.dataset.resetYear));
    if(row){ row.rateOverride = null; renderStructure(); scheduleCompute(); }
  }
});

document.getElementById('brb_reset').addEventListener('click', () => {
  if(!confirm('Reset every input to the approved GASQ baseline?')) return;
  state = clone(BRB_DEFAULTS);
  renderStructure(); scheduleCompute(0);
});

// ── Compute (server engine) ─────────────────────────────
let timer = null, inflight = null;
function scheduleCompute(delay = 600){ clearTimeout(timer); timer = setTimeout(runCompute, delay); }

function setError(msg){
  const el = document.getElementById('br_error');
  if(!msg){ el.style.display = 'none'; el.textContent = ''; return; }
  el.style.display = ''; el.textContent = msg;
}

async function runCompute(){
  try{
    setError('');
    if(inflight) inflight.abort();
    inflight = new AbortController();
    const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'bill-rate-analysis']) }}', {
      method: 'POST',
      signal: inflight.signal,
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json' },
      body: JSON.stringify({ version: 'v24', scenario: { meta: { brb: state } } }),
    });
    let data = null;
    try { data = await res.json(); } catch { data = null; }
    if(!res.ok || !data || !data.ok){
      setError(data && data.error === 'insufficient_credits'
        ? (data.message || 'Not enough credits to run this calculator.')
        : 'Unable to calculate right now. Please try again.');
      return;
    }
    result = data.kpis || {};
    renderResults();
  }catch(e){
    if(e?.name === 'AbortError') return;
    console.error(e);
    setError('Unable to calculate right now. Please try again.');
  }
}

// ── Result render ───────────────────────────────────────
function renderResults(){
  const r = result;
  document.querySelectorAll('[data-out]').forEach(el => { el.textContent = fmtAs(el.dataset.fmt, getPath(r, el.dataset.out)); });

  // Layer headers
  const m = (v) => fmtMoney(v) + '/hr';
  setText('brb_l1_head', m(r.layer1.weightedWage));
  setText('brb_l2_head', m(r.layer2.fullBurden));
  setText('brb_l3_head', m(r.layer3.finalBillRate));
  setText('brb_l4_head', m(r.layer4.hourly));
  setText('brb_l5_head', m(r.layer5.hourly));
  setText('brb_l6_head', m(r.layer6.profitHourly));

  // Position calc columns
  const rows = document.querySelectorAll('#brb_positions_body tr');
  let active = 0;
  state.positions.forEach((p, i) => {
    const tr = rows[i]; if(!tr) return;
    const calc = p.status === 'inactive' ? null : r.layer1.positions[active++];
    tr.querySelectorAll('[data-pcol]').forEach(td => { td.textContent = calc ? fmtAs(td.dataset.fmt, calc[td.dataset.pcol]) : '—'; });
  });

  // Layer 2: share and hourly columns, with the spec's balancing final row.
  const bLines = r.layer2.lines || [];
  const bHourly = balancedColumn(bLines.map(l => l.hourly), r.layer2.incrementalBurden);
  const bShare = balancedColumn(bLines.map(l => l.share * 100), r.layer2.burdenShare * 100);
  document.querySelectorAll('[data-bcol]').forEach(td => {
    const i = +td.dataset.bcol;
    td.textContent = bLines[i] ? fmtMoney(bHourly[i]) : '—';
  });
  document.querySelectorAll('[data-bshare]').forEach(td => {
    const i = +td.dataset.bshare;
    td.textContent = bLines[i] ? fmtNum(bShare[i], 2) + '%' : '—';
  });
  setText('brb_l2_total_share', fmtNum(r.layer2.burdenShare * 100, 2) + '%');
  setText('brb_l2_total_hourly', fmtMoney(r.layer2.incrementalBurden));
  const l2Badge = document.getElementById('brb_l2_badge');
  if(r.layer2.method === 'detailed'){
    l2Badge.hidden = false;
    l2Badge.textContent = r.layer2.reconciles ? 'Reconciled' : 'Does not reconcile';
    l2Badge.className = 'brb-badge ' + (r.layer2.reconciles ? 'calc' : 'over');
  } else {
    l2Badge.hidden = true;
  }

  // Layer 3 categories: share and rate allocation, balanced the same way.
  const wCats = r.layer3.categories || [];
  const wHourly = balancedColumn(wCats.map(c => c.hourly), r.layer3.rateAllocation);
  const wShare = balancedColumn(wCats.map(c => c.share * 100), wCats.length ? 100 : 0);
  document.querySelectorAll('[data-wcol]').forEach(td => {
    const i = +td.dataset.wcol;
    td.textContent = wCats[i] ? fmtMoney(wHourly[i]) : '—';
  });
  document.querySelectorAll('[data-wshare]').forEach(td => {
    const i = +td.dataset.wshare;
    td.textContent = wCats[i] ? fmtNum(wShare[i], 2) + '%' : '—';
  });
  setText('brb_l3_total_alloc', fmtMoney(r.layer3.rateAllocation));

  // Layer 4: an absorbed or buyer-provided cost stays visible but adds nothing.
  const odcByKey = Object.fromEntries((r.layer4.lines || []).map(l => [l.key, l]));
  document.querySelectorAll('[data-ocol]').forEach(td => {
    const line = odcByKey[state.odc[td.dataset.i]?.key];
    if(!line){ td.textContent = '—'; return; }
    if(td.dataset.ocol === 'annual'){ td.textContent = fmtMoney(line.annual, 0); return; }
    td.innerHTML = line.recoverable
      ? fmtMoney(line.hourly) + '/hr'
      : `<span class="text-gasq-muted">${fmtMoney(line.hourly)} · not recovered</span>`;
  });
  const gaByKey = Object.fromEntries((r.layer5.lines || []).map(l => [l.key, l]));
  document.querySelectorAll('[data-gcol]').forEach(td => {
    const line = gaByKey[state.ga[td.dataset.i]?.key];
    td.textContent = line ? fmtMoney(line.hourly) + '/hr' : '—';
  });

  // Layer 3 override status
  setText('brb_wmc_status', r.layer3.wmcOverridden ? 'Authorised override' : 'Calculated');
  const wmcBadge = document.getElementById('brb_wmc_badge');
  wmcBadge.textContent = r.layer3.categoriesReconcile ? 'Reconciled to ' + fmtNum(r.layer3.wmcHours) + ' hrs' : 'Does not reconcile';
  wmcBadge.className = 'brb-badge ' + (r.layer3.categoriesReconcile ? 'calc' : 'over');

  // Summary: CRO vs premium
  const premium = r.summary.premiumAboveCtp > 0;
  document.querySelectorAll('[data-show="cro"]').forEach(el => el.hidden = premium);
  document.querySelectorAll('[data-show="premium"]').forEach(el => el.hidden = !premium);
  setText('brb_rate_status', r.pricingMode === 'buildup'
    ? 'Full cost build-up · all layers additive'
    : (r.reconciliation.rateOverridden ? 'Approved rate · authorised override' : 'Approved baseline · calculated in Layer 3'));

  // Reconciliation
  const approved = r.pricingMode !== 'buildup';
  const rem = r.reconciliation.remaining;
  const over = approved && rem < -0.000001;
  setText('brb_recon_labor_label', approved ? 'Employer full-burden labor (L1-2)' : 'Labor subtotal (L1-3)');
  setText('brb_recon_total_label', approved ? 'Embedded Allocation Total' : 'Built-up Final Bill Rate');
  setText('brb_recon_remaining_label', over ? 'Over-Allocated' : 'Remaining (unallocated)');
  document.getElementById('brb_recon_remaining_row').hidden = !approved;
  const remEl = document.getElementById('brb_recon_remaining');
  remEl.textContent = fmtMoney(Math.abs(rem));
  remEl.style.color = over ? '#c2410c' : '';
  const badge = document.getElementById('brb_recon_badge');
  badge.textContent = over ? 'Over-allocated' : (approved ? 'Embedded' : 'Additive');
  badge.className = 'brb-badge ' + (over ? 'over' : (approved ? 'embedded' : 'additive'));
  setText('brb_recon_note', approved
    ? 'Layers 4-6 are carved out of the approved rate for analysis. They are never added on top of it.'
    : 'Labor subtotal (Layer 3 rate) + ODC + G&A = pre-profit cost, grossed up by the profit margin.');

  renderForecastResults(r.forecast);
  renderValidations(r.validations || []);
  renderFormulas(r);
}

function renderForecastResults(f){
  document.getElementById('brb_forecast_body').innerHTML = f.years.map(y => `
    <tr>
      <td class="fw-semibold">Year ${y.year}${y.priceLock ? ' <span class="brb-badge embedded">Locked</span>' : ''}</td>
      <td class="calc">${fmtMoney(y.wage)}</td>
      <td class="calc">${fmtMoney(y.fullBurden)}</td>
      <td class="calc fw-bold">${fmtMoney(y.finalBillRate)}</td>
      <td class="calc">${fmtNum(y.annualHours)}</td>
      <td class="calc">${fmtMoney(y.annualContractValue, 0)}</td>
      <td class="calc">${fmtMoney(y.costToProtect)}</td>
      <td class="calc">${y.croHourly > 0 ? fmtMoney(y.croHourly) : '<span class="text-warning">+' + fmtMoney(y.premiumAboveCtp) + ' premium</span>'}</td>
      <td class="calc">${fmtMoney(y.annualCro, 0)}</td>
    </tr>`).join('');
  const t = f.totals;
  document.getElementById('brb_forecast_foot').innerHTML = `
    <tr><td>${f.termYears}-Year Total</td><td></td><td></td><td class="calc">${fmtMoney(t.averageBillRate)} avg</td>
    <td class="calc">${fmtNum(t.protectiveHours)}</td><td class="calc">${fmtMoney(t.contractValue, 0)}</td><td></td><td></td>
    <td class="calc">${fmtMoney(t.annualCro, 0)}</td></tr>`;

  document.getElementById('brb_staffing_body').innerHTML = f.years.map(y => `
    <tr>
      <td class="fw-semibold">Year ${y.year}</td>
      <td class="calc">${fmtNum(y.staffing.annualHours)}</td>
      <td class="calc">${fmtNum(y.staffing.equivalentPosts, 3)}</td>
      <td class="calc">${fmtNum(y.staffing.requiredFte, 2)}</td>
      <td class="calc">${fmtNum(y.staffing.roundedHeadcount)}</td>
      <td class="calc">${fmtNum(y.staffing.availableCapacity)}</td>
      <td class="calc">${fmtNum(y.staffing.staffingReserveCapacity)}</td>
    </tr>`).join('');

  f.years.forEach(y => {
    const cell = document.querySelector(`[data-ystatus="${y.year}"]`);
    if(!cell) return;
    cell.innerHTML = y.status === 'overridden'
      ? `<span class="brb-badge over">Overridden</span> <button type="button" class="btn btn-link btn-sm p-0 ms-1" data-reset-year="${y.year}">Reset to forecast</button>`
      : `<span class="brb-badge calc">Calculated</span>`;
  });

  const last = f.years[f.years.length - 1];
  setText('brb_f_contract', fmtMoney(t.contractValue, 0));
  setText('brb_f_cro', fmtMoney(t.annualCro, 0));
  setText('brb_f_avg', fmtMoney(t.averageBillRate));
  setText('brb_f_hours', fmtNum(t.protectiveHours));
  setText('brb_f_headcount', fmtNum(last.staffing.roundedHeadcount));
  setText('brb_f_reserve', fmtNum(last.staffing.staffingReserveCapacity) + ' hrs');
  setText('brb_f_wage', fmtPct(t.wageGrowth));
  setText('brb_f_rate', fmtPct(t.rateGrowth));
  setText('brb_f_profit', fmtMoney(t.embeddedProfit, 0));
  setText('brb_f_term', f.termYears);
}

function renderValidations(list){
  const box = document.getElementById('brb_warnings');
  box.hidden = list.length === 0;
  const blocking = list.filter(m => m.blocking).length;
  box.innerHTML = (blocking
      ? `<div class="item error fw-semibold"><i class="fa fa-circle-exclamation mt-1"></i><span>${blocking} issue${blocking > 1 ? 's' : ''} ${blocking > 1 ? 'block' : 'blocks'} approval.</span></div>`
      : '')
    + list.map(m => `<div class="item ${m.severity}">
        <i class="fa ${m.severity === 'error' ? 'fa-circle-exclamation' : 'fa-triangle-exclamation'} mt-1"></i>
        <span><span class="brb-code">${esc(m.code)}</span> ${esc(m.message)}</span>
      </div>`).join('');
}

// "How calculated" drawers, filled with the live numbers.
function renderFormulas(r){
  const s = r.staffing, l1 = r.layer1, l2 = r.layer2, l3 = r.layer3, l6 = r.layer6, sm = r.summary;
  const n = (v, dp = 0) => fmtNum(v, dp), $ = (v) => fmtMoney(v);
  const f = {
    l1: [
      `Available protective hours/week = ${n(s.availableHoursPerEmployee)} / ${n(r.coverage.weeksPerYear)} = ${n(s.availablePerWeek, 2)}`,
      `Manpower per 24/7 post = 168 / ${n(s.availablePerWeek, 2)} = ${n(s.manpowerPerPost, 2)}`,
      `Equivalent 24/7 posts = ${n(s.annualHours)} / ${n(168 * r.coverage.weeksPerYear)} = ${n(s.equivalentPosts, 3)}`,
      `Required headcount = ceiling(${n(s.annualHours)} / ${n(s.availableHoursPerEmployee)} = ${n(s.requiredFte, 4)}) = ${n(s.roundedHeadcount)}`,
      `Staffing Reserve Capacity = (${n(s.roundedHeadcount)} × ${n(s.availableHoursPerEmployee)}) − ${n(s.annualHours)} = ${n(s.staffingReserveCapacity)}`,
      `Weighted wage = ${$(l1.totalPayroll)} / ${n(l1.totalAnnualPaidHours)} = ${$(l1.weightedWage)} (total cash wage)`,
      `Position bill rate = position cash wage × (${$(sm.finalBillRate)} / ${$(l1.weightedWage)}) = cash wage × ${n(l1.rateMultiplier, 9)}`,
    ],
    l2: l2.method === 'ratio'
      ? [
          `Employer full-burden cost = ${$(l1.weightedWage)} / ${fmtNum(l2.wageShare, 4)} = ${$(l2.fullBurden)}`,
          `Incremental employer burden = ${$(l2.fullBurden)} − ${$(l1.weightedWage)} = ${$(l2.incrementalBurden)}`,
          `Line items are shares of the employer cost and total ${fmtPct(l2.burdenShare)}.`,
        ]
      : [
          `Employer full-burden cost = ${$(l1.weightedWage)} / (1 − ${fmtNum(l2.burdenShare, 4)} line-item share) = ${$(l2.fullBurden)}`,
          `Line items total ${fmtPct(l2.burdenShare)} against a configured burden share of ${fmtPct(l2.configuredBurdenShare)}.`,
          `Incremental employer burden = ${$(l2.incrementalBurden)}`,
        ],
    l3: [
      `WMC hours/employee = ${n(s.paidHoursPerEmployee)} − ${n(s.availableHoursPerEmployee)} = ${n(l3.wmcHoursCalculated)}` + (l3.wmcOverridden ? ` (override: ${n(l3.wmcHours, 2)})` : ''),
      `WMC hours/post = ${n(l3.wmcHours, 2)} × ${n(s.manpowerPerPost, 2)} = ${n(l3.wmcPerPost, 2)}`,
      `WMC value = ${$(l2.fullBurden)} × ${n(l3.wmcPerPost, 2)} = ${fmtMoney(l3.wmcValue)}`,
      `Final Bill Rate (baseline) = ${fmtMoney(l3.wmcValue)} / ${n(s.paidHoursPerEmployee)} = ${$(l3.finalBillRate)}`,
      `Maintenance share of the rate = ${$(l3.finalBillRate)} − ${$(l2.fullBurden)} = ${$(l3.rateAllocation)}, split across the categories by hours`,
    ],
    l4: [`Only a recovered vendor item raises the rate; absorbed and buyer-provided items show but contribute $0.`,
      `Each line → annual cost → ÷ ${n(s.annualHours)} annual hours = $/hr`, `$/employee × ${n(s.roundedHeadcount)} headcount · $/post × ${n(s.equivalentPosts, 3)} posts · $/month × 12 · contract ÷ ${n(r.forecast.termYears)} yrs`, `Layer 4 total = ${$(r.layer4.hourly)}/hr`],
    l5: [`% items apply to the subtotal ${$(r.layer5.base)} (labor base + ODC)`, `Annual items ÷ ${n(s.annualHours)} hours`, `Layer 5 total = ${$(r.layer5.hourly)}/hr`],
    l6: r.pricingMode === 'buildup'
      ? [`Final price = ${$(l6.preProfit)} / (1 − ${fmtPct(l6.marginPct)}) = ${$(sm.finalBillRate)}`, `Profit = ${$(sm.finalBillRate)} − ${$(l6.preProfit)} = ${$(l6.profitHourly)}`, `Check: ${$(l6.profitHourly)} / ${$(sm.finalBillRate)} = ${fmtPct(sm.finalBillRate > 0 ? l6.profitHourly / sm.finalBillRate : 0)}`]
      : [`Max pre-profit subtotal = ${$(sm.finalBillRate)} × ${n(l6.marginDivisor, 4)} = ${fmtNum(l6.preProfit, 3)}`, `Embedded profit = ${$(sm.finalBillRate)} × ${fmtPct(l6.marginPct)} = ${fmtNum(l6.profitHourly, 3)}`, `Markup on cost = ${fmtPct(l6.markup)}. An allocation check, not an extra charge.`],
    ctp: [
      `Cost to Protect = ${fmtMoney(l3.wmcValue)} / ${n(s.availableHoursPerEmployee)} = ${fmtNum(sm.costToProtect, 6)}`,
      sm.croHourly > 0
        ? `Capital Recovery = ${fmtNum(sm.costToProtect, 6)} − ${$(sm.finalBillRate)} = ${fmtNum(sm.croHourly, 6)}/hr × ${n(s.annualHours)} = ${$(sm.annualCro)}`
        : `Final Bill Rate exceeds Cost to Protect: CRO = $0, premium = ${$(sm.premiumAboveCtp)}/hr`,
    ],
  };
  Object.entries(f).forEach(([k, lines]) => {
    const el = document.getElementById('brb_how_' + k);
    if(el) el.innerHTML = lines.map(esc).join('<br>');
  });
}

// ── Boot ─────────────────────────────────────────────────
function mergeSaved(saved){
  if(!saved || typeof saved !== 'object') return;
  const d = clone(BRB_DEFAULTS);
  ['coverage','workforce','finalRateOverride','profit','burden','forecast'].forEach(k => { d[k] = Object.assign(d[k], saved[k] || {}); });
  if(typeof saved.wmcCategoryOverrideReason === 'string') d.wmcCategoryOverrideReason = saved.wmcCategoryOverrideReason;
  ['positions','wmcCategories','odc','ga'].forEach(k => { if(Array.isArray(saved[k])) d[k] = saved[k]; });
  if(Array.isArray(saved.burden?.items)) d.burden.items = saved.burden.items;
  if(saved.pricingMode) d.pricingMode = saved.pricingMode;
  state = d;
}

document.addEventListener('DOMContentLoaded', () => {
  mergeSaved(window.__gasqCalculatorState?.scenario?.meta?.brb);
  renderStructure();
  runCompute();
});
</script>
@endpush
