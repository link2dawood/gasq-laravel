<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Mobile Patrol Hit Service Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }

        /* ── Header ─────────────────────────────────────────── */
        .header { background: #062d79; color: #fff; padding: 18px 24px; display: table; width: 100%; }
        .header-logo { display: table-cell; vertical-align: middle; width: 100px; }
        .header-logo img { width: 80px; height: auto; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 14px; }
        .header-title { font-size: 16px; font-weight: bold; letter-spacing: 0.02em; }
        .header-sub { font-size: 10px; color: rgba(255,255,255,0.72); margin-top: 3px; }
        .header-meta { display: table-cell; vertical-align: middle; text-align: right; font-size: 9px; color: rgba(255,255,255,0.65); white-space: nowrap; }
        .report-number { font-size: 11px; font-weight: bold; color: #fff; }

        .accent-bar { height: 4px; background: linear-gradient(90deg, #062d79 0%, #1a56db 100%); }

        /* ── Contact / site block ────────────────────────────── */
        .contact-block { padding: 14px 24px; background: #f3f5f8; border-bottom: 1px solid #dde3ed; display: table; width: 100%; }
        .contact-col { display: table-cell; vertical-align: top; width: 33%; padding-right: 12px; }
        .contact-col:last-child { padding-right: 0; }
        .cl { font-size: 8px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; font-weight: bold; margin-bottom: 3px; }
        .cv { font-size: 11px; color: #111827; font-weight: bold; }
        .cv-sm { font-size: 10px; color: #374151; }

        /* ── Hero price box ──────────────────────────────────── */
        .price-hero { text-align: center; padding: 24px; background: #f0f5ff; border-bottom: 2px solid #dde3ed; }
        .price-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; font-weight: bold; }
        .price-value { font-size: 48px; font-weight: bold; color: #062d79; letter-spacing: -0.02em; }
        .price-sub { font-size: 10px; color: #6b7280; margin-top: 4px; }

        /* ── Body ──────────────────────────────────────────── */
        .body { padding: 18px 24px; }

        /* ── Section heading ─────────────────────────────────── */
        .sh {
            font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.07em;
            color: #062d79; border-bottom: 2px solid #062d79; padding-bottom: 4px; margin: 16px 0 8px;
        }
        .sh:first-child { margin-top: 0; }

        /* ── Tables ─────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        td, th { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #062d79; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: bold; }
        tr:nth-child(even) td { background: #f9fafb; }

        .row-dark td { background: #062d79 !important; color: #fff; font-weight: bold; font-size: 11px; }
        .row-mid td { background: #1a3a6b !important; color: #fff; font-weight: bold; }
        .row-green td { background: #ecfdf3 !important; color: #065f46; font-weight: bold; font-size: 12px; }

        td.val { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        td.bold { font-weight: bold; }

        /* ── Two-column layout ───────────────────────────────── */
        .two-col { display: table; width: 100%; }
        .col-left { display: table-cell; width: 48%; vertical-align: top; padding-right: 10px; }
        .col-right { display: table-cell; width: 52%; vertical-align: top; padding-left: 10px; }

        /* ── Narrative ───────────────────────────────────────── */
        .narrative { background: #f3f5f8; border-left: 3px solid #062d79; padding: 10px 14px; font-size: 10px; color: #374151; line-height: 1.5; margin-top: 14px; }

        /* ── Footer ─────────────────────────────────────────── */
        .footer { margin-top: 20px; padding: 10px 24px; background: #f3f5f8; border-top: 2px solid #062d79; text-align: center; font-size: 9px; color: #6b7280; }
        .footer strong { color: #062d79; }
    </style>
</head>
<body>
@php
    $meta    = (array) ($scenario['meta']    ?? []);
    $contact = (array) ($scenario['contact'] ?? []);
    $kpis    = (array) ($result['kpis']      ?? $result ?? []);

    $money  = static fn ($v) => '$' . number_format((float) ($v ?? 0), 2);
    $num    = static fn ($v, $d = 2) => number_format((float) ($v ?? 0), $d);
    $pct    = static fn ($v) => number_format((float) ($v ?? 0) * 100, 2) . '%';

    $reportNum  = 'GASQ ' . now()->format('Y-m-d') . '-' . str_pad(($reportId ?? rand(1, 9999)), 4, '0', STR_PAD_LEFT);
    $logoPath   = 'file://' . public_path('img/gasq-logo.png');

    $siteName   = trim($meta['siteName']            ?? $contact['siteName']    ?? '');
    $svcType    = trim($meta['serviceType']          ?? '');
    $cName      = trim($contact['contactName']       ?? $meta['contactName']   ?? '');
    $cCompany   = trim($contact['companyName']       ?? $meta['companyName']   ?? '');
    $cAddress   = trim($contact['contactAddress']    ?? $meta['contactAddress']?? '');
    $cEmail     = trim($contact['contactEmail']      ?? $meta['contactEmail']  ?? '');
    $cPhone     = trim($contact['contactPhone']      ?? $meta['contactPhone']  ?? '');

    $weeklyChecks  = (int) ($meta['weeklyChecks']  ?? $kpis['weeklyChecks']  ?? 0);
    $weeksPerYear  = (float) ($meta['weeksPerYear'] ?? 52);
    $monthlyChecks = $weeklyChecks * ($weeksPerYear / 12);
    $annualChecks  = $weeklyChecks * $weeksPerYear;

    $finalPrice      = (float) ($kpis['finalPricePerCheck']   ?? 0);
    $weeklyRevenue   = (float) ($kpis['weeklyRevenue']         ?? 0);
    $monthlyRevenue  = (float) ($kpis['monthlyRevenue']        ?? 0);
    $annualRevenue   = (float) ($kpis['annualRevenue']         ?? 0);
    $profitMarginPct = (float) ($kpis['profitMarginPct']       ?? 0);

    $svcPlural = $annualChecks === 1 ? 'check' : 'checks';
@endphp

{{-- Header --}}
<div class="header">
    <div class="header-logo"><img src="{{ $logoPath }}" alt="GASQ"></div>
    <div class="header-info">
        <div class="header-title">Mobile Patrol Hit Service Report</div>
        <div class="header-sub">Per-Check Pricing &amp; Revenue Analysis</div>
    </div>
    <div class="header-meta">
        <div class="report-number">{{ $reportNum }}</div>
        <div style="margin-top:4px;">{{ now()->format('M j, Y') }}</div>
        <div>{{ now()->format('g:i A') }}</div>
    </div>
</div>
<div class="accent-bar"></div>

{{-- Contact block --}}
@if($siteName || $cName || $cCompany || $cEmail || $cPhone)
<div class="contact-block">
    <div class="contact-col">
        @if($siteName)  <div class="cl">Site</div><div class="cv">{{ $siteName }}</div> @endif
        @if($svcType)   <div style="margin-top:5px;"><div class="cl">Service Type</div><div class="cv-sm">{{ $svcType }}</div></div> @endif
    </div>
    <div class="contact-col">
        @if($cName)    <div class="cl">Prepared For</div><div class="cv">{{ $cName }}</div> @endif
        @if($cCompany) <div style="margin-top:5px;"><div class="cl">Company</div><div class="cv">{{ $cCompany }}</div></div> @endif
        @if($cAddress) <div style="margin-top:5px;"><div class="cl">Address</div><div class="cv-sm">{{ $cAddress }}</div></div> @endif
    </div>
    <div class="contact-col">
        @if($cEmail)  <div class="cl">Email</div><div class="cv-sm">{{ $cEmail }}</div> @endif
        @if($cPhone)  <div style="margin-top:5px;"><div class="cl">Phone</div><div class="cv-sm">{{ $cPhone }}</div></div> @endif
        <div style="margin-top:5px;"><div class="cl">Date</div><div class="cv-sm">{{ now()->format('M j, Y') }}</div></div>
    </div>
</div>
@endif

{{-- Hero price --}}
<div class="price-hero">
    <div class="price-label">Final Sell Price Per Check</div>
    <div class="price-value">{{ $money($finalPrice) }}</div>
    <div class="price-sub">{{ $weeklyChecks }} checks/week &nbsp;·&nbsp; {{ (int)$weeksPerYear }} weeks/yr &nbsp;·&nbsp; {{ number_format($annualChecks, 0) }} annual {{ $svcPlural }} &nbsp;·&nbsp; {{ number_format($profitMarginPct * 100, 1) }}% margin</div>
</div>

<div class="body">

    {{-- Revenue + Cost side by side --}}
    <div class="two-col">
        <div class="col-left">
            <div class="sh">Revenue Summary</div>
            <table>
                <tr><th>Period</th><th>Checks</th><th class="val">Revenue</th></tr>
                <tr><td>Weekly</td><td>{{ number_format($weeklyChecks, 0) }}</td><td class="val">{{ $money($weeklyRevenue) }}</td></tr>
                <tr><td>Monthly</td><td>{{ $num($monthlyChecks, 1) }}</td><td class="val">{{ $money($monthlyRevenue) }}</td></tr>
                <tr class="row-dark"><td>Annual</td><td>{{ number_format($annualChecks, 0) }}</td><td class="val">{{ $money($annualRevenue) }}</td></tr>
            </table>

            <div class="sh" style="margin-top:14px;">Service Configuration</div>
            <table>
                <tr><td class="bold">Service Type</td><td class="val">{{ $svcType ?: '—' }}</td></tr>
                <tr><td>Weekly Checks</td><td class="val">{{ $weeklyChecks }}</td></tr>
                <tr><td>Weeks Per Year</td><td class="val">{{ (int)$weeksPerYear }}</td></tr>
                <tr><td>Checks Per Day</td><td class="val">{{ $num($kpis['checksPerDay'] ?? ($weeklyChecks / 7), 2) }}</td></tr>
                <tr><td>Minutes On Site</td><td class="val">{{ $num($meta['minutesOnSite'] ?? 0, 0) }} min</td></tr>
                <tr><td>Travel / Dispatch</td><td class="val">{{ $num($meta['minutesTravel'] ?? 0, 0) }} min</td></tr>
                <tr><td class="bold">Total Billable Min</td><td class="val bold">{{ $num($kpis['totalMinutes'] ?? 0, 0) }} min</td></tr>
                <tr><td>Hours Per Check</td><td class="val">{{ $num($kpis['hoursPerCheck'] ?? 0, 4) }} hrs</td></tr>
            </table>
        </div>

        <div class="col-right">
            <div class="sh">Per-Check Cost Breakdown</div>
            <table>
                <tr><th>Cost Component</th><th class="val">Per Check</th></tr>
                <tr><td>Fully Burdened Labor Rate</td><td class="val">{{ $money($kpis['burdenedRate'] ?? 0) }}/hr</td></tr>
                <tr><td>Total Operating Cost / Hr</td><td class="val">{{ $money($kpis['totalOpCostPerHour'] ?? 0) }}/hr</td></tr>
                <tr><td>Base Cost Per Check</td><td class="val">{{ $money($kpis['baseCostPerCheck'] ?? 0) }}</td></tr>
                <tr><td>Overhead ({{ $num($meta['overheadPct'] ?? 0, 0) }}%)</td><td class="val">{{ $money($kpis['overheadPerCheck'] ?? 0) }}</td></tr>
                <tr><td>G&amp;A ({{ $num($meta['gaPct'] ?? 0, 0) }}%)</td><td class="val">{{ $money($kpis['gaPerCheck'] ?? 0) }}</td></tr>
                <tr><td>Subtotal Cost</td><td class="val">{{ $money($kpis['subtotalCostPerCheck'] ?? 0) }}</td></tr>
                @if(($meta['addOnCost'] ?? 0) > 0)
                <tr><td>Add-On Per Check</td><td class="val">{{ $money($meta['addOnCost'] ?? 0) }}</td></tr>
                @endif
                <tr><td class="bold">Pre-Markup Cost</td><td class="val bold">{{ $money($kpis['preMkupCostPerCheck'] ?? 0) }}</td></tr>
                <tr><td>Profit ({{ $num($meta['profitPct'] ?? 0, 0) }}%)</td><td class="val">{{ $money($kpis['profitAmountPerCheck'] ?? 0) }}</td></tr>
                <tr class="row-green"><td>Final Sell Price / Check</td><td class="val">{{ $money($finalPrice) }}</td></tr>
            </table>

            <div class="sh" style="margin-top:14px;">Labor &amp; Operating Inputs</div>
            <table>
                <tr><td>Officer Pay Rate</td><td class="val">{{ $money($meta['officerPayRate'] ?? 0) }}/hr</td></tr>
                <tr><td>Payroll Burden</td><td class="val">{{ $num($meta['payrollBurdenPct'] ?? 0, 0) }}%</td></tr>
                <tr><td>Vehicle Cost</td><td class="val">{{ $money($meta['vehicleCostPerHour'] ?? 0) }}/hr</td></tr>
                <tr><td>Fuel Cost</td><td class="val">{{ $money($meta['fuelCostPerHour'] ?? 0) }}/hr</td></tr>
                <tr><td>Equipment / Tech</td><td class="val">{{ $money($meta['equipmentCostPerHour'] ?? 0) }}/hr</td></tr>
                <tr><td>Supervision / Admin</td><td class="val">{{ $money($meta['supervisionCostPerHour'] ?? 0) }}/hr</td></tr>
                <tr><td>Minimum Charge / Check</td><td class="val">{{ $money($meta['minimumCharge'] ?? 0) }}</td></tr>
            </table>
        </div>
    </div>

    {{-- Executive Narrative --}}
    <div class="narrative">
        This mobile patrol estimate is based on <strong>{{ $weeklyChecks }} weekly checks</strong>
        over <strong>{{ (int)$weeksPerYear }} weeks per year</strong>{{ $siteName ? " at <strong>{$siteName}</strong>" : '' }}.
        The selected service type is <strong>{{ $svcType ?: 'N/A' }}</strong>.
        The final sell price of <strong>{{ $money($finalPrice) }} per check</strong> reflects a fully burdened labor rate,
        vehicle, fuel, equipment and supervision costs, plus overhead, G&amp;A, and a
        <strong>{{ $num($meta['profitPct'] ?? 0, 0) }}% profit margin</strong>,
        resulting in a total annual revenue of <strong>{{ $money($annualRevenue) }}</strong>.
        Created in collaboration with the GASQ AI Team.
    </div>

</div>

<div class="footer">
    <strong>GASQ Security</strong> &nbsp;|&nbsp; Mobile Patrol Hit Service Report &nbsp;|&nbsp; {{ $reportNum }} &nbsp;|&nbsp; Rates are estimates based on provided inputs
</div>
</body>
</html>
