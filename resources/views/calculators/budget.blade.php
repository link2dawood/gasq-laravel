@extends('layouts.app')
@section('title', 'Budget Calculator')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-piggy-bank text-primary"></i> Security Budget Calculator
        </h1>
        <div class="text-gasq-muted small">Plan and analyze your security budget across categories</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="resetBudget()"><i class="fa fa-rotate me-1"></i> Reset</button>
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
  </div>

  <div class="row g-4">

    {{-- Budget Input Panel --}}
    <div class="col-lg-6">
      <div class="card gasq-card h-100">
        <div class="card-header py-3">
          <h5 class="card-title mb-0 fw-semibold d-flex align-items-center gap-2">
            <i class="fa fa-list text-primary"></i> Budget Categories
          </h5>
        </div>
        <div class="card-body d-flex flex-column gap-3">

          <div>
            <label class="form-label fw-medium">Total Annual Budget ($)</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" id="bg_total" class="form-control fs-5 fw-semibold" value="250000" step="1000" oninput="calcBudget()">
            </div>
          </div>

          <hr class="my-1">
          <h6 class="fw-semibold">Allocation Percentages</h6>
          <p class="small text-gasq-muted mb-0">Adjust percentages to allocate your budget. Total should equal 100%.</p>

          @php
          $cats = [
            ['id'=>'bg_labor','label'=>'Labor (Wages & Benefits)','default'=>60,'color'=>'#3b82f6'],
            ['id'=>'bg_training','label'=>'Training & Development','default'=>8,'color'=>'#22c55e'],
            ['id'=>'bg_equipment','label'=>'Equipment & Technology','default'=>10,'color'=>'#f97316'],
            ['id'=>'bg_vehicles','label'=>'Vehicles & Transportation','default'=>8,'color'=>'#8b5cf6'],
            ['id'=>'bg_overhead','label'=>'Overhead & Administration','default'=>7,'color'=>'#06b6d4'],
            ['id'=>'bg_insurance','label'=>'Insurance & Compliance','default'=>5,'color'=>'#ef4444'],
            ['id'=>'bg_misc','label'=>'Miscellaneous / Contingency','default'=>2,'color'=>'#84cc16'],
          ];
          @endphp

          @foreach($cats as $cat)
          <div>
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label small fw-medium mb-0 d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:{{ $cat['color'] }}"></span>
                {{ $cat['label'] }}
              </label>
              <div class="d-flex align-items-center gap-2">
                <span class="small fw-medium" id="{{ $cat['id'] }}_amt">$0.00</span>
                <div class="input-group" style="width:80px">
                  <input type="number" id="{{ $cat['id'] }}" class="form-control form-control-sm text-center" value="{{ $cat['default'] }}" min="0" max="100" step="1" oninput="calcBudget()">
                  <span class="input-group-text px-1">%</span>
                </div>
              </div>
            </div>
            <input type="range" id="{{ $cat['id'] }}_range" class="form-range mb-1" min="0" max="100" step="1" value="{{ $cat['default'] }}" data-sync="{{ $cat['id'] }}">
            <div class="progress" style="height:6px">
              <div class="progress-bar" id="{{ $cat['id'] }}_bar" style="width:{{ $cat['default'] }}%;background:{{ $cat['color'] }}"></div>
            </div>
          </div>
          @endforeach

          <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--gasq-muted-bg)">
            <span class="small fw-semibold">Total Allocated</span>
            <span class="fw-bold" id="bg_totalPct">100%</span>
          </div>
          <div class="alert alert-warning d-none py-2" id="bg_warning" role="alert">
            <i class="fa fa-triangle-exclamation me-1"></i> Percentages should total 100%
          </div>

        </div>
      </div>
    </div>

    {{-- Results Panel --}}
    <div class="col-lg-6">
      <div class="card gasq-card mb-4">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Budget Summary</h5></div>
        <div class="card-body">
          <div class="row g-3 mb-4">
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Annual Budget</div>
                <div class="metric-value text-primary" id="r_annual">$0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Monthly Budget</div>
                <div class="metric-value" id="r_monthly">$0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Weekly Budget</div>
                <div class="metric-value" id="r_weekly">$0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="gasq-metric-card text-center">
                <div class="metric-desc">Daily Budget</div>
                <div class="metric-value" id="r_daily">$0.00</div>
              </div>
            </div>
          </div>

          <h6 class="fw-semibold mb-3">Allocation Breakdown</h6>
          <div id="bg_breakdown" class="d-flex flex-column gap-2 mb-4"></div>

          <div class="rounded p-3" style="background:rgba(6,45,121,0.06);border:1px solid rgba(6,45,121,0.15)">
            <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2"><i class="fa fa-lightbulb text-primary"></i> Budget Insights</h6>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Labor allocation</span><span id="ins_laborPct" class="fw-medium">0%</span></div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-gasq-muted">Industry benchmark (labor)</span><span class="text-gasq-muted">55–70%</span></div>
            <div class="d-flex justify-content-between small"><span class="text-gasq-muted">Labor status</span><span id="ins_laborStatus" class="fw-medium">—</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <x-report-actions reportType="budget-calculator" />

</div>
</div>
@endsection

@push('scripts')
<style>.x-sm{font-size:0.75rem;line-height:1.2}</style>
<script>
const savedScenario = window.__gasqCalculatorState?.scenario || null;
const CATS = [
  {id:'bg_labor',label:'Labor (Wages & Benefits)',color:'#3b82f6'},
  {id:'bg_training',label:'Training & Development',color:'#22c55e'},
  {id:'bg_equipment',label:'Equipment & Technology',color:'#f97316'},
  {id:'bg_vehicles',label:'Vehicles & Transportation',color:'#8b5cf6'},
  {id:'bg_overhead',label:'Overhead & Administration',color:'#06b6d4'},
  {id:'bg_insurance',label:'Insurance & Compliance',color:'#ef4444'},
  {id:'bg_misc',label:'Miscellaneous / Contingency',color:'#84cc16'},
];

