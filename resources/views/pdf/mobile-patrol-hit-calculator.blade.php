<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GASQ Mobile Patrol Hit Service Report</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 0; background: #fff; }
    table { border-collapse: collapse; }
    td, th { padding: 0; margin: 0; }
    p { margin: 0; padding: 0; }
</style>
</head>
<body>
@php
    $meta    = (array) ($scenario['meta']    ?? []);
    $contact = (array) ($scenario['contact'] ?? []);
    $kpis    = (array) ($result['kpis']      ?? $result ?? []);

    $money  = static fn ($v) => '$' . number_format((float)($v ?? 0), 2);
    $moneyK = static fn ($v) => '$' . number_format((float)($v ?? 0), 0);
    $num    = static fn ($v, $d = 2) => number_format((float)($v ?? 0), $d);
    $pct    = static fn ($v) => number_format((float)($v ?? 0) * 100, 2) . '%';

    $reportNum  = 'GASQ ' . now()->format('Y-m-d') . '-' . str_pad(($reportId ?? rand(1,9999)), 4, '0', STR_PAD_LEFT);
    $logoPath   = 'file://' . public_path('img/gasq-logo.png');

    $siteName   = trim($meta['siteName']         ?? $contact['siteName']    ?? '');
    $svcType    = trim($meta['serviceType']       ?? '');
    $cName      = trim($contact['contactName']    ?? $meta['contactName']   ?? '');
    $cCompany   = trim($contact['companyName']    ?? $meta['companyName']   ?? '');
    $cAddress   = trim($contact['contactAddress'] ?? $meta['contactAddress']?? '');
    $cEmail     = trim($contact['contactEmail']   ?? $meta['contactEmail']  ?? '');
    $cPhone     = trim($contact['contactPhone']   ?? $meta['contactPhone']  ?? '');

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
@endphp

