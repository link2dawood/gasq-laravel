@extends('layouts.app')

@section('title', 'GASQ Impact — Procurement Intelligence. Proven Results.')

@php
    // GASQ's own numbers are always USD — not converted to the viewer's currency.
    $usd = fn ($v) => '$' . number_format((float) $v, 0);
    $num = fn ($v) => number_format((float) $v, 0);
@endphp

@push('styles')
<style>
  .gi-shell { background: radial-gradient(circle at top right, #123a6b 0%, transparent 45%), linear-gradient(180deg,#0a1c38 0%,#071429 100%); color:#eaf1fb; min-height: calc(100vh - 4rem); }
  .gi-gold { color:#d4af37; }
  .gi-green { color:#4ade80; }
  .gi-kicker { letter-spacing:.14em; font-weight:700; text-transform:uppercase; }
  .gi-band { background:linear-gradient(90deg,#c9a227,#e6c65c); color:#0a1c38; border-radius:.6rem; font-weight:800; letter-spacing:.06em; }
  .gi-card { background:rgba(255,255,255,.03); border:1px solid rgba(212,175,55,.35); border-radius:1rem; padding:1.25rem; height:100%; }
  .gi-card .gi-icon { width:2.5rem;height:2.5rem;border-radius:.65rem;display:inline-flex;align-items:center;justify-content:center;background:rgba(212,175,55,.14); color:#d4af37; }
  .gi-stat { font-size:1.7rem; font-weight:800; line-height:1.1; }
  .gi-stat-sm { font-size:1.35rem; font-weight:800; }
  .gi-label { font-size:.72rem; letter-spacing:.06em; text-transform:uppercase; color:#9fb3d1; font-weight:700; }
  .gi-sub { font-size:.78rem; color:#8ea3c4; }
  .gi-badge-cert { border:2px solid #d4af37; border-radius:999px; color:#d4af37; font-weight:800; letter-spacing:.08em; }
</style>
@endpush

@section('content')
<div class="gi-shell py-5 px-3 px-md-5">
  <div class="container-xl">

    {{-- Header --}}
    <div class="text-center mb-5">
      <div class="gi-kicker gi-gold small mb-2">GASQ Certified™ · Procurement Appraisal Program</div>
      <h1 class="fw-bold mb-1">Procurement Intelligence. <span class="gi-gold">Proven Results.</span></h1>
      <div class="gi-kicker" style="color:#cdd9ec;">Real Data. Real Value. Real Impact.</div>
    </div>

    {{-- Financial Impact --}}
    <div class="gi-band text-center py-2 px-3 mb-4">FINANCIAL IMPACT SUMMARY</div>
    <div class="row g-3 mb-5">
      @php
        $fin = [
          ['icon'=>'fa-dollar-sign','label'=>'Total Capital Recovery Identified','value'=>$usd($impact['capital_recovery_total']),'sub'=>'Potential capital recovery identified for clients','cls'=>'gi-gold'],
          ['icon'=>'fa-chart-line','label'=>'Average Identified per Appraisal','value'=>$usd($impact['capital_recovery_avg']),'sub'=>'Average value identified per appraisal','cls'=>'gi-gold'],
          ['icon'=>'fa-bullseye','label'=>'Highest Single Recovery','value'=>$usd($impact['capital_recovery_highest']),'sub'=>'Largest single-appraisal capital recovery','cls'=>'gi-gold'],
          ['icon'=>'fa-trophy','label'=>'Total Client Savings Realized','value'=>$usd($impact['client_savings_awarded']),'sub'=>'Recovery on awarded contracts','cls'=>'gi-green'],
          ['icon'=>'fa-coins','label'=>'Total Contract Value Analyzed','value'=>$usd($impact['contract_value_total']),'sub'=>'Across all appraisals','cls'=>'gi-gold'],
          ['icon'=>'fa-shield-halved','label'=>'Contracts Awarded','value'=>$num($impact['contracts_awarded']),'sub'=>'Awarded through the platform','cls'=>'gi-green'],
        ];
      @endphp
      @foreach($fin as $c)
        <div class="col-6 col-lg-4">
          <div class="gi-card">
            <div class="gi-icon mb-3"><i class="fa {{ $c['icon'] }}"></i></div>
            <div class="gi-label mb-1">{{ $c['label'] }}</div>
            <div class="gi-stat {{ $c['cls'] }}">{{ $c['value'] }}</div>
            <div class="gi-sub mt-1">{{ $c['sub'] }}</div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Operational & Reach --}}
    <div class="gi-band text-center py-2 px-3 mb-4">OPERATIONAL &amp; PLATFORM REACH</div>
    <div class="row g-3 mb-5">
      @php
        $ops = [
          ['label'=>'Appraisals Run','value'=>$num($operational['appraisals'])],
          ['label'=>'Annual Billable Hours Analyzed','value'=>$num($operational['billable_hours_annual'])],
          ['label'=>'Monthly Billable Hours','value'=>$num($operational['billable_hours_monthly'])],
          ['label'=>'Weekly Billable Hours','value'=>$num($operational['billable_hours_weekly'])],
          ['label'=>'Buyers on Platform','value'=>$num($operational['buyers'])],
          ['label'=>'Vendors on Platform','value'=>$num($operational['vendors'])],
          ['label'=>'States Covered','value'=>$num($operational['states'])],
        ];
      @endphp
      @foreach($ops as $c)
        <div class="col-6 col-md-4 col-xl-3">
          <div class="gi-card text-center">
            <div class="gi-stat-sm gi-green">{{ $c['value'] }}</div>
            <div class="gi-label mt-1">{{ $c['label'] }}</div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Footer --}}
    <div class="text-center">
      <span class="gi-badge-cert d-inline-block px-3 py-2 mb-3">GASQ CERTIFIED™ · FINANCIAL CERTAINTY · PROVEN RESULTS</span>
      <h4 class="fw-bold mb-1">Stronger Proposals. Stronger Margins. Stronger Future.</h4>
      <div class="gi-gold fw-semibold">Know Before You Buy. Know Before You Bid. Know Before You Price.</div>
      <p class="gi-sub mt-3 mb-0" style="max-width:52rem;margin-inline:auto;">Figures are computed live from platform activity and will vary based on project scope, market conditions, vendor participation, and client implementation decisions. Amounts in USD.</p>
    </div>

  </div>
</div>
@endsection
