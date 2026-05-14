@php
    $r = $result ?? [];
    $totalHours = (float) ($r['total_annual_hours'] ?? 0);
    $payCost = (float) ($r['total_annual_pay_cost'] ?? 0);
    $billRevenue = (float) ($r['total_annual_bill_revenue'] ?? 0);
    $grossMargin = (float) ($r['gross_margin'] ?? 0);
    $marginPct = (float) ($r['margin_percent'] ?? 0);

    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-CA' . str_pad((string) (rand(1000, 9999)), 4, '0', STR_PAD_LEFT);
    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $moneyK = fn ($v) => '$' . number_format((float) $v, 0);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Contract Analysis Report',
    'subtitle' => 'Annual Margin & Revenue Summary',
    'reportNumber' => $reportNumber,
    'reportType' => 'Vendor — Contract Analysis',
    'contactName' => $user?->name ?? null,
    'contactCompany' => $user?->company ?? null,
    'contactEmail' => $user?->email ?? null,
    'contactPhone' => $user?->phone ?? null,
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%" class="stat-grid-label"><p>Annual Bill Revenue</p></td>
    <td width="34%" class="stat-grid-label"><p>Annual Pay Cost</p></td>
    <td width="33%" class="stat-grid-label last"><p>Gross Margin</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-blue">
      <p class="num">{{ $moneyK($billRevenue) }}</p>
      <p class="sub">contract revenue</p>
    </td>
    <td class="stat-grid-value bg-pink">
      <p class="num">{{ $moneyK($payCost) }}</p>
      <p class="sub">workforce cost</p>
    </td>
    <td class="stat-grid-value bg-green last">
      <p class="num">{{ $moneyK($grossMargin) }}</p>
      <p class="sub">{{ number_format($marginPct, 1) }}% margin</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Contract Summary</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Total Annual Hours</td><td class="v">{{ number_format($totalHours, 0) }}</td></tr>
  <tr class="alt"><td>Total Annual Pay Cost</td><td class="v">{{ $money($payCost) }}</td></tr>
  <tr><td>Total Annual Bill Revenue</td><td class="v">{{ $money($billRevenue) }}</td></tr>
  <tr class="total"><td>Gross Margin</td><td class="v">{{ $money($grossMargin) }}</td></tr>
  <tr style="background:#e8f5eb;"><td style="font-weight:bold; color:#1e3558;">Margin %</td><td class="v" style="color:#1e3558;">{{ number_format($marginPct, 1) }}%</td></tr>
</table>

@endsection
