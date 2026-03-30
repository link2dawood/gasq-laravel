@extends('layouts.app')
@section('title', 'Security Cost Analysis')
@section('header_variant', 'dashboard')

@section('content')
<div class="min-vh-100 py-4 px-3 px-md-4" style="background:var(--gasq-background)">
<div class="container-xl">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('main-menu-calculator.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i></a>
      <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="fa fa-chart-line text-primary"></i> Security Cost Analysis
        </h1>
        <div class="text-gasq-muted small">Comprehensive security cost breakdown and ROI analysis</div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card gasq-card h-100">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Cost Inputs</h5></div>
        <div class="card-body d-flex flex-column gap-3">
          <div><label class="form-label fw-medium">Annual Security Budget ($)</label>
            <div class="input-group"><span class="input-group-text">$</span><input type="number" id="ca_budget" class="form-control" value="180000" oninput="calcCA()"></div></div>
          <div><label class="form-label fw-medium">Number of Guards</label>
            <input type="number" id="ca_guards" class="form-control" value="3" min="1" oninput="calcCA()"></div>
          <div><label class="form-label fw-medium">Hours Coverage per Day</label>
            <input type="number" id="ca_hours" class="form-control" value="24" step="0.5" oninput="calcCA()"></div>
          <hr>
          <h6 class="fw-semibold">Cost of NOT Having Security</h6>
          <div><label class="form-label small fw-medium">Annual Theft/Shrinkage Losses ($)</label>
            <div class="input-group"><span class="input-group-text">$</span><input type="number" id="ca_theft" class="form-control" value="50000" oninput="calcCA()"></div></div>
          <div><label class="form-label small fw-medium">Annual Insurance Premium ($)</label>
            <div class="input-group"><span class="input-group-text">$</span><input type="number" id="ca_insurance" class="form-control" value="25000" oninput="calcCA()"></div></div>
          <div><label class="form-label small fw-medium">Annual Liability Exposure ($)</label>
            <div class="input-group"><span class="input-group-text">$</span><input type="number" id="ca_liability" class="form-control" value="30000" oninput="calcCA()"></div></div>
          <div><label class="form-label small fw-medium">Security Reduces Risk By (%)</label>
            <input type="number" id="ca_reduction" class="form-control" value="70" step="5" oninput="calcCA()"></div>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card gasq-card mb-4">
        <div class="card-header py-3"><h5 class="card-title mb-0 fw-semibold">Cost Analysis Results</h5></div>
        <div class="card-body">
          <div class="row g-3 mb-4">
            <div class="col-6 col-md-3"><div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)"><div class="x-sm text-gasq-muted mb-1">Cost/Guard/Year</div><div class="fs-5 fw-bold" id="r_perGuard">$0</div></div></div>
            <div class="col-6 col-md-3"><div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)"><div class="x-sm text-gasq-muted mb-1">Cost/Hour</div><div class="fs-5 fw-bold" id="r_perHour">$0.00</div></div></div>
            <div class="col-6 col-md-3"><div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)"><div class="x-sm text-gasq-muted mb-1">Monthly Cost</div><div class="fs-5 fw-bold" id="r_monthly">$0</div></div></div>
            <div class="col-6 col-md-3"><div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)"><div class="x-sm text-gasq-muted mb-1">Weekly Cost</div><div class="fs-5 fw-bold" id="r_weekly">$0</div></div></div>
          </div>

          <h6 class="fw-semibold mb-3">Risk Reduction Value</h6>
          <div class="rounded p-3 mb-3" style="background:var(--gasq-muted-bg)">
            <div class="d-flex justify-content-between mb-1 small"><span class="text-gasq-muted">Total annual risk exposure</span><span id="r_totalRisk">$0.00</span></div>
            <div class="d-flex justify-content-between mb-1 small"><span class="text-gasq-muted">Risk reduction (at <span id="r_reductionPct">70</span>%)</span><span id="r_riskReduction">$0.00</span></div>
            <div class="d-flex justify-content-between small fw-semibold"><span>Net value of security investment</span><span class="text-success" id="r_netValue">$0.00</span></div>
          </div>

          <div class="rounded p-4 text-white text-center mb-3" style="background:var(--gasq-primary)">
            <div class="small mb-1" style="opacity:.85">Return on Security Investment (ROSI)</div>
            <div class="display-5 fw-bold" id="r_rosi">0%</div>
            <div class="small mt-1" style="opacity:.75">Risk-adjusted net benefit</div>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <div class="rounded p-3 text-center" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3)">
                <div class="x-sm text-gasq-muted mb-1">Risk Savings</div>
                <div class="fs-5 fw-bold text-success" id="r_savings">$0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="rounded p-3 text-center" style="background:var(--gasq-muted-bg)">
                <div class="x-sm text-gasq-muted mb-1">Payback Period</div>
                <div class="fs-5 fw-bold" id="r_payback">0.0 mo</div>
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
<style>.x-sm{font-size:0.75rem;line-height:1.2}</style>
<script>
function fmt(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:0,maximumFractionDigits:0}).format(v);}
function fmt2(v){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',minimumFractionDigits:2}).format(v);}
function g(id){return parseFloat(document.getElementById(id).value)||0;}
function setText(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}

function calcCA(){
  const budget=g('ca_budget'), guards=g('ca_guards'), hours=g('ca_hours');
  const theft=g('ca_theft'), insurance=g('ca_insurance'), liability=g('ca_liability'), reduction=g('ca_reduction')/100;
  const annualHours = hours * 365;
  const perGuard = guards>0 ? budget/guards : 0;
  const perHour = annualHours>0 ? budget/annualHours : 0;
  const totalRisk = theft + insurance + liability;
  const riskReduction = totalRisk * reduction;
  const netValue = riskReduction - budget;
  const rosi = budget>0 ? (netValue/budget)*100 : 0;
  const monthly = budget/12;
  const weekly = budget/52;
  const payback = riskReduction>0 ? (budget/riskReduction)*12 : 0;

  setText('r_perGuard', fmt(perGuard));
  setText('r_perHour', fmt2(perHour));
  setText('r_monthly', fmt(monthly));
  setText('r_weekly', fmt(weekly));
  setText('r_totalRisk', fmt(totalRisk));
  setText('r_riskReduction', fmt(riskReduction));
  setText('r_netValue', fmt(netValue));
  setText('r_reductionPct', g('ca_reduction').toFixed(0));
  const rosiEl=document.getElementById('r_rosi');
  rosiEl.textContent = rosi.toFixed(1)+'%';
  rosiEl.className = 'display-5 fw-bold '+(rosi>=0?'text-white':'text-warning');
  setText('r_savings', fmt(riskReduction));
  setText('r_payback', payback.toFixed(1)+' mo');
}

document.addEventListener('DOMContentLoaded', calcCA);
</script>
@endpush
