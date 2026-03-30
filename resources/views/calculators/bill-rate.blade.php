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

  {{-- Tabs --}}
  <div class="gasq-tabs-scroll d-print-none mb-3">
    <ul class="gasq-tabs-pill" role="tablist">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#br-basic"><i class="fa fa-calculator me-1"></i> Quick Calculator</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#br-components"><i class="fa fa-chart-pie me-1"></i> Component Builder</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#br-comparison"><i class="fa fa-code-compare me-1"></i> Rate Comparison</a></li>
    </ul>
  </div>

  <div class="tab-content card gasq-card">
    <div class="card-body p-4">

      {{-- ===== QUICK CALCULATOR ===== --}}
      <div class="tab-pane fade show active" id="br-basic">
        <div class="row g-4">
          <div class="col-lg-5">
            <h5 class="fw-semibold mb-3">Inputs</h5>
            <div class="mb-3">
              <label class="form-label fw-medium">Base Pay Rate ($/hr)</label>
              <div class="input-group"><span class="input-group-text">$</span><input type="number" id="qbr_base" class="form-control" value="18.00" step="0.01" oninput="calcQuickBR()"></div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Payroll Tax &amp; Benefits (%)</label>
              <input type="number" id="qbr_benefits" class="form-control" value="20" step="0.1" oninput="calcQuickBR()">
              <div class="form-text">FICA, SUTA, FUTA, workers comp, etc.</div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Overhead (%)</label>
              <input type="number" id="qbr_overhead" class="form-control" value="35" step="0.1" oninput="calcQuickBR()">
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Profit Margin (%)</label>
              <input type="number" id="qbr_profit" class="form-control" value="15" step="0.1" oninput="calcQuickBR()">
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
                  <input type="number" id="{{ $c['id'] }}" class="form-control form-control-sm text-end py-1" value="{{ $c['default'] }}" step="0.01" oninput="calcComponents()">
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

      {{-- ===== COMPARISON ===== --}}
      <div class="tab-pane fade" id="br-comparison">
        <p class="text-gasq-muted small mb-3">Compare up to 4 different bill rate scenarios side by side.</p>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="table-light">
              <tr>
                <th>Parameter</th>
                @foreach(['A','B','C','D'] as $s)
                <th class="text-center">Scenario {{ $s }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="small text-gasq-muted">Base Pay ($/hr)</td>
                @foreach(['A'=>18,'B'=>20,'C'=>22,'D'=>16] as $s=>$def)
                <td><input type="number" id="cmp_base{{ $s }}" class="form-control form-control-sm text-center" value="{{ $def }}" step="0.01" oninput="calcCmpTable()"></td>
                @endforeach
              </tr>
              <tr>
                <td class="small text-gasq-muted">Overhead (%)</td>
                @foreach(['A'=>35,'B'=>30,'C'=>40,'D'=>35] as $s=>$def)
                <td><input type="number" id="cmp_overhead{{ $s }}" class="form-control form-control-sm text-center" value="{{ $def }}" step="0.1" oninput="calcCmpTable()"></td>
                @endforeach
              </tr>
              <tr>
                <td class="small text-gasq-muted">Profit (%)</td>
                @foreach(['A'=>15,'B'=>18,'C'=>12,'D'=>20] as $s=>$def)
                <td><input type="number" id="cmp_profit{{ $s }}" class="form-control form-control-sm text-center" value="{{ $def }}" step="0.1" oninput="calcCmpTable()"></td>
                @endforeach
              </tr>
              <tr class="table-light fw-semibold">
                <td>Bill Rate ($/hr)</td>
                @foreach(['A','B','C','D'] as $s)
                <td class="text-center" id="cmp_rate{{ $s }}">$0.00</td>
                @endforeach
              </tr>
              <tr>
                <td class="small text-gasq-muted">Weekly (40hr)</td>
                @foreach(['A','B','C','D'] as $s)
                <td class="text-center small" id="cmp_weekly{{ $s }}">$0.00</td>
                @endforeach
              </tr>
              <tr>
                <td class="small text-gasq-muted">Annual (40hr/52wk)</td>
                @foreach(['A','B','C','D'] as $s)
                <td class="text-center small" id="cmp_annual{{ $s }}">$0.00</td>
                @endforeach
              </tr>
              <tr>
                <td class="small text-gasq-muted">Markup %</td>
                @foreach(['A','B','C','D'] as $s)
                <td class="text-center small" id="cmp_markup{{ $s }}">0%</td>
                @endforeach
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

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

async function calcQuickBR(){
  const payload = {
    version:'v24',
    scenario:{ meta:{ quick:{ basePayRate:g('qbr_base'), benefitsPct:g('qbr_benefits'), overheadPct:g('qbr_overhead'), profitPct:g('qbr_profit') } } }
  };
  // include components too so backend can return both blocks
  payload.scenario.meta.components = {
    wages:g('bc_wages'), taxes:g('bc_taxes'), training:g('bc_training'), recruiting:g('bc_recruiting'),
    uniforms:g('bc_uniforms'), overhead:g('bc_overhead'), profit:g('bc_profit')
  };
  const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'bill-rate-analysis']) }}', {
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
  const out = (data.kpis||{}).quick||{};

  setText('qr_base', fmt(out.basePayRate||0)+'/hr');
  setText('qr_benefitsAmt', '+'+fmt(out.benefitsAmt||0)+'/hr');
  setText('qr_burdened', fmt(out.burdenedCost||0)+'/hr');
  setText('qr_burdened2', fmt(out.burdenedCost||0)+'/hr');
  setText('qr_overheadAmt', '+'+fmt(out.overheadAmt||0)+'/hr');
  setText('qr_withOverhead', fmt(out.withOverhead||0)+'/hr');
  setText('qr_billRate', fmt(out.billRate||0));
  setText('qr_markup', (out.markupPct||0).toFixed(1)+'%');
  setText('qr_weekly', fmt(out.weeklyAt40||0));
}

