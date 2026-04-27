<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GASQ Mobile Patrol – Vendor Report</title>
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

    $money = static fn ($v) => '$' . number_format((float)($v ?? 0), 2);
    $moneyK = static fn ($v) => '$' . number_format((float)($v ?? 0), 0);
    $num   = static fn ($v, $d = 2) => number_format((float)($v ?? 0), $d);

    $rosInput = (float)($scenarioMeta['returnOnSalesPct'] ?? 0);
    $rosRate  = $rosInput > 1 ? $rosInput / 100 : $rosInput;
    $rosPct   = (float)($kpis['returnOnSalesPercentDisplay'] ?? ($rosRate * 100));

    $reportNum = 'GASQ ' . now()->format('Y-m-d') . '-' . str_pad(($reportId ?? rand(1,9999)), 4, '0', STR_PAD_LEFT);
    $logoPath  = 'file://' . public_path('images/site-logo.png');

    $cName    = trim($contact['contactName']    ?? '');
    $cCompany = trim($contact['companyName']    ?? '');
    $cAddress = trim($contact['contactAddress'] ?? '');
    $cEmail   = trim($contact['contactEmail']   ?? '');
    $cPhone   = trim($contact['contactPhone']   ?? '');

    /* KPI values */
    $hourlyRate   = (float)($kpis['costPerHour']                     ?? 0);
    $totalCost    = (float)($kpis['totalAnnualCost']                 ?? 0);
    $rosAmount    = (float)($kpis['returnOnSalesAmount']             ?? 0);
    $totalRos     = (float)($kpis['totalAnnualCostWithReturnOnSales']?? 0);
    $annualHours  = (float)($scenarioMeta['annualHours']             ?? 0);
    $laborCost    = (float)($kpis['annualLaborCost']                 ?? 0);
@endphp

