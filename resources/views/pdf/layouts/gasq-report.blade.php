{{--
    GASQ Report PDF base layout — Mobile Patrol Report design language.

    Required vars:
        $title          string  e.g. "GASQ Mobile Patrol Report"
        $subtitle       string  e.g. "VENDOR COST ANALYSIS & HOURLY BILL RATE"
        $reportNumber   string  e.g. "GASQ 2026-05-14-7559"

    Optional vars:
        $reportType     string  shown in top-right meta card (e.g. "Vendor — Full Report")
        $contactName    string  vendor full name
        $contactCompany string
        $contactAddress string
        $contactEmail   string
        $contactPhone   string

    Sections (yields):
        @section('stat_grid')       full stat-grid HTML (use the .stat-* classes below)
        @section('content')         main body — section bands + tables
--}}
@php
    $logoPath  = 'file://' . public_path('images/site-logo.png');
    $title         = $title ?? 'GASQ Report';
    $subtitle      = $subtitle ?? '';
    $reportNumber  = $reportNumber ?? ('GASQ ' . now()->format('Y-m-d'));
    $reportType    = $reportType ?? 'Vendor — Full Report';
    $contactName    = $contactName ?? '';
    $contactCompany = $contactCompany ?? '';
    $contactAddress = $contactAddress ?? '';
    $contactEmail   = $contactEmail ?? '';
    $contactPhone   = $contactPhone ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 0; background: #fff; }
    table { border-collapse: collapse; }
    td, th { padding: 0; margin: 0; }
    p { margin: 0; padding: 0; }

    /* Section header band */
    .gasq-section-band { background:#1e3558; padding:9px 16px; }
    .gasq-section-band p { font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:.08em; }

    /* Generic kv table inside a section */
    .gasq-kv { width:100%; border:1px solid #d8dff0; border-top:none; border-collapse:collapse; }
    .gasq-kv td { padding:7px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151; }
    .gasq-kv td.v { color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums; }
    .gasq-kv tr.alt td { background:#f6f8fb; }
    .gasq-kv tr.hl td { background:#1e3558; color:#ffffff; font-weight:bold; font-size:11px; }
    .gasq-kv tr.hl td.v { color:#ffffff; }
    .gasq-kv tr.total td { background:#e8f5eb; color:#1e3558; font-weight:bold; border-top:2px solid #1e3558; font-size:11px; }
    .gasq-kv tr.total td.v { font-size:13px; }

    /* Stat grid cells */
    .stat-grid-label { background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff; }
    .stat-grid-label.last { border-right:0; }
    .stat-grid-label p { font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:.1em; }

    .stat-grid-value { padding:16px; text-align:center; border-right:2px solid #ffffff; border-bottom:2px solid #1e3558; }
    .stat-grid-value.last { border-right:0; }
    .stat-grid-value .num { font-size:34px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums; }
    .stat-grid-value .sub { font-size:8px; color:#6b7280; margin-top:3px; }

    /* Background variants for the stat cells */
    .bg-blue   { background:#f5f8ff; }
    .bg-pink   { background:#fde8e0; }  .bg-pink .sub   { color:#7a4a3a; }
    .bg-green  { background:#e8f5eb; }  .bg-green .sub  { color:#2d6a4f; }
    .bg-purple { background:#ede9f6; }
    .bg-peach  { background:#fdeae4; }  .bg-peach .sub  { color:#7a4a3a; }
    .bg-sky    { background:#e0eaf8; }  .bg-sky .sub    { color:#3a5a8a; }

    /* Inline badges */
    .gasq-badge { display:inline-block; padding:3px 10px; border-radius:10px; font-size:9px; font-weight:bold; letter-spacing:.4px; }
    .gasq-badge.ok   { background:#d1e7dd; color:#0a3622; }
    .gasq-badge.warn { background:#fff3cd; color:#664d03; }
    .gasq-badge.info { background:#dbeafe; color:#1e3a8a; }

    /* Section spacing */
    .gasq-mt   { margin-top:18px; }
    .gasq-mt-sm { margin-top:8px; }

    /* Optional generic note paragraph */
    .gasq-note { font-size:9.5px; color:#4b5563; line-height:1.45; margin-top:10px; padding:0 4px; }

    .compact-row { padding:5px 16px; }
</style>
</head>
<body>

{{-- ═══════════════════════════════════════════════════════ HEADER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff; border-bottom:3px solid #1e3558;">
  <tr>
    <td width="110" style="padding:14px 12px 14px 20px; vertical-align:middle;">
      <img src="{{ $logoPath }}" alt="GASQ" style="width:80px; height:auto; display:block;">
    </td>
    <td style="padding:14px 12px; vertical-align:middle;">
      <p style="font-size:18px; font-weight:bold; color:#1e3558; letter-spacing:0.01em; margin-bottom:4px;">{{ $title }}</p>
      @if($subtitle)
        <p style="font-size:8.5px; color:#6b7280; text-transform:uppercase; letter-spacing:0.12em;">{{ $subtitle }}</p>
      @endif
    </td>
    <td style="padding:14px 20px 14px 12px; vertical-align:middle; text-align:right;">
      <p style="font-size:11px; font-weight:bold; color:#1e3558; margin-bottom:4px;">{{ $reportNumber }}</p>
      <p style="font-size:9px; color:#6b7280;">{{ now()->format('F j, Y') }}</p>
    </td>
  </tr>
</table>

{{-- ═══════════════════════════════════════════════════════ CONTACT META CARD --}}
@if($contactName || $contactCompany || $contactEmail || $contactPhone)
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb; border-bottom:1px solid #d8dff0;">
  <tr>
    <td width="33%" style="padding:11px 10px 11px 20px; vertical-align:top; border-right:1px solid #d8dff0;">
      @if($contactName)
        <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Vendor Contact</p>
        <p style="font-size:11px; font-weight:bold; color:#1e3558;">{{ $contactName }}</p>
      @endif
      @if($contactCompany)<p style="font-size:10px; color:#374151; margin-top:3px;">{{ $contactCompany }}</p>@endif
      @if($contactAddress)<p style="font-size:9px; color:#6b7280; margin-top:2px;">{{ $contactAddress }}</p>@endif
    </td>
    <td style="padding:11px 10px; vertical-align:top; border-right:1px solid #d8dff0;">
      @if($contactEmail)
        <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Email</p>
        <p style="font-size:10px; color:#1e3558;">{{ $contactEmail }}</p>
      @endif
      @if($contactPhone)
        <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-top:6px; margin-bottom:2px;">Phone</p>
        <p style="font-size:10px; color:#1e3558;">{{ $contactPhone }}</p>
      @endif
    </td>
    <td style="padding:11px 20px 11px 10px; vertical-align:top; text-align:right;">
      <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Report Type</p>
      <p style="font-size:10px; color:#1e3558; font-weight:bold;">{{ $reportType }}</p>
      <p style="font-size:8px; color:#6b7280; margin-top:8px;">Generated {{ now()->format('M j, Y g:i A') }}</p>
    </td>
  </tr>
</table>
@endif

{{-- ═══════════════════════════════════════════════════════ STAT GRID --}}
@hasSection('stat_grid')
<div style="border-top:3px solid #1e3558; margin-top:16px;">
@yield('stat_grid')
</div>
@endif

{{-- ═══════════════════════════════════════════════════════ MAIN CONTENT --}}
@yield('content')

{{-- ═══════════════════════════════════════════════════════ FOOTER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px; border-top:2px solid #1e3558;">
  <tr style="background:#f4f6fb;">
    <td style="padding:10px 20px;">
      <p style="font-size:8.5px; color:#1e3558; font-weight:bold;">GASQ Security</p>
      <p style="font-size:8px; color:#6b7280; margin-top:2px;">{{ $reportNumber }} &nbsp;·&nbsp; {{ $reportType }} &nbsp;·&nbsp; Confidential — for authorized recipients only</p>
    </td>
    <td style="padding:10px 20px; text-align:right;">
      <p style="font-size:8px; color:#6b7280;">{{ now()->format('M j, Y') }}</p>
    </td>
  </tr>
</table>

</body>
</html>
