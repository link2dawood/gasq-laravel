@extends('layouts.app')
@section('title', 'Bill Rate Analysis')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .bra-shell {
    background:
      radial-gradient(circle at top right, rgba(6, 45, 121, 0.08), transparent 30%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .bra-sidebar {
    background: linear-gradient(180deg, #fbfcff 0%, #f2f5fb 100%);
  }
  .bra-sticky {
    position: sticky;
    top: 1.25rem;
  }
  .bra-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gasq-muted);
  }
  .bra-section + .bra-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .bra-stat {
    border: 1px solid rgba(6, 45, 121, 0.08);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }
  .bra-stat-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gasq-muted);
  }
  .bra-stat-value {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--gasq-primary);
  }
  .bra-panel {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
  }
  .bra-panel-muted {
    background: rgba(6, 45, 121, 0.04);
  }
  .bra-chip {
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
  .bra-mono {
    font-variant-numeric: tabular-nums;
  }
  @media (max-width: 1199.98px) {
    .bra-sticky {
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
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-dollar-sign text-primary"></i> Bill Rate Analysis
        </h1>
        <div class="text-gasq-muted small">Shared input rail with live results across all bill rate tabs</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
  </div>

  <div class="card gasq-card bra-shell overflow-hidden">
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-xl-4 border-end bra-sidebar">
          <div class="p-3 p-md-4 bra-sticky">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
              <div>
                <div class="bra-kicker mb-2">Shared Inputs</div>
                <h2 class="h4 fw-bold mb-2">Bill Rate Controls</h2>
                <p class="small text-gasq-muted mb-0">The inputs on this side drive both tabs on the right, so quick analysis and component views stay synchronized.</p>
              </div>
              <span class="bra-chip"><i class="fa fa-bolt"></i> Live</span>
            </div>

            <div class="bra-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-calculator text-primary"></i>
                <h5 class="mb-0 fw-semibold">Quick Calculator Inputs</h5>
              </div>
              <div class="d-flex flex-column gap-3">
                <div>
                  <label class="form-label fw-medium">Base Pay Rate ($/hr)</label>
                  <input type="number" id="qbr_base" class="form-control" value="18.00" step="0.01" oninput="scheduleCompute()">
                </div>
                <div>
                  <label class="form-label fw-medium">Payroll Tax &amp; Benefits (%)</label>
                  <input type="number" id="qbr_benefits" class="form-control" value="20" step="0.1" oninput="scheduleCompute()">
                  <div class="form-text">FICA, SUTA, FUTA, workers comp, and related burden.</div>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Overhead (%)</label>
                    <input type="number" id="qbr_overhead" class="form-control" value="35" step="0.1" oninput="scheduleCompute()">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-medium">Profit Margin (%)</label>
                    <input type="number" id="qbr_profit" class="form-control" value="15" step="0.1" oninput="scheduleCompute()">
                  </div>
                </div>
              </div>
            </div>

            <div class="bra-section">
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fa fa-chart-pie text-primary"></i>
                <h5 class="mb-0 fw-semibold">Component Builder Inputs</h5>
              </div>
              @php
              $components = [
                ['id'=>'bc_wages','label'=>'Wages & Benefits','default'=>41.05,'color'=>'#3b82f6'],
                ['id'=>'bc_taxes','label'=>'Taxes & Insurance','default'=>10.96,'color'=>'#84cc16'],
                ['id'=>'bc_training','label'=>'Training Costs','default'=>2.02,'color'=>'#ef4444'],
                ['id'=>'bc_recruiting','label'=>'Recruiting, Screening & Drug Testing','default'=>0.09,'color'=>'#8b5cf6'],
                ['id'=>'bc_uniforms','label'=>'Uniforms & Equipment','default'=>1.47,'color'=>'#06b6d4'],
                ['id'=>'bc_overhead','label'=>'Overhead','default'=>0.50,'color'=>'#f97316'],
                ['id'=>'bc_profit','label'=>'Profit','default'=>3.07,'color'=>'#a855f7'],
              ];
              @endphp
              <div class="d-flex flex-column gap-2">
                @foreach($components as $c)
                <div class="d-flex align-items-center gap-3 p-2 rounded" style="background:rgba(6,45,121,0.04)">
                  <span class="rounded-circle flex-shrink-0" style="width:12px;height:12px;background:{{ $c['color'] }}"></span>
                  <span class="small text-gasq-muted flex-grow-1">{{ $c['label'] }}</span>
                  <input type="number" id="{{ $c['id'] }}" class="form-control form-control-sm text-end py-1" style="max-width:130px" value="{{ $c['default'] }}" step="0.01" oninput="scheduleCompute()">
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-8">
          <div class="p-3 p-md-4">
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="bra-stat">
                  <div class="bra-stat-label mb-2">Quick Bill Rate</div>
                  <div class="bra-stat-value bra-mono" id="qr_billRate_top">$0.00</div>
                  <div class="small text-gasq-muted">Base pay, benefits, overhead, profit</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="bra-stat">
                  <div class="bra-stat-label mb-2">Weekly at 40 Hours</div>
                  <div class="bra-stat-value bra-mono" id="qr_weekly_top">$0.00</div>
                  <div class="small text-gasq-muted">Live from quick calculator inputs</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="bra-stat">
                  <div class="bra-stat-label mb-2">Component Total</div>
                  <div class="bra-stat-value bra-mono" id="bcc_total_top">$0.00</div>
                  <div class="small text-gasq-muted">All component inputs combined</div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <div class="bra-kicker mb-1">Results Workspace</div>
                <h3 class="h5 fw-bold mb-0">Bill Rate Outputs</h3>
              </div>
              <div class="small text-gasq-muted">Both tabs below update from the shared input rail on the left.</div>
            </div>

            <div class="gasq-tabs-scroll mb-3 d-print-none">
              <ul class="gasq-tabs-pill mb-0" role="tablist" id="brTabs">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#br-basic"><i class="fa fa-calculator me-1"></i> Quick Calculator</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#br-components"><i class="fa fa-chart-pie me-1"></i> Component Builder</a></li>
              </ul>
            </div>

            <div class="tab-content">
              <div class="alert alert-light border gasq-border small d-print-none mb-3" id="br_error" style="display:none"></div>

              <div class="tab-pane fade show active" id="br-basic">
                <div class="row g-3">
                  <div class="col-lg-6">
                    <div class="bra-panel p-3 h-100">
                      <h5 class="fw-semibold mb-3">Bill Rate Build-Up</h5>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Base pay rate</span><span class="fw-medium bra-mono" id="qr_base">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Payroll taxes &amp; benefits</span><span class="fw-medium bra-mono" id="qr_benefitsAmt">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Fully burdened cost</span><span class="fw-medium bra-mono" id="qr_burdened">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Overhead amount</span><span class="fw-medium bra-mono" id="qr_overheadAmt">$0.00/hr</span></div>
                      <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Cost with overhead</span><span class="fw-medium bra-mono" id="qr_withOverhead">$0.00/hr</span></div>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="bra-panel bra-panel-muted p-3 h-100">
                      <h5 class="fw-semibold mb-3">Quick Analysis</h5>
                      <div class="rounded-4 p-4 text-white text-center mb-3" style="background:var(--gasq-primary)">
                        <div class="small mb-1" style="opacity:.85">Final Bill Rate</div>
                        <div class="display-5 fw-bold bra-mono" id="qr_billRate">$0.00</div>
                        <div class="small mt-2" style="opacity:.75">Markup: <span id="qr_markup">0.0%</span></div>
                      </div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Weekly at 40 hours</span><span class="fw-medium bra-mono" id="qr_weekly">$0.00</span></div>
                      <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Burdened cost recheck</span><span class="fw-medium bra-mono" id="qr_burdened2">$0.00/hr</span></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="br-components">
                <div class="row g-3">
                  <div class="col-lg-5">
                    <div class="bra-panel bra-panel-muted p-3 h-100">
                      <h5 class="fw-semibold mb-3">Component Summary</h5>
                      <div class="rounded-4 p-4 text-white text-center mb-3" style="background:var(--gasq-primary)">
                        <div class="small mb-1" style="opacity:.85">Total Bill Rate</div>
                        <div class="display-5 fw-bold bra-mono" id="bcc_total">$0.00</div>
                        <div class="small mt-1" style="opacity:.7">per hour</div>
                      </div>
                      <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted small">Largest component</span><span class="fw-medium bra-mono" id="bcc_largest">—</span></div>
                      <div class="d-flex justify-content-between"><span class="text-gasq-muted small">Quick calculator bill rate</span><span class="fw-medium bra-mono" id="bcc_quickMirror">$0.00</span></div>
                    </div>
                  </div>
                  <div class="col-lg-7">
                    <div class="bra-panel p-3 h-100">
                      <h5 class="fw-semibold mb-3">Breakdown</h5>
                      <div id="bcc_breakdown" class="d-flex flex-column gap-2"></div>
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

  <x-report-actions reportType="bill-rate-analysis" />

</div>
</div>
@endsection

@push('scripts')
<script>
const savedScenario = window.__gasqCalculatorState?.scenario || null;
const COMP_COLORS = ['#3b82f6','#84cc16','#ef4444','#8b5cf6','#06b6d4','#f97316','#a855f7'];

function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v || 0);}
function g(id){return parseFloat(document.getElementById(id)?.value)||0;}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

function setError(msg){
  const el = document.getElementById('br_error');
  if(!el) return;
  if(!msg){ el.style.display='none'; el.textContent=''; return; }
  el.style.display='';
  el.textContent = msg;
}

let t = null;
let inflight = null;
function scheduleCompute(){ clearTimeout(t); t = setTimeout(runCompute, 250); }

function buildPayload(){
  return {
    version:'v24',
    scenario:{ meta:{
      quick:{ basePayRate:g('qbr_base'), benefitsPct:g('qbr_benefits'), overheadPct:g('qbr_overhead'), profitPct:g('qbr_profit') },
      components:{
        wages:g('bc_wages'), taxes:g('bc_taxes'), training:g('bc_training'), recruiting:g('bc_recruiting'),
        uniforms:g('bc_uniforms'), overhead:g('bc_overhead'), profit:g('bc_profit')
      }
    } }
  };
}

function hydrateSavedBillRate(){
  const meta = savedScenario?.meta || {};
  const quick = meta.quick || {};
  const components = meta.components || {};

  const map = {
    qbr_base: quick.basePayRate,
    qbr_benefits: quick.benefitsPct,
    qbr_overhead: quick.overheadPct,
    qbr_profit: quick.profitPct,
    bc_wages: components.wages,
    bc_taxes: components.taxes,
    bc_training: components.training,
    bc_recruiting: components.recruiting,
    bc_uniforms: components.uniforms,
    bc_overhead: components.overhead,
    bc_profit: components.profit,
  };

  Object.entries(map).forEach(([id, value]) => {
    if(value === undefined || value === null) return;
    const el = document.getElementById(id);
    if(el) el.value = value;
  });
}

async function runCompute(){
  try{
    setError('');
    if(inflight){ inflight.abort(); }
    inflight = new AbortController();
    const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'bill-rate-analysis']) }}', {
      method:'POST',
      signal: inflight.signal,
      headers:{
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept':'application/json'
      },
      body: JSON.stringify(buildPayload())
    });

    let data = null;
    try { data = await res.json(); } catch { data = null; }
    if(!res.ok || !data || !data.ok){
      if(data && data.error === 'insufficient_credits'){
        setError(data.message || 'Not enough credits to run this calculator.');
      } else {
        setError('Unable to calculate right now. Please try again.');
      }
      console.error(data);
      return;
    }

    const quick = (data.kpis||{}).quick||{};
    setText('qr_base', fmt(quick.basePayRate||0)+'/hr');
    setText('qr_benefitsAmt', '+'+fmt(quick.benefitsAmt||0)+'/hr');
    setText('qr_burdened', fmt(quick.burdenedCost||0)+'/hr');
    setText('qr_burdened2', fmt(quick.burdenedCost||0)+'/hr');
    setText('qr_overheadAmt', '+'+fmt(quick.overheadAmt||0)+'/hr');
    setText('qr_withOverhead', fmt(quick.withOverhead||0)+'/hr');
    setText('qr_billRate', fmt(quick.billRate||0));
    setText('qr_billRate_top', fmt(quick.billRate||0));
    setText('qr_markup', (quick.markupPct||0).toFixed(1)+'%');
    setText('qr_weekly', fmt(quick.weeklyAt40||0));
    setText('qr_weekly_top', fmt(quick.weeklyAt40||0));

    const comp = (data.kpis||{}).components||{};
    setText('bcc_total', fmt(comp.totalBillRate||0));
    setText('bcc_total_top', fmt(comp.totalBillRate||0));
    setText('bcc_quickMirror', fmt(quick.billRate||0));

    const rows = (comp.rows||[]).filter(c=>(c.value||0)>0);
    const largest = rows.reduce((best, row) => ((row.value||0) > (best.value||0) ? row : best), { value: 0, label: '—' });
    setText('bcc_largest', largest.label === '—' ? '—' : `${largest.label} (${fmt(largest.value || 0)})`);

    const bd = document.getElementById('bcc_breakdown');
    if(bd){
      bd.innerHTML = rows.map((c,i)=>{
        const pct = c.pct||0;
        const color = COMP_COLORS[i%COMP_COLORS.length];
        return `<div>
          <div class="d-flex justify-content-between small mb-1">
            <div class="d-flex align-items-center gap-2">
              <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:${color}"></span>
              <span class="text-gasq-muted">${c.label}</span>
            </div>
            <span class="fw-medium">${fmt(c.value||0)} (${pct.toFixed(1)}%)</span>
          </div>
          <div class="progress mb-1" style="height:6px">
            <div class="progress-bar" style="width:${pct.toFixed(1)}%;background:${color}"></div>
          </div>
        </div>`;
      }).join('');
    }
  }catch(e){
    if(e?.name === 'AbortError') return;
    console.error(e);
    setError('Unable to calculate right now. Please try again.');
  }
}

document.addEventListener('DOMContentLoaded', ()=>{
  hydrateSavedBillRate();
  runCompute();
});
</script>
@endpush
