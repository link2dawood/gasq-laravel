@extends('layouts.app')
@section('title', 'Bill Rate Analysis')
@section('header_variant', 'dashboard')

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
        <div class="text-gasq-muted small">Analyze and build security service bill rates from components</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
  </div>

  {{-- Tabs + body: Bootstrap 5 expects .tab-pane as direct children of .tab-content --}}
  <div class="card gasq-card">
    <div class="card-header px-3 px-md-4 pt-3 pb-0 d-print-none" style="background:transparent;border-bottom:none">
      <div class="gasq-tabs-scroll">
        <ul class="gasq-tabs-pill mb-0" role="tablist" id="brTabs">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#br-basic"><i class="fa fa-calculator me-1"></i> Quick Calculator</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#br-components"><i class="fa fa-chart-pie me-1"></i> Component Builder</a></li>
        </ul>
      </div>
    </div>

    <div class="card-body p-4">
      <div class="tab-content">

      <div class="alert alert-light border gasq-border small d-print-none mb-3" id="br_error" style="display:none"></div>

      {{-- ===== QUICK CALCULATOR ===== --}}
      <div class="tab-pane fade show active" id="br-basic">
        <div class="row g-4">
          <div class="col-lg-5">
            <h5 class="fw-semibold mb-3">Inputs</h5>
            <div class="mb-3">
              <label class="form-label fw-medium">Base Pay Rate ($/hr)</label>
              <div class="input-group"><span class="input-group-text">$</span><input type="number" id="qbr_base" class="form-control" value="18.00" step="0.01" oninput="scheduleCompute()"></div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Payroll Tax &amp; Benefits (%)</label>
              <input type="number" id="qbr_benefits" class="form-control" value="20" step="0.1" oninput="scheduleCompute()">
              <div class="form-text">FICA, SUTA, FUTA, workers comp, etc.</div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Overhead (%)</label>
              <input type="number" id="qbr_overhead" class="form-control" value="35" step="0.1" oninput="scheduleCompute()">
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Profit Margin (%)</label>
              <input type="number" id="qbr_profit" class="form-control" value="15" step="0.1" oninput="scheduleCompute()">
            </div>
          </div>
          <div class="col-lg-7">
            <h5 class="fw-semibold mb-3">Bill Rate Build-up</h5>
            <div class="d-flex flex-column gap-3">
              <div class="rounded p-3" style="background:var(--gasq-muted-bg)">
                <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted">Base pay rate</span><span class="fw-medium" id="qr_base">$0.00/hr</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted">+ Payroll taxes &amp; benefits</span><span class="fw-medium" id="qr_benefitsAmt">$0.00/hr</span></div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fw-semibold"><span>Fully burdened cost</span><span id="qr_burdened">$0.00/hr</span></div>
              </div>
              <div class="rounded p-3" style="background:var(--gasq-muted-bg)">
                <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted">Fully burdened cost</span><span class="fw-medium" id="qr_burdened2">$0.00/hr</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-gasq-muted">+ Overhead</span><span class="fw-medium" id="qr_overheadAmt">$0.00/hr</span></div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fw-semibold"><span>Cost with overhead</span><span id="qr_withOverhead">$0.00/hr</span></div>
              </div>
              <div class="rounded p-4 text-white text-center" style="background:var(--gasq-primary)">
                <div class="small mb-1" style="opacity:.85">Final Bill Rate</div>
                <div class="display-5 fw-bold" id="qr_billRate">$0.00</div>
                <div class="small mt-2" style="opacity:.75">Markup: <span id="qr_markup">0.0%</span> | Weekly (40hr): <span id="qr_weekly">$0.00</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== COMPONENT BUILDER ===== --}}
      <div class="tab-pane fade" id="br-components">
        <div class="row g-4">
          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3">Component Inputs ($/hr)</h5>
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
              <div class="d-flex align-items-center gap-3 p-2 rounded" style="background:var(--gasq-muted-bg)">
                <span class="rounded-circle flex-shrink-0" style="width:12px;height:12px;background:{{ $c['color'] }}"></span>
                <span class="small text-gasq-muted flex-grow-1">{{ $c['label'] }}</span>
                <div class="input-group" style="max-width:120px">
                  <span class="input-group-text py-1 px-2">$</span>
                  <input type="number" id="{{ $c['id'] }}" class="form-control form-control-sm text-end py-1" value="{{ $c['default'] }}" step="0.01" oninput="scheduleCompute()">
                </div>
              </div>
              @endforeach
            </div>
          </div>
          <div class="col-lg-6">
            <h5 class="fw-semibold mb-3">Breakdown</h5>
            <div class="rounded p-4 text-white text-center mb-4" style="background:var(--gasq-primary)">
              <div class="small mb-1" style="opacity:.85">Total Bill Rate</div>
              <div class="display-5 fw-bold" id="bcc_total">$0.00</div>
              <div class="small mt-1" style="opacity:.7">per hour</div>
            </div>
            <div id="bcc_breakdown" class="d-flex flex-column gap-2"></div>
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
const COMP_COLORS = ['#3b82f6','#84cc16','#ef4444','#8b5cf6','#06b6d4','#f97316','#a855f7'];
const COMP_IDS = ['bc_wages','bc_taxes','bc_training','bc_recruiting','bc_uniforms','bc_overhead','bc_profit'];
const COMP_LABELS = ['Wages & Benefits','Taxes & Insurance','Training Costs','Recruiting & Screening','Uniforms & Equipment','Overhead','Profit'];

function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v);}
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
    setText('qr_markup', (quick.markupPct||0).toFixed(1)+'%');
    setText('qr_weekly', fmt(quick.weeklyAt40||0));

    const comp = (data.kpis||{}).components||{};
    setText('bcc_total', fmt(comp.totalBillRate||0));
    const bd = document.getElementById('bcc_breakdown');
    if(bd){
      bd.innerHTML = (comp.rows||[]).filter(c=>(c.value||0)>0).map((c,i)=>{
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

document.addEventListener('DOMContentLoaded', ()=>{ runCompute(); });
</script>
@endpush