{{-- ═══════════════════════════════════════════════════════ HEADER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff; border-bottom:3px solid #1e3558;">
  <tr>
    <td width="110" style="padding:14px 12px 14px 20px; vertical-align:middle;">
      <img src="{{ $logoPath }}" alt="GASQ" style="width:80px; height:auto; display:block;">
    </td>
    <td style="padding:14px 12px; vertical-align:middle;">
      <p style="font-size:18px; font-weight:bold; color:#1e3558; letter-spacing:0.01em; margin-bottom:4px;">GASQ Mobile Patrol Report</p>
      <p style="font-size:8.5px; color:#6b7280; text-transform:uppercase; letter-spacing:0.12em;">Vendor Cost Analysis &amp; Hourly Bill Rate</p>
    </td>
    <td style="padding:14px 20px 14px 12px; vertical-align:middle; text-align:right;">
      <p style="font-size:11px; font-weight:bold; color:#1e3558; margin-bottom:4px;">{{ $reportNum }}</p>
      <p style="font-size:9px; color:#6b7280;">{{ now()->format('F j, Y') }}</p>
    </td>
  </tr>
</table>

{{-- ═══════════════════════════════════════════════════════ CONTACT --}}
@if($cName || $cCompany || $cEmail || $cPhone)
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb; border-bottom:1px solid #d8dff0;">
  <tr>
    @if($cName || $cCompany)
    <td width="33%" style="padding:11px 10px 11px 20px; vertical-align:top; border-right:1px solid #d8dff0;">
      @if($cName) <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Vendor Contact</p><p style="font-size:11px; font-weight:bold; color:#1e3558;">{{ $cName }}</p>@endif
      @if($cCompany)<p style="font-size:10px; color:#374151; margin-top:3px;">{{ $cCompany }}</p>@endif
      @if($cAddress)<p style="font-size:9px; color:#6b7280; margin-top:2px;">{{ $cAddress }}</p>@endif
    </td>
    @endif
    <td style="padding:11px 10px; vertical-align:top; border-right:1px solid #d8dff0;">
      @if($cEmail)<p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Email</p><p style="font-size:10px; color:#1e3558;">{{ $cEmail }}</p>@endif
      @if($cPhone)<p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-top:6px; margin-bottom:2px;">Phone</p><p style="font-size:10px; color:#1e3558;">{{ $cPhone }}</p>@endif
    </td>
    <td style="padding:11px 20px 11px 10px; vertical-align:top; text-align:right;">
      <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Report Type</p>
      <p style="font-size:10px; color:#1e3558; font-weight:bold;">Vendor — Full Report</p>
      <p style="font-size:8px; color:#6b7280; margin-top:8px;">Generated {{ now()->format('M j, Y g:i A') }}</p>
    </td>
  </tr>
</table>
@endif

{{-- ═══════════════════════════════════════════════════════ KPI GRID ROW 1 --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:3px solid #1e3558; margin-top:16px;">
  {{-- Label row --}}
  <tr>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Hourly Bill Rate</p>
    </td>
    <td width="34%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Total Annual Cost</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Return on Sales Amount</p>
    </td>
  </tr>
  {{-- Value row --}}
  <tr>
    <td style="background:#f5f8ff; padding:16px; text-align:center; border-right:2px solid #ffffff; border-bottom:2px solid #1e3558;">
      <p style="font-size:36px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $money($hourlyRate) }}</p>
      <p style="font-size:8px; color:#6b7280; margin-top:3px;">per hour</p>
    </td>
    <td style="background:#fde8e0; padding:16px; text-align:center; border-right:2px solid #ffffff; border-bottom:2px solid #1e3558;">
      <p style="font-size:36px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($totalCost) }}</p>
      <p style="font-size:8px; color:#7a4a3a; margin-top:3px;">annual operating total</p>
    </td>
    <td style="background:#e8f5eb; padding:16px; text-align:center; border-bottom:2px solid #1e3558;">
      <p style="font-size:36px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($rosAmount) }}</p>
      <p style="font-size:8px; color:#2d6a4f; margin-top:3px;">at {{ $num($rosPct,1) }}% return</p>
    </td>
  </tr>
</table>

{{-- KPI GRID ROW 2 --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:2px;">
  <tr>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Return on Sales %</p>
    </td>
    <td width="34%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Total Cost + Return on Sales</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Annual Hours</p>
    </td>
  </tr>
  <tr>
    <td style="background:#ede9f6; padding:14px 16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:32px; font-weight:bold; color:#1e3558;">{{ $num($rosPct,1) }}%</p>
      <p style="font-size:8px; color:#6b7280; margin-top:3px;">selected profit return</p>
    </td>
    <td style="background:#fdeae4; padding:14px 16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:32px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($totalRos) }}</p>
      <p style="font-size:8px; color:#7a4a3a; margin-top:3px;">basis for bill rate</p>
    </td>
    <td style="background:#e0eaf8; padding:14px 16px; text-align:center; border-bottom:3px solid #1e3558;">
      <p style="font-size:32px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $num($annualHours,0) }}</p>
      <p style="font-size:8px; color:#3a5a8a; margin-top:3px;">hours per year</p>
    </td>
  </tr>
</table>

{{-- ═══════════════════════════════════════════════════════ CORE ASSUMPTIONS --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Core Assumptions</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
  @php
    $assumptions = [
      ['Baseline Hourly Pay Rate',        $money($scenarioMeta['baselinePayRate'] ?? 0)],
      ['Divisor',                          $num($scenarioMeta['divisor'] ?? 0, 2)],
      ['Annual Hours',                     $num($scenarioMeta['annualHours'] ?? 0, 0)],
      ['Driving Speed (MPH)',              $num($scenarioMeta['mph'] ?? 0, 0)],
      ['Hours Per Day',                    $num($scenarioMeta['hoursPerDay'] ?? 0, 0)],
      ['Miles Per Gallon',                 $num($scenarioMeta['mpg'] ?? 0, 0)],
      ['Fuel Cost Per Gallon',             $money($scenarioMeta['fuelCostPerGallon'] ?? 0)],
      ['Annual Maintenance / Repair',      $money($scenarioMeta['annualMaintenance'] ?? 0)],
      ['Tire Sets Per Year',               $num($scenarioMeta['tireSetsPerYear'] ?? 0, 0)],
      ['Tire Cost Per Set',                $money($scenarioMeta['tireCostPerSet'] ?? 0)],
      ['Auto Lease & Insurance',           $money($scenarioMeta['autoInsurance'] ?? 0)],
      ['Oil Change Interval (Miles)',      $num($scenarioMeta['oilChangeIntervalMiles'] ?? 0, 0)],
      ['Oil Change Cost',                  $money($scenarioMeta['oilChangeCost'] ?? 0)],
      ['Return on Sales %',                $num($rosPct, 2) . '%'],
    ];
  @endphp
  @foreach($assumptions as $i => [$label, $val])
  <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
    <td style="padding:7px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151;">{{ $label }}</td>
    <td style="padding:7px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $val }}</td>
  </tr>
  @endforeach
</table>

{{-- ═══════════════════════════════════════════════════════ CALCULATED RESULTS --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Calculated Results</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
  @php
    $results = [
      ['1. Employer Cost Per Hour',                    $money($kpis['employerCostHourly'] ?? 0),          false, false],
      ['2. Annual Labor Cost',                         $money($kpis['annualLaborCost'] ?? 0),              false, false],
      ['3. Miles Per Day',                             $num($kpis['milesPerDay'] ?? 0, 0),                 false, false],
      ['4. Miles Per Year',                            $num($kpis['milesPerYear'] ?? 0, 0),                false, false],
      ['5. Gallons Per Year',                          $num($kpis['gallonsPerYear'] ?? 0, 0),              false, false],
      ['6. Annual Fuel Cost',                          $money($kpis['annualFuelCost'] ?? 0),               false, false],
      ['7. Annual Maintenance / Repair',               $money($scenarioMeta['annualMaintenance'] ?? 0),    false, false],
      ['8. Annual Tire Cost',                          $money($kpis['annualTireCost'] ?? 0),               false, false],
      ['9. Auto Lease & Insurance',                    $money($scenarioMeta['autoInsurance'] ?? 0),        false, false],
      ['10. Oil Changes / Year',                       $num($kpis['oilChangesPerYear'] ?? 0, 0) . '  (' . $money($kpis['annualOilCost'] ?? 0) . ')', false, false],
      ['11. Total Annual Cost',                        $money($kpis['totalAnnualCost'] ?? 0),              true, false],
      ['12. Return on Sales Amount (' . $num($rosPct,2) . '%)', $money($kpis['returnOnSalesAmount'] ?? 0), false, false],
      ['13. Total Annual Cost + Return on Sales',      $money($kpis['totalAnnualCostWithReturnOnSales'] ?? 0), true, false],
      ['14. Hourly Bill Rate',                         $money($kpis['costPerHour'] ?? 0),                  false, true],
    ];
  @endphp
  @foreach($results as $i => [$label, $val, $isDark, $isGreen])
  @if($isDark)
  <tr style="background:#1e3558;">
    <td style="padding:9px 16px; font-size:10.5px; color:#ffffff; font-weight:bold;">{{ $label }}</td>
    <td style="padding:9px 16px; font-size:11px; color:#ffffff; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $val }}</td>
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

{{-- ═══════════════════════════════════════════════════════ FOOTER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px; border-top:2px solid #1e3558;">
  <tr style="background:#f4f6fb;">
    <td style="padding:10px 20px;">
      <p style="font-size:8.5px; color:#1e3558; font-weight:bold;">GASQ Security</p>
      <p style="font-size:8px; color:#6b7280; margin-top:2px;">{{ $reportNum }} &nbsp;·&nbsp; Vendor Report &nbsp;·&nbsp; Confidential – for authorized recipients only</p>
    </td>
    <td style="padding:10px 20px; text-align:right;">
      <p style="font-size:8px; color:#6b7280;">{{ now()->format('M j, Y') }}</p>
    </td>
  </tr>
</table>

</body>
</html>