function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v);}
function g(id){return parseFloat(document.getElementById(id).value)||0;}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

function initSliderSync(){
  document.querySelectorAll('input[type="range"][data-sync]').forEach((rangeEl)=>{
    const id = rangeEl.getAttribute('data-sync');
    const numEl = document.getElementById(id);
    if(!numEl) return;

    const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
    const syncRangeFromNumber = () => {
      const min = parseFloat(rangeEl.min || '0');
      const max = parseFloat(rangeEl.max || '100');
      const v = parseFloat(numEl.value || rangeEl.value || '0');
      rangeEl.value = String(clamp(v, min, max));
    };
    const syncNumberFromRange = () => {
      numEl.value = rangeEl.value;
    };

    syncRangeFromNumber();

    rangeEl.addEventListener('input', () => {
      syncNumberFromRange();
      calcBudget();
    });
    numEl.addEventListener('input', () => {
      syncRangeFromNumber();
    });
  });
}

async function calcBudget(){
  const total = g('bg_total');
  const pcts = CATS.map(c=>({...c, pct:g(c.id)}));
  const sumPct = pcts.reduce((s,c)=>s+c.pct,0);
  const warning = document.getElementById('bg_warning');
  const pctEl = document.getElementById('bg_totalPct');

  pctEl.textContent = sumPct.toFixed(1)+'%';
  pctEl.className = 'fw-bold ' + (Math.abs(sumPct-100)>0.5 ? 'text-danger' : 'text-success');
  warning.classList.toggle('d-none', Math.abs(sumPct-100) <= 0.5);

  pcts.forEach(c=>{
    const amt = total * c.pct/100;
    const barEl = document.getElementById(c.id+'_bar');
    const amtEl = document.getElementById(c.id+'_amt');
    if(barEl) barEl.style.width = Math.min(c.pct, 100)+'%';
    if(amtEl) amtEl.textContent = fmt(amt);
  });

  setText('r_annual', fmt(total));
  setText('r_monthly', fmt(total/12));
  setText('r_weekly', fmt(total/52));
  setText('r_daily', fmt(total/365));

  const bd = document.getElementById('bg_breakdown');
  bd.innerHTML = pcts.filter(c=>c.pct>0).map(c=>{
    const amt = total * c.pct/100;
    return `<div class="d-flex justify-content-between align-items-center small">
      <div class="d-flex align-items-center gap-2">
        <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:${c.color}"></span>
        <span class="text-gasq-muted">${c.label}</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="fw-medium">${fmt(amt)}</span>
        <span class="badge text-bg-secondary" style="font-size:0.65rem">${c.pct.toFixed(0)}%</span>
      </div>
    </div>`;
  }).join('');

  const laborPct = g('bg_labor');
  setText('ins_laborPct', laborPct.toFixed(0)+'%');
  const laborStatus = document.getElementById('ins_laborStatus');
  if(laborPct<55){ laborStatus.textContent='Below benchmark'; laborStatus.className='fw-medium text-warning'; }
  else if(laborPct>70){ laborStatus.textContent='Above benchmark'; laborStatus.className='fw-medium text-danger'; }
  else{ laborStatus.textContent='Within benchmark'; laborStatus.className='fw-medium text-success'; }

  // Also publish basic budget KPIs via backend (keeps parity harness consistent).
  const res = await fetch('{{ route('backend.standalone.v24.compute', ['type' => 'budget-calculator']) }}', {
    method:'POST',
    headers:{
      'Content-Type':'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
      'Accept':'application/json'
    },
    body: JSON.stringify({
      version:'v24',
      scenario:{ meta:{
        annualBudget: total,
        allocations: {
          labor: g('bg_labor'),
          training: g('bg_training'),
          equipment: g('bg_equipment'),
          vehicles: g('bg_vehicles'),
          overhead: g('bg_overhead'),
          insurance: g('bg_insurance'),
          misc: g('bg_misc'),
        }
      } }
    })
  });
  const data = await res.json();
  if(!res.ok || !data || !data.ok){ console.error(data); }
}

function hydrateSavedBudget(){
  const meta = savedScenario?.meta || {};
  const allocations = meta.allocations || {};

  if(meta.annualBudget !== undefined){
    const totalEl = document.getElementById('bg_total');
    if(totalEl) totalEl.value = meta.annualBudget;
  }

  const map = {
    bg_labor: allocations.labor,
    bg_training: allocations.training,
    bg_equipment: allocations.equipment,
    bg_vehicles: allocations.vehicles,
    bg_overhead: allocations.overhead,
    bg_insurance: allocations.insurance,
    bg_misc: allocations.misc,
  };

  Object.entries(map).forEach(([id, value]) => {
    if(value === undefined || value === null) return;
    const el = document.getElementById(id);
    if(el) el.value = value;
  });
}

function resetBudget(){
  document.getElementById('bg_total').value = 250000;
  const defaults = {bg_labor:60,bg_training:8,bg_equipment:10,bg_vehicles:8,bg_overhead:7,bg_insurance:5,bg_misc:2};
  Object.entries(defaults).forEach(([id,v])=>{ const el=document.getElementById(id); if(el) el.value=v; });
  initSliderSync();
  calcBudget();
}

document.addEventListener('DOMContentLoaded', ()=>{
  hydrateSavedBudget();
  initSliderSync();
  calcBudget();
});
</script>
@endpush