{{-- HEADER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#1e3558;">
  <tr>
    <td width="90" style="padding:16px 12px 16px 20px; vertical-align:middle;">
      <img src="{{ $logoPath }}" alt="GASQ" style="width:70px; height:auto; display:block;">
    </td>
    <td style="padding:16px 12px; vertical-align:middle;">
      <p style="font-size:17px; font-weight:bold; color:#ffffff; letter-spacing:0.03em; margin-bottom:3px;">GASQ Mobile Patrol Hit Service Report</p>
      <p style="font-size:9px; color:rgba(255,255,255,0.65); text-transform:uppercase; letter-spacing:0.1em;">Per-Check Pricing &amp; Revenue Analysis</p>
    </td>
    <td style="padding:16px 20px 16px 12px; vertical-align:middle; text-align:right;">
      <p style="font-size:10px; font-weight:bold; color:#ffffff; margin-bottom:4px;">{{ $reportNum }}</p>
      <p style="font-size:9px; color:rgba(255,255,255,0.6);">{{ now()->format('F j, Y') }}</p>
    </td>
  </tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="height:4px; background:#3a7bd5;"></td></tr></table>

{{-- CONTACT / SITE --}}
@if($siteName || $cName || $cCompany || $cEmail)
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb; border-bottom:1px solid #d8dff0;">
  <tr>
    <td width="33%" style="padding:11px 10px 11px 20px; vertical-align:top; border-right:1px solid #d8dff0;">
      @if($siteName)<p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Site</p><p style="font-size:12px; font-weight:bold; color:#1e3558;">{{ $siteName }}</p>@endif
      @if($svcType) <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-top:6px; margin-bottom:2px;">Service Type</p><p style="font-size:10px; color:#374151;">{{ $svcType }}</p>@endif
    </td>
    <td width="33%" style="padding:11px 10px; vertical-align:top; border-right:1px solid #d8dff0;">
      @if($cName)    <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Prepared For</p><p style="font-size:11px; font-weight:bold; color:#1e3558;">{{ $cName }}</p>@endif
      @if($cCompany) <p style="font-size:10px; color:#374151; margin-top:2px;">{{ $cCompany }}</p>@endif
      @if($cAddress) <p style="font-size:9px; color:#6b7280; margin-top:2px;">{{ $cAddress }}</p>@endif
    </td>
    <td style="padding:11px 20px 11px 10px; vertical-align:top;">
      @if($cEmail) <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Email</p><p style="font-size:10px; color:#1e3558;">{{ $cEmail }}</p>@endif
      @if($cPhone) <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-top:6px; margin-bottom:2px;">Phone</p><p style="font-size:10px; color:#1e3558;">{{ $cPhone }}</p>@endif
      <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-top:6px; margin-bottom:2px;">Date</p>
      <p style="font-size:10px; color:#1e3558;">{{ now()->format('M j, Y') }}</p>
    </td>
  </tr>
</table>
@endif

{{-- KPI GRID ROW 1 --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:3px solid #1e3558; margin-top:16px;">
  <tr>
    <td width="34%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Final Price Per Check</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Annual Revenue</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Profit Margin</p>
    </td>
  </tr>
  <tr>
    <td style="background:#f5f8ff; padding:16px; text-align:center; border-right:2px solid #ffffff; border-bottom:2px solid #1e3558;">
      <p style="font-size:36px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $money($finalPrice) }}</p>
      <p style="font-size:8px; color:#6b7280; margin-top:3px;">per patrol check</p>
    </td>
    <td style="background:#fde8e0; padding:16px; text-align:center; border-right:2px solid #ffffff; border-bottom:2px solid #1e3558;">
      <p style="font-size:36px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($annualRevenue) }}</p>
      <p style="font-size:8px; color:#7a4a3a; margin-top:3px;">{{ $num($annualChecks,0) }} annual checks</p>
    </td>
    <td style="background:#e8f5eb; padding:16px; text-align:center; border-bottom:2px solid #1e3558;">
      <p style="font-size:36px; font-weight:bold; color:#1e3558;">{{ number_format($profitMarginPct * 100, 1) }}%</p>
      <p style="font-size:8px; color:#2d6a4f; margin-top:3px;">gross profit margin</p>
    </td>
  </tr>
</table>

{{-- KPI GRID ROW 2 --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:2px;">
  <tr>
    <td width="34%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Weekly Revenue</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Monthly Revenue</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Weekly Checks</p>
    </td>
  </tr>
  <tr>
    <td style="background:#fdeae4; padding:14px 16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:28px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($weeklyRevenue) }}</p>
      <p style="font-size:8px; color:#7a4a3a; margin-top:3px;">{{ $weeklyChecks }} checks/week</p>
    </td>
    <td style="background:#ede9f6; padding:14px 16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:28px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($monthlyRevenue) }}</p>
      <p style="font-size:8px; color:#4a3a7a; margin-top:3px;">{{ $num($monthlyChecks,1) }} checks/month</p>
    </td>
    <td style="background:#e0eaf8; padding:14px 16px; text-align:center; border-bottom:3px solid #1e3558;">
      <p style="font-size:28px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $weeklyChecks }}</p>
      <p style="font-size:8px; color:#3a5a8a; margin-top:3px;">{{ (int)$weeksPerYear }} weeks per year</p>
    </td>
  </tr>
</table>

{{-- CORE ASSUMPTIONS --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Core Assumptions</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
  @php
    $assumptions = [
      ['Service Type',               $svcType ?: '—'],
      ['Weekly Checks',              $num($weeklyChecks, 0)],
      ['Weeks Per Year',             $num($weeksPerYear, 0)],
      ['Checks Per Day',             $num($kpis['checksPerDay'] ?? ($weeklyChecks / 7), 2)],
      ['Minutes On Site Per Check',  $num($meta['minutesOnSite'] ?? 0, 0) . ' min'],
      ['Travel / Dispatch Minutes',  $num($meta['minutesTravel'] ?? 0, 0) . ' min'],
      ['Total Billable Minutes',     $num($kpis['totalMinutes'] ?? 0, 0) . ' min'],
      ['Hours Per Check',            $num($kpis['hoursPerCheck'] ?? 0, 4) . ' hrs'],
      ['Officer Pay Rate',           $money($meta['officerPayRate'] ?? 0) . '/hr'],
      ['Payroll Burden %',           $num($meta['payrollBurdenPct'] ?? 0, 0) . '%'],
      ['Vehicle Cost / Hr',          $money($meta['vehicleCostPerHour'] ?? 0)],
      ['Fuel Cost / Hr',             $money($meta['fuelCostPerHour'] ?? 0)],
      ['Equipment / Tech / Hr',      $money($meta['equipmentCostPerHour'] ?? 0)],
      ['Supervision / Admin / Hr',   $money($meta['supervisionCostPerHour'] ?? 0)],
      ['Overhead %',                 $num($meta['overheadPct'] ?? 0, 0) . '%'],
      ['G&A %',                      $num($meta['gaPct'] ?? 0, 0) . '%'],
      ['Profit / Markup %',          $num($profitPct, 0) . '%'],
      ['Minimum Charge Per Check',   $money($meta['minimumCharge'] ?? 0)],
    ];
  @endphp
  @foreach($assumptions as $i => [$label, $val])
  <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
    <td style="padding:6px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151;">{{ $label }}</td>
    <td style="padding:6px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $val }}</td>
  </tr>
  @endforeach
</table>

{{-- PER-CHECK COST BREAKDOWN --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Per-Check Cost Breakdown</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
  @php
    $breakdown = [
      ['Fully Burdened Labor Rate',  $money($kpis['burdenedRate'] ?? 0) . '/hr',    false, false],
      ['Total Operating Cost / Hr',  $money($kpis['totalOpCostPerHour'] ?? 0) . '/hr', false, false],
      ['Base Cost Per Check',        $money($kpis['baseCostPerCheck'] ?? 0),          false, false],
      ['Overhead Per Check',         $money($kpis['overheadPerCheck'] ?? 0),          false, false],
      ['G&A Per Check',              $money($kpis['gaPerCheck'] ?? 0),                false, false],
      ['Subtotal Cost Per Check',    $money($kpis['subtotalCostPerCheck'] ?? 0),      true,  false],
      ['Add-On Per Check',           $money($meta['addOnCost'] ?? 0),                 false, false],
      ['Pre-Markup Cost Per Check',  $money($kpis['preMkupCostPerCheck'] ?? 0),       false, false],
      ['Profit Amount Per Check',    $money($kpis['profitAmountPerCheck'] ?? 0),      false, false],
      ['Final Sell Price Per Check', $money($finalPrice),                              false, true],
    ];
  @endphp
  @foreach($breakdown as $i => [$label, $val, $isDark, $isGreen])
  @if($isDark)
  <tr style="background:#1e3558;">
    <td style="padding:8px 16px; font-size:10.5px; color:#ffffff; font-weight:bold;">{{ $label }}</td>
    <td style="padding:8px 16px; font-size:10.5px; color:#ffffff; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $val }}</td>
  </tr>
  @elseif($isGreen)
  <tr style="background:#e8f5eb;">
    <td style="padding:9px 16px; font-size:11px; color:#1e3558; font-weight:bold; border-top:2px solid #1e3558;">{{ $label }}</td>
    <td style="padding:9px 16px; font-size:13px; color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums; border-top:2px solid #1e3558;">{{ $val }}</td>
  </tr>
  @else
  <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
    <td style="padding:7px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151;">{{ $label }}</td>
    <td style="padding:7px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $val }}</td>
  </tr>
  @endif
  @endforeach
</table>

{{-- BAND LOGIC --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Band Logic</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none; background:#f4f6fb;">
  <tr><td style="padding:12px 16px;">
    <p style="font-size:10px; color:#374151; margin-bottom:5px;">· Below 5.0%: thin pricing / elevated execution risk.</p>
    <p style="font-size:10px; color:#374151; margin-bottom:5px;">· 5.0% to 10.0%: standard award zone for patrol check services.</p>
    <p style="font-size:10px; color:#374151;">· Above 10.0%: premium pricing; justify with complexity, specialization, or risk.</p>
  </td>
  <td style="padding:12px 16px; text-align:right; vertical-align:top;">
    @php
      $band = $profitMarginPct * 100;
      $bandLabel = $band < 5 ? 'Thin / Elevated Risk' : ($band <= 10 ? 'Standard Award Zone' : 'Premium Pricing');
      $bandColor = $band < 5 ? '#c53030' : ($band <= 10 ? '#276749' : '#c05621');
    @endphp
    <p style="font-size:8px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:3px;">Current Band</p>
    <p style="font-size:13px; font-weight:bold; color:{{ $bandColor }};">{{ $bandLabel }}</p>
    <p style="font-size:11px; color:#1e3558; font-weight:bold; margin-top:2px;">{{ number_format($band, 1) }}% margin</p>
  </td></tr>
</table>

{{-- EXECUTIVE NARRATIVE --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px;">
  <tr><td style="background:#f4f6fb; border-left:4px solid #1e3558; padding:11px 16px;">
    <p style="font-size:10px; color:#374151; line-height:1.6;">
      This mobile patrol estimate is based on <strong style="color:#1e3558;">{{ $weeklyChecks }} weekly checks</strong>
      over <strong style="color:#1e3558;">{{ (int)$weeksPerYear }} weeks per year</strong>{{ $siteName ? " at <strong style=\"color:#1e3558;\">{$siteName}</strong>" : '' }}.
      The selected service type is <strong style="color:#1e3558;">{{ $svcType ?: 'N/A' }}</strong>.
      The final sell price of <strong style="color:#1e3558;">{{ $money($finalPrice) }} per check</strong>
      reflects fully burdened labor, vehicle, fuel, equipment and supervision costs plus overhead, G&amp;A, and a
      <strong style="color:#1e3558;">{{ $num($profitPct, 0) }}% profit markup</strong>,
      yielding total annual revenue of <strong style="color:#1e3558;">{{ $moneyK($annualRevenue) }}</strong>.
      Created in collaboration with the GASQ AI Team.
    </p>
  </td></tr>
</table>

{{-- FOOTER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px; border-top:2px solid #1e3558;">
  <tr style="background:#f4f6fb;">
    <td style="padding:10px 20px;">
      <p style="font-size:8.5px; color:#1e3558; font-weight:bold;">GASQ Security</p>
      <p style="font-size:8px; color:#6b7280; margin-top:2px;">{{ $reportNum }} &nbsp;·&nbsp; Hit Service Report &nbsp;·&nbsp; Rates are estimates based on provided inputs</p>
    </td>
    <td style="padding:10px 20px; text-align:right;">
      <p style="font-size:8px; color:#6b7280;">{{ now()->format('M j, Y') }}</p>
    </td>
  </tr>
</table>

</body>
</html>