async function calcComponents(){
  await calcQuickBR(); // compute endpoint returns both quick + component breakdown
  const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'bill-rate-analysis']) }}', {
    method:'POST',
    headers:{
      'Content-Type':'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
      'Accept':'application/json'
    },
    body: JSON.stringify({
      version:'v24',
      scenario:{ meta:{ components:{
        wages:g('bc_wages'), taxes:g('bc_taxes'), training:g('bc_training'), recruiting:g('bc_recruiting'),
        uniforms:g('bc_uniforms'), overhead:g('bc_overhead'), profit:g('bc_profit')
      } } }
    })
  });
  const data = await res.json();
  if(!res.ok || !data || !data.ok){ console.error(data); return; }
  const out = (data.kpis||{}).components||{};
  setText('bcc_total', fmt(out.totalBillRate||0));
  const bd = document.getElementById('bcc_breakdown');
  bd.innerHTML = (out.rows||[]).filter(c=>(c.value||0)>0).map((c,i)=>{
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

function calcCmpTable(){
  ['A','B','C','D'].forEach(s=>{
    const base = g('cmp_base'+s), overhead = g('cmp_overhead'+s)/100, profit = g('cmp_profit'+s)/100;
    const taxRate = 0.2; // default 20% burden
    const burdened = base * (1+taxRate);
    const withOverhead = burdened * (1+overhead);
    const billRate = withOverhead / (1-profit);
    const markup = base>0 ? ((billRate-base)/base)*100 : 0;
    const rateEl = document.getElementById('cmp_rate'+s);
    if(rateEl){ rateEl.textContent=fmt(billRate); rateEl.className='text-center fw-semibold text-primary'; }
    setText('cmp_weekly'+s, fmt(billRate*40));
    setText('cmp_annual'+s, fmt(billRate*40*52));
    setText('cmp_markup'+s, markup.toFixed(1)+'%');
  });
}

document.addEventListener('DOMContentLoaded', ()=>{ calcQuickBR(); calcComponents(); calcCmpTable(); });
</script>
@endpush
