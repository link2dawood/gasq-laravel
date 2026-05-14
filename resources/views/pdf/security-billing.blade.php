@php
    $r = $result ?? [];
    $weekly = (float) ($r['weekly_total'] ?? 0);
    $monthly = (float) ($r['monthly_total'] ?? 0);
    $annual = (float) ($r['annual_total'] ?? 0);

    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-SB' . str_pad((string) (rand(1000, 9999)), 4, '0', STR_PAD_LEFT);
    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $moneyK = fn ($v) => '$' . number_format((float) $v, 0);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Security Billing Report',
    'subtitle' => 'Weekly / Monthly / Annual Billing Estimate',
    'reportNumber' => $reportNumber,
    'reportType' => 'Vendor — Billing Estimate',
    'contactName' => $user?->name ?? null,
    'contactCompany' => $user?->company ?? null,
    'contactEmail' => $user?->email ?? null,
    'contactPhone' => $user?->phone ?? null,
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%" class="stat-grid-label"><p>Weekly Total</p></td>
    <td width="34%" class="stat-grid-label"><p>Monthly Total</p></td>
    <td width="33%" class="stat-grid-label last"><p>Annual Total</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-blue">
      <p class="num">{{ $moneyK($weekly) }}</p>
      <p class="sub">per week</p>
    </td>
    <td class="stat-grid-value bg-purple">
      <p class="num">{{ $moneyK($monthly) }}</p>
      <p class="sub">per month</p>
    </td>
    <td class="stat-grid-value bg-green last">
      <p class="num">{{ $moneyK($annual) }}</p>
      <p class="sub">per year</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Billing Estimate Detail</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Weekly Total</td><td class="v">{{ $money($weekly) }}</td></tr>
  <tr class="alt"><td>Monthly Total</td><td class="v">{{ $money($monthly) }}</td></tr>
  <tr class="total"><td>Annual Total</td><td class="v">{{ $money($annual) }}</td></tr>
</table>

@endsection
