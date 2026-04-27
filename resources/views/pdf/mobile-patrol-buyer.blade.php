<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GASQ Mobile Patrol – Buyer Quote</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 0; background: #fff; }
    table { border-collapse: collapse; }
    td, th { padding: 0; margin: 0; }
    p { margin: 0; padding: 0; }
</style>
</head>
<body>
@php
    $scenarioMeta = (array) ($scenario['meta'] ?? []);
    $contact      = (array) ($scenario['contact'] ?? []);
    $kpis         = (array) ($result['kpis'] ?? $result ?? []);

    $money  = static fn ($v) => '$' . number_format((float)($v ?? 0), 2);
    $moneyK = static fn ($v) => '$' . number_format((float)($v ?? 0), 0);
    $num    = static fn ($v, $d = 2) => number_format((float)($v ?? 0), $d);

    $rosInput = (float)($scenarioMeta['returnOnSalesPct'] ?? 0);
    $rosRate  = $rosInput > 1 ? $rosInput / 100 : $rosInput;
    $rosPct   = (float)($kpis['returnOnSalesPercentDisplay'] ?? ($rosRate * 100));

    $reportNum   = 'GASQ ' . now()->format('Y-m-d') . '-' . str_pad(($reportId ?? rand(1,9999)), 4, '0', STR_PAD_LEFT);
    $logoPath    = 'file://' . public_path('img/gasq-logo.png');

    $cName    = trim($contact['contactName']    ?? '');
    $cCompany = trim($contact['companyName']    ?? '');
    $cAddress = trim($contact['contactAddress'] ?? '');
    $cEmail   = trim($contact['contactEmail']   ?? '');
    $cPhone   = trim($contact['contactPhone']   ?? '');

    $hourlyRate  = (float)($kpis['costPerHour']                     ?? 0);
    $totalCost   = (float)($kpis['totalAnnualCost']                 ?? 0);
    $totalRos    = (float)($kpis['totalAnnualCostWithReturnOnSales'] ?? 0);
    $annualHours = (float)($scenarioMeta['annualHours']              ?? 0);
@endphp

{{-- HEADER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#1e3558;">
  <tr>
    <td width="90" style="padding:16px 12px 16px 20px; vertical-align:middle;">
      <img src="{{ $logoPath }}" alt="GASQ" style="width:70px; height:auto; display:block;">
    </td>
    <td style="padding:16px 12px; vertical-align:middle;">
      <p style="font-size:17px; font-weight:bold; color:#ffffff; letter-spacing:0.03em; margin-bottom:3px;">GASQ Mobile Patrol Quote</p>
      <p style="font-size:9px; color:rgba(255,255,255,0.65); text-transform:uppercase; letter-spacing:0.1em;">Estimated Hourly Service Rate</p>
    </td>
    <td style="padding:16px 20px 16px 12px; vertical-align:middle; text-align:right;">
      <p style="font-size:10px; font-weight:bold; color:#ffffff; margin-bottom:4px;">{{ $reportNum }}</p>
      <p style="font-size:9px; color:rgba(255,255,255,0.6);">{{ now()->format('F j, Y') }}</p>
    </td>
  </tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="height:4px; background:#3a7bd5;"></td></tr></table>

{{-- CONTACT --}}
@if($cName || $cCompany || $cEmail)
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb; border-bottom:1px solid #d8dff0;">
  <tr>
    <td width="40%" style="padding:11px 10px 11px 20px; vertical-align:top; border-right:1px solid #d8dff0;">
      <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Prepared For</p>
      @if($cName)    <p style="font-size:12px; font-weight:bold; color:#1e3558;">{{ $cName }}</p>@endif
      @if($cCompany) <p style="font-size:10px; color:#374151; margin-top:2px;">{{ $cCompany }}</p>@endif
      @if($cAddress) <p style="font-size:9px; color:#6b7280; margin-top:2px;">{{ $cAddress }}</p>@endif
    </td>
    <td style="padding:11px 10px; vertical-align:top; border-right:1px solid #d8dff0;">
      @if($cEmail)<p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Email</p><p style="font-size:10px; color:#1e3558;">{{ $cEmail }}</p>@endif
      @if($cPhone)<p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-top:6px; margin-bottom:2px;">Phone</p><p style="font-size:10px; color:#1e3558;">{{ $cPhone }}</p>@endif
    </td>
    <td style="padding:11px 20px 11px 10px; vertical-align:middle; text-align:right;">
      <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Date</p>
      <p style="font-size:10px; color:#1e3558;">{{ now()->format('M j, Y') }}</p>
    </td>
  </tr>
</table>
@endif

{{-- KPI GRID --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:3px solid #1e3558; margin-top:16px;">
  <tr>
    <td width="34%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Hourly Bill Rate</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Total Annual Cost</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Annual Hours Covered</p>
    </td>
  </tr>
  <tr>
    <td style="background:#f5f8ff; padding:20px 16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:44px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $money($hourlyRate) }}</p>
      <p style="font-size:8px; color:#6b7280; margin-top:4px;">estimated hourly service rate</p>
    </td>
    <td style="background:#fde8e0; padding:20px 16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:32px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($totalCost) }}</p>
      <p style="font-size:8px; color:#7a4a3a; margin-top:4px;">annual operating cost</p>
    </td>
    <td style="background:#e0eaf8; padding:20px 16px; text-align:center; border-bottom:3px solid #1e3558;">
      <p style="font-size:32px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $num($annualHours,0) }}</p>
      <p style="font-size:8px; color:#3a5a8a; margin-top:4px;">hours per year</p>
    </td>
  </tr>
</table>

{{-- SERVICE SUMMARY --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Service Summary</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
  @php
    $summary = [
      ['Annual Coverage Hours', $num($annualHours, 0) . ' hrs/yr'],
      ['Annual Hours ÷ Return on Sales', $moneyK($totalRos)],
      ['Return on Sales Rate', $num($rosPct, 2) . '%'],
      ['Annual Operating Cost', $moneyK($totalCost)],
      ['Hourly Bill Rate', $money($hourlyRate)],
    ];
  @endphp
  @foreach($summary as $i => [$label, $val])
  <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151;">{{ $label }}</td>
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $val }}</td>
  </tr>
  @endforeach
</table>

{{-- NARRATIVE --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#f4f6fb; border-left:4px solid #1e3558; padding:12px 16px;">
    <p style="font-size:10px; color:#374151; line-height:1.6;">
      This estimate covers mobile patrol services at an hourly bill rate of <strong style="color:#1e3558;">{{ $money($hourlyRate) }}</strong>
      over <strong style="color:#1e3558;">{{ $num($annualHours,0) }} annual hours</strong>.
      The rate accounts for all labor, vehicle, fuel, maintenance, and insurance costs, plus a
      <strong style="color:#1e3558;">{{ $num($rosPct,1) }}%</strong> return on sales.
      Detailed cost breakdowns are available upon request.
    </p>
  </td></tr>
</table>

{{-- FOOTER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px; border-top:2px solid #1e3558;">
  <tr style="background:#f4f6fb;">
    <td style="padding:10px 20px;">
      <p style="font-size:8.5px; color:#1e3558; font-weight:bold;">GASQ Security</p>
      <p style="font-size:8px; color:#6b7280; margin-top:2px;">{{ $reportNum }} &nbsp;·&nbsp; Buyer Quote &nbsp;·&nbsp; Rates are estimates based on provided inputs</p>
    </td>
    <td style="padding:10px 20px; text-align:right;">
      <p style="font-size:8px; color:#6b7280;">{{ now()->format('M j, Y') }}</p>
    </td>
  </tr>
</table>

</body>
</html>
