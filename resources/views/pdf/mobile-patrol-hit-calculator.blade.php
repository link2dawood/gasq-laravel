@php
    $meta    = (array) ($scenario['meta']    ?? []);
    $contact = (array) ($scenario['contact'] ?? []);
    $kpis    = (array) ($result['kpis']      ?? $result ?? []);

    $money  = static fn ($v) => '$' . number_format((float)($v ?? 0), 2);
    $moneyK = static fn ($v) => '$' . number_format((float)($v ?? 0), 0);
    $num    = static fn ($v, $d = 2) => number_format((float)($v ?? 0), $d);

    $siteName   = trim($meta['siteName']        ?? $contact['siteName']    ?? '');
    $svcType    = trim($meta['serviceType']     ?? '');

    $weeklyChecks   = (int)($meta['weeklyChecks']  ?? $kpis['weeklyChecks']  ?? 0);
    $weeksPerYear   = (float)($meta['weeksPerYear'] ?? 52);
    $monthlyChecks  = (float)($kpis['monthlyChecks'] ?? $weeklyChecks * ($weeksPerYear / 12));
    $annualChecks   = (float)($kpis['annualChecks']  ?? $weeklyChecks * $weeksPerYear);

    $finalPrice     = (float)($kpis['finalPricePerCheck'] ?? 0);
    $weeklyRevenue  = (float)($kpis['weeklyRevenue']      ?? 0);
    $monthlyRevenue = (float)($kpis['monthlyRevenue']     ?? 0);
    $annualRevenue  = (float)($kpis['annualRevenue']      ?? 0);
    $profitMarginPct= (float)($kpis['profitMarginPct']    ?? 0);
    $profitPct      = (float)($meta['profitPct']           ?? 0);

    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-MPH' . str_pad((string) ($reportId ?? rand(1000, 9999)), 4, '0', STR_PAD_LEFT);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Mobile Patrol Hit Service Report',
    'subtitle' => 'Per-Check Pricing & Revenue Analysis',
    'reportNumber' => $reportNumber,
    'reportType' => 'Vendor — Hit Service Report',
    'contactName' => trim($contact['contactName'] ?? $meta['contactName'] ?? ''),
    'contactCompany' => trim($contact['companyName'] ?? $meta['companyName'] ?? ''),
    'contactAddress' => trim($contact['contactAddress'] ?? $meta['contactAddress'] ?? ''),
    'contactEmail' => trim($contact['contactEmail'] ?? $meta['contactEmail'] ?? ''),
    'contactPhone' => trim($contact['contactPhone'] ?? $meta['contactPhone'] ?? ''),
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%" class="stat-grid-label"><p>Final Price Per Check</p></td>
    <td width="34%" class="stat-grid-label"><p>Annual Revenue</p></td>
    <td width="33%" class="stat-grid-label last"><p>Annual Checks</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-blue">
      <p class="num">{{ $money($finalPrice) }}</p>
      <p class="sub">per patrol check</p>
    </td>
    <td class="stat-grid-value bg-pink">
      <p class="num">{{ $moneyK($annualRevenue) }}</p>
      <p class="sub">{{ $num($annualChecks,0) }} annual checks</p>
    </td>
    <td class="stat-grid-value bg-green last">
      <p class="num">{{ $num($annualChecks, 0) }}</p>
      <p class="sub">patrol checks per year</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

@if($siteName || $svcType)
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Site Information</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  @if($siteName)<tr><td>Site Name</td><td class="v">{{ $siteName }}</td></tr>@endif
  @if($svcType)<tr class="alt"><td>Service Type</td><td class="v" style="font-weight:normal; color:#374151;">{{ $svcType }}</td></tr>@endif
</table>
@endif

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Check Volume</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Weekly Checks</td><td class="v">{{ $num($weeklyChecks, 0) }}</td></tr>
  <tr class="alt"><td>Monthly Checks</td><td class="v">{{ $num($monthlyChecks, 0) }}</td></tr>
  <tr><td>Annual Checks</td><td class="v">{{ $num($annualChecks, 0) }}</td></tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Revenue Summary</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Final Price per Check</td><td class="v">{{ $money($finalPrice) }}</td></tr>
  <tr class="alt"><td>Weekly Revenue</td><td class="v">{{ $money($weeklyRevenue) }}</td></tr>
  <tr><td>Monthly Revenue</td><td class="v">{{ $money($monthlyRevenue) }}</td></tr>
  <tr class="total"><td>Annual Revenue</td><td class="v">{{ $money($annualRevenue) }}</td></tr>
</table>

@endsection
