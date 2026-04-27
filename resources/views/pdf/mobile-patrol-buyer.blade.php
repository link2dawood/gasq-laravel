<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Mobile Patrol – Buyer Report</title>
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
        .body { padding: 32px 24px; }

        /* ── Rate display ────────────────────────────────────── */
        .rate-wrapper { text-align: center; padding: 40px 24px; }
        .rate-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; font-weight: bold; margin-bottom: 12px; }
        .rate-value { font-size: 56px; font-weight: bold; color: #062d79; letter-spacing: -0.02em; }
        .rate-unit { font-size: 16px; color: #374151; margin-top: 6px; }
        .rate-box {
            border: 2px solid #062d79;
            border-radius: 8px;
            display: inline-block;
            padding: 28px 48px;
            background: #f0f5ff;
        }
        .rate-note {
            margin-top: 20px;
            font-size: 10px;
            color: #6b7280;
            font-style: italic;
        }

        /* ── Divider ─────────────────────────────────────────── */
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 0 24px; }

        /* ── Info table ──────────────────────────────────────── */
        .info-section { padding: 16px 24px 0; }
        .info-heading { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.07em; color: #062d79; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; color: #374151; }
        td.label-col { color: #6b7280; width: 50%; }
        td.val { text-align: right; font-variant-numeric: tabular-nums; font-weight: bold; color: #111827; }

        /* ── Badge ──────────────────────────────────────────── */
        .badge-buyer { display: inline-block; background: #065f46; color: #fff; font-size: 8px; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; padding: 2px 7px; border-radius: 3px; margin-left: 8px; vertical-align: middle; }

        /* ── Footer ─────────────────────────────────────────── */
        .footer { margin-top: 32px; padding: 12px 24px; background: #f3f5f8; border-top: 2px solid #062d79; text-align: center; font-size: 9px; color: #6b7280; }
        .footer strong { color: #062d79; }
    </style>
</head>
<body>
@php
    $scenarioMeta = (array) ($scenario['meta'] ?? []);
    $contact      = (array) ($scenario['contact'] ?? []);
    $kpis         = (array) ($result['kpis'] ?? $result ?? []);

    $money = static fn ($v) => '$' . number_format((float) ($v ?? 0), 2);
    $num   = static fn ($v, $d = 2) => number_format((float) ($v ?? 0), $d);

    $rosInput = (float) ($scenarioMeta['returnOnSalesPct'] ?? 0);
    $rosRate  = $rosInput > 1 ? ($rosInput / 100) : $rosInput;
    $rosPct   = (float) ($kpis['returnOnSalesPercentDisplay'] ?? ($rosRate * 100));

    $reportNum = 'GASQ ' . now()->format('Y-m-d') . '-' . str_pad(($reportId ?? rand(1, 9999)), 4, '0', STR_PAD_LEFT);
    $logoPath  = 'file://' . public_path('img/gasq-logo.png');

    $cName    = trim($contact['contactName']    ?? '');
    $cCompany = trim($contact['companyName']    ?? '');
    $cAddress = trim($contact['contactAddress'] ?? '');
    $cEmail   = trim($contact['contactEmail']   ?? '');
    $cPhone   = trim($contact['contactPhone']   ?? '');

    $hourlyRate = $kpis['costPerHour'] ?? 0;
@endphp

{{-- Header --}}
<div class="header">
    <div class="header-logo">
        <img src="{{ $logoPath }}" alt="GASQ">
    </div>
    <div class="header-info">
        <div class="header-title">Mobile Patrol Quote <span class="badge-buyer">Buyer</span></div>
        <div class="header-sub">Estimated Hourly Service Rate</div>
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
        @if($cName)    <div class="contact-label">Prepared For</div><div class="contact-value">{{ $cName }}</div> @endif
        @if($cCompany) <div style="margin-top:6px;"><div class="contact-label">Company</div><div class="contact-value">{{ $cCompany }}</div></div> @endif
        @if($cAddress) <div style="margin-top:6px;"><div class="contact-label">Address</div><div class="contact-value-sm">{{ $cAddress }}</div></div> @endif
    </div>
    <div class="contact-col">
        @if($cEmail) <div class="contact-label">Email</div><div class="contact-value-sm">{{ $cEmail }}</div> @endif
        @if($cPhone) <div style="margin-top:6px;"><div class="contact-label">Phone</div><div class="contact-value-sm">{{ $cPhone }}</div></div> @endif
    </div>
</div>
@endif

{{-- Rate display --}}
<div class="body">
    <div class="rate-wrapper">
        <div class="rate-box">
            <div class="rate-label">Estimated Hourly Bill Rate</div>
            <div class="rate-value">{{ $money($hourlyRate) }}</div>
            <div class="rate-unit">per hour</div>
        </div>
        <div class="rate-note">
            This rate is calculated from mobile patrol operating costs including labor, fuel, vehicle,<br>
            maintenance, and a {{ $num($rosPct, 0) }}% return on sales margin.
        </div>
    </div>
</div>

<hr class="divider">

{{-- Summary key facts --}}
<div class="info-section">
    <div class="info-heading">Service Summary</div>
    <table>
        <tr>
            <td class="label-col">Annual coverage hours</td>
            <td class="val">{{ $num($scenarioMeta['annualHours'] ?? 0, 0) }} hrs/yr</td>
            <td class="label-col">Patrol speed</td>
            <td class="val">{{ $num($scenarioMeta['mph'] ?? 0, 0) }} MPH</td>
        </tr>
        <tr>
            <td class="label-col">Hours per day</td>
            <td class="val">{{ $num($scenarioMeta['hoursPerDay'] ?? 0, 0) }}</td>
            <td class="label-col">Miles covered per year</td>
            <td class="val">{{ $num($kpis['milesPerYear'] ?? 0, 0) }}</td>
        </tr>
        <tr>
            <td class="label-col">Total annual operating cost</td>
            <td class="val">{{ $money($kpis['totalAnnualCost'] ?? 0) }}</td>
            <td class="label-col">Return on sales</td>
            <td class="val">{{ $num($rosPct, 2) }}%</td>
        </tr>
    </table>
</div>

<div class="footer">
    <strong>GASQ Security</strong> &nbsp;|&nbsp; Mobile Patrol Buyer Quote &nbsp;|&nbsp; {{ $reportNum }} &nbsp;|&nbsp; Rates are estimates based on provided inputs
</div>
</body>
</html>
