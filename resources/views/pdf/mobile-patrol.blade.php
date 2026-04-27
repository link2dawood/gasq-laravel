<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Mobile Patrol – Vendor Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }

        /* ── Header ─────────────────────────────────────────── */
        .header {
            background: #062d79;
            color: #fff;
            padding: 18px 24px;
            display: table;
            width: 100%;
        }
        .header-logo { display: table-cell; vertical-align: middle; width: 100px; }
        .header-logo img { width: 80px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 14px; }
        .header-title { font-size: 16px; font-weight: bold; letter-spacing: 0.02em; }
        .header-sub { font-size: 10px; color: rgba(255,255,255,0.72); margin-top: 3px; }
        .header-meta { display: table-cell; vertical-align: middle; text-align: right; font-size: 9px; color: rgba(255,255,255,0.65); white-space: nowrap; }
        .report-number { font-size: 11px; font-weight: bold; color: #fff; }

        /* ── Accent bar ──────────────────────────────────────── */
        .accent-bar { height: 4px; background: linear-gradient(90deg, #062d79 0%, #1a56db 100%); }

        /* ── Contact block ──────────────────────────────────── */
        .contact-block { padding: 14px 24px; background: #f3f5f8; border-bottom: 1px solid #dde3ed; display: table; width: 100%; }
        .contact-col { display: table-cell; vertical-align: top; width: 50%; padding-right: 16px; }
        .contact-col:last-child { padding-right: 0; }
        .contact-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; font-weight: bold; margin-bottom: 3px; }
        .contact-value { font-size: 11px; color: #111827; font-weight: bold; }
        .contact-value-sm { font-size: 10px; color: #374151; }

        /* ── Body ──────────────────────────────────────────── */
        .body { padding: 20px 24px; }

        /* ── Section heading ─────────────────────────────────── */
        .section-heading {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #062d79;
            border-bottom: 2px solid #062d79;
            padding-bottom: 5px;
            margin: 18px 0 10px;
        }
        .section-heading:first-child { margin-top: 0; }

        /* ── Tables ─────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        td, th { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #062d79; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: bold; }
        tr:nth-child(even) td { background: #f9fafb; }

        .row-total td { font-weight: bold; background: #062d79 !important; color: #fff; font-size: 11px; }
        .row-subtotal td { font-weight: bold; background: #1a3a6b !important; color: #fff; }
        .row-highlight td { background: #ecfdf3 !important; color: #065f46; font-weight: bold; font-size: 12px; }

        td.val { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }

        /* ── Footer ─────────────────────────────────────────── */
        .footer { margin-top: 24px; padding: 12px 24px; background: #f3f5f8; border-top: 2px solid #062d79; text-align: center; font-size: 9px; color: #6b7280; }
        .footer strong { color: #062d79; }

        /* ── Badge ──────────────────────────────────────────── */
        .badge-vendor { display: inline-block; background: #062d79; color: #fff; font-size: 8px; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; padding: 2px 7px; border-radius: 3px; margin-left: 8px; vertical-align: middle; }
    </style>
</head>
<body>
@php
    $scenarioMeta    = (array) ($scenario['meta'] ?? []);
    $contact         = (array) ($scenario['contact'] ?? []);
    $kpis            = (array) ($result['kpis'] ?? $result ?? []);

    $money  = static fn ($v) => '$' . number_format((float) ($v ?? 0), 2);
    $num    = static fn ($v, $d = 2) => number_format((float) ($v ?? 0), $d);

    $rosInput   = (float) ($scenarioMeta['returnOnSalesPct'] ?? 0);
    $rosRate    = $rosInput > 1 ? ($rosInput / 100) : $rosInput;
    $rosPct     = (float) ($kpis['returnOnSalesPercentDisplay'] ?? ($rosRate * 100));

    $reportNum  = 'GASQ ' . now()->format('Y-m-d') . '-' . str_pad(($reportId ?? rand(1, 9999)), 4, '0', STR_PAD_LEFT);
    $logoPath   = 'file://' . public_path('img/gasq-logo.png');

    $cName      = trim($contact['contactName']    ?? '');
    $cCompany   = trim($contact['companyName']    ?? '');
    $cAddress   = trim($contact['contactAddress'] ?? '');
    $cEmail     = trim($contact['contactEmail']   ?? '');
    $cPhone     = trim($contact['contactPhone']   ?? '');
@endphp

{{-- Header --}}
<div class="header">
    <div class="header-logo">
        <img src="{{ $logoPath }}" alt="GASQ">
    </div>
    <div class="header-info">
        <div class="header-title">Mobile Patrol Calculator <span class="badge-vendor">Vendor</span></div>
        <div class="header-sub">Full Cost Analysis &amp; Hourly Bill Rate</div>
    </div>
    <div class="header-meta">
        <div class="report-number">{{ $reportNum }}</div>
        <div style="margin-top:4px;">{{ now()->format('M j, Y') }}</div>
        <div>{{ now()->format('g:i A') }}</div>
    </div>
</div>
<div class="accent-bar"></div>

{{-- Contact block --}}
@if($cName || $cCompany || $cAddress || $cEmail || $cPhone)
<div class="contact-block">
    <div class="contact-col">
        @if($cName)    <div class="contact-label">Contact</div><div class="contact-value">{{ $cName }}</div> @endif
        @if($cCompany) <div style="margin-top:6px;"><div class="contact-label">Company</div><div class="contact-value">{{ $cCompany }}</div></div> @endif
        @if($cAddress) <div style="margin-top:6px;"><div class="contact-label">Address</div><div class="contact-value-sm">{{ $cAddress }}</div></div> @endif
    </div>
    <div class="contact-col">
        @if($cEmail) <div class="contact-label">Email</div><div class="contact-value-sm">{{ $cEmail }}</div> @endif
        @if($cPhone) <div style="margin-top:6px;"><div class="contact-label">Phone</div><div class="contact-value-sm">{{ $cPhone }}</div></div> @endif
    </div>
</div>
@endif

<div class="body">

    {{-- Input Summary --}}
    <div class="section-heading">Input Summary</div>
    <table>
        <tr><th>Field</th><th class="val">Value</th></tr>
        <tr><td>Baseline hourly pay rate</td><td class="val">{{ $money($scenarioMeta['baselinePayRate'] ?? 0) }}</td></tr>
        <tr><td>Divisor</td><td class="val">{{ $num($scenarioMeta['divisor'] ?? 0, 2) }}</td></tr>
        <tr><td>Annual hours</td><td class="val">{{ $num($scenarioMeta['annualHours'] ?? 0, 0) }}</td></tr>
        <tr><td>Driving speed (MPH)</td><td class="val">{{ $num($scenarioMeta['mph'] ?? 0, 0) }}</td></tr>
        <tr><td>Hours per day</td><td class="val">{{ $num($scenarioMeta['hoursPerDay'] ?? 0, 0) }}</td></tr>
        <tr><td>Miles per gallon</td><td class="val">{{ $num($scenarioMeta['mpg'] ?? 0, 0) }}</td></tr>
        <tr><td>Fuel cost per gallon</td><td class="val">{{ $money($scenarioMeta['fuelCostPerGallon'] ?? 0) }}</td></tr>
        <tr><td>Annual maintenance / repair</td><td class="val">{{ $money($scenarioMeta['annualMaintenance'] ?? 0) }}</td></tr>
        <tr><td>Tire sets per year</td><td class="val">{{ $num($scenarioMeta['tireSetsPerYear'] ?? 0, 0) }}</td></tr>
        <tr><td>Tire cost per set</td><td class="val">{{ $money($scenarioMeta['tireCostPerSet'] ?? 0) }}</td></tr>
        <tr><td>Auto lease &amp; insurance</td><td class="val">{{ $money($scenarioMeta['autoInsurance'] ?? 0) }}</td></tr>
        <tr><td>Oil change interval (miles)</td><td class="val">{{ $num($scenarioMeta['oilChangeIntervalMiles'] ?? 0, 0) }}</td></tr>
        <tr><td>Oil change cost</td><td class="val">{{ $money($scenarioMeta['oilChangeCost'] ?? 0) }}</td></tr>
        <tr><td>Return on sales %</td><td class="val">{{ $num($rosPct, 2) }}%</td></tr>
    </table>

    {{-- Calculated Results --}}
    <div class="section-heading">Calculated Results</div>
    <table>
        <tr><th>Metric</th><th class="val">Value</th></tr>
        <tr><td>1. Employer cost per hour</td><td class="val">{{ $money($kpis['employerCostHourly'] ?? 0) }}</td></tr>
        <tr><td>2. Annual labor cost</td><td class="val">{{ $money($kpis['annualLaborCost'] ?? 0) }}</td></tr>
        <tr><td>3. Miles per day</td><td class="val">{{ $num($kpis['milesPerDay'] ?? 0, 0) }}</td></tr>
        <tr><td>4. Miles per year</td><td class="val">{{ $num($kpis['milesPerYear'] ?? 0, 0) }}</td></tr>
        <tr><td>5. Gallons per year</td><td class="val">{{ $num($kpis['gallonsPerYear'] ?? 0, 0) }}</td></tr>
        <tr><td>6. Annual fuel cost</td><td class="val">{{ $money($kpis['annualFuelCost'] ?? 0) }}</td></tr>
        <tr><td>7. Annual maintenance / repair</td><td class="val">{{ $money($scenarioMeta['annualMaintenance'] ?? 0) }}</td></tr>
        <tr><td>8. Annual tire cost</td><td class="val">{{ $money($kpis['annualTireCost'] ?? 0) }}</td></tr>
        <tr><td>9. Auto lease &amp; insurance</td><td class="val">{{ $money($scenarioMeta['autoInsurance'] ?? 0) }}</td></tr>
        <tr><td>10. Oil changes / year</td><td class="val">{{ $num($kpis['oilChangesPerYear'] ?? 0, 0) }} ({{ $money($kpis['annualOilCost'] ?? 0) }})</td></tr>
        <tr class="row-total"><td>11. Total annual cost</td><td class="val">{{ $money($kpis['totalAnnualCost'] ?? 0) }}</td></tr>
        <tr><td>12. Return on sales amount ({{ $num($rosPct, 2) }}%)</td><td class="val">{{ $money($kpis['returnOnSalesAmount'] ?? 0) }}</td></tr>
        <tr class="row-subtotal"><td>13. Total annual cost + return on sales</td><td class="val">{{ $money($kpis['totalAnnualCostWithReturnOnSales'] ?? 0) }}</td></tr>
        <tr class="row-highlight"><td>14. Hourly bill rate</td><td class="val">{{ $money($kpis['costPerHour'] ?? 0) }}</td></tr>
    </table>

</div>

<div class="footer">
    <strong>GASQ Security</strong> &nbsp;|&nbsp; Mobile Patrol Vendor Report &nbsp;|&nbsp; {{ $reportNum }} &nbsp;|&nbsp; Confidential – for authorized recipients only
</div>
</body>
</html>
