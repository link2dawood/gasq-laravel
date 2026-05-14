@php
    $r = $result ?? [];
    $scenarioA = (float) ($r['scenario_a_annual'] ?? 0);
    $scenarioB = (float) ($r['scenario_b_annual'] ?? 0);
    $savings = (float) ($r['savings'] ?? 0);
    $savingsPct = (float) ($r['savings_percent'] ?? 0);

    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-MPC' . str_pad((string) (rand(1000, 9999)), 4, '0', STR_PAD_LEFT);
    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $moneyK = fn ($v) => '$' . number_format((float) $v, 0);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Mobile Patrol Comparison',
    'subtitle' => 'Scenario A vs Scenario B · Annual Cost',
    'reportNumber' => $reportNumber,
    'reportType' => 'Vendor — Comparison Report',
    'contactName' => $user?->name ?? null,
    'contactCompany' => $user?->company ?? null,
    'contactEmail' => $user?->email ?? null,
    'contactPhone' => $user?->phone ?? null,
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%" class="stat-grid-label"><p>Scenario A — Annual</p></td>
    <td width="34%" class="stat-grid-label"><p>Scenario B — Annual</p></td>
    <td width="33%" class="stat-grid-label last"><p>Savings (B vs A)</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-blue">
      <p class="num">{{ $moneyK($scenarioA) }}</p>
      <p class="sub">baseline scenario</p>
    </td>
    <td class="stat-grid-value bg-purple">
      <p class="num">{{ $moneyK($scenarioB) }}</p>
      <p class="sub">alternative scenario</p>
    </td>
    <td class="stat-grid-value bg-green last">
      <p class="num">{{ $moneyK($savings) }}</p>
      <p class="sub">{{ number_format($savingsPct, 1) }}% improvement</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Comparison Result</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Scenario A Annual Cost</td><td class="v">{{ $money($scenarioA) }}</td></tr>
  <tr class="alt"><td>Scenario B Annual Cost</td><td class="v">{{ $money($scenarioB) }}</td></tr>
  <tr><td>Savings (B vs A)</td><td class="v">{{ $money($savings) }}</td></tr>
  <tr style="background:#e8f5eb;"><td style="font-weight:bold; color:#1e3558;">Savings %</td><td class="v" style="color:#1e3558;">{{ number_format($savingsPct, 1) }}%</td></tr>
</table>

@endsection
