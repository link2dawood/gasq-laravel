{{--
    Shared PDF layout for customer-facing GASQ reports.
    Usage:
        @extends('pdf.layouts.gasq-base', [
            'title' => 'Vendor Qualification Packet',
            'subtitle' => 'GASQ Responsive & Responsible Vendor Verification',
            'preparedFor' => 'Acme Corp',
            'preparedBy' => 'Vendor Name',
            'reportDate' => '2026-05-12',
        ])
        @section('cover_summary') ... cover-page summary block ... @endsection
        @section('content') ... main body sections ... @endsection
--}}
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $title ?? 'GASQ Report' }}</title>
<style>
    @page { margin: 110px 40px 70px 40px; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 11px;
        color: #1f2937;
        line-height: 1.5;
    }
    /* ---------- Page chrome ---------- */
    header.page-header {
        position: fixed; top: -90px; left: 0; right: 0;
        height: 80px;
        background: #0d6efd;
        color: #fff;
        padding: 18px 24px;
    }
    header.page-header .brand {
        font-size: 22px; font-weight: 800; letter-spacing: .5px;
    }
    header.page-header .tagline {
        font-size: 11px; opacity: .9; margin-top: 2px;
    }
    footer.page-footer {
        position: fixed; bottom: -50px; left: 0; right: 0;
        height: 40px;
        font-size: 9px; color: #6b7280;
        padding: 10px 24px;
        border-top: 1px solid #e5e7eb;
    }
    footer.page-footer .pageno:after { content: counter(page); }

    /* ---------- Cover ---------- */
    .cover {
        page-break-after: always;
        padding: 32px 0;
    }
    .cover h1 {
        font-size: 28px;
        color: #0d6efd;
        margin: 0 0 6px;
        line-height: 1.2;
    }
    .cover .subtitle {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 24px;
    }
    .cover .meta {
        background: #f8fafc;
        border-left: 4px solid #0d6efd;
        padding: 14px 18px;
        margin: 24px 0;
    }
    .cover .meta td { padding: 5px 6px; border: 0; font-size: 12px; }
    .cover .meta .label { color: #6b7280; width: 35%; }

    .badge-row { margin: 18px 0 8px; }
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 14px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .3px;
        margin-right: 6px;
    }
    .badge.ok { background: #d1e7dd; color: #0a3622; }
    .badge.warn { background: #fff3cd; color: #664d03; }
    .badge.danger { background: #f8d7da; color: #842029; }
    .badge.info { background: #cfe2ff; color: #084298; }

    /* ---------- Typography ---------- */
    h2 {
        font-size: 15px;
        color: #0d6efd;
        margin: 22px 0 8px;
        padding-bottom: 4px;
        border-bottom: 2px solid #0d6efd;
    }
    h3 {
        font-size: 12px;
        margin: 14px 0 6px;
        color: #1f2937;
        text-transform: uppercase;
        letter-spacing: .6px;
    }

    /* ---------- Tables ---------- */
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th, td { padding: 7px 10px; vertical-align: top; }
    th {
        background: #f1f5f9;
        font-size: 10px;
        text-transform: uppercase;
        color: #475569;
        text-align: left;
        border-bottom: 2px solid #cbd5e1;
    }
    td { border-bottom: 1px solid #e5e7eb; font-size: 11px; }
    td.label { color: #6b7280; width: 45%; }
    .right { text-align: right; }
    .mono { font-family: "DejaVu Sans Mono", "Courier New", monospace; }

    .kv-table td { border-bottom: 1px solid #e5e7eb; }

    /* Executive summary callouts */
    .exec-grid { display: table; width: 100%; margin: 12px 0 18px; }
    .exec-cell {
        display: table-cell;
        width: 25%;
        padding: 14px 12px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        text-align: center;
    }
    .exec-cell + .exec-cell { border-left: 0; }
    .exec-cell .label { color: #6b7280; font-size: 9px; text-transform: uppercase; letter-spacing: .6px; }
    .exec-cell .value { font-size: 16px; font-weight: 700; color: #0d6efd; margin-top: 4px; }

    /* Highlighted footer rows (for appraisal-style tables) */
    .emphasis { background: #fff4e6; font-weight: 600; }

    .muted { color: #6b7280; }
    .small { font-size: 10px; }

    .gasq-protection-block {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        padding: 14px 16px;
        margin-top: 18px;
        border-radius: 4px;
    }
    .gasq-protection-block h3 { color: #0369a1; margin-top: 0; }
</style>
</head>
<body>

<header class="page-header">
    <table style="width:100%;border:0;">
        <tr>
            <td style="border:0;padding:0;">
                <div class="brand">GASQ</div>
                <div class="tagline">GetASecurityQuoteNow · CFO Tested · CFO Approved</div>
            </td>
            <td style="border:0;padding:0;text-align:right;color:#cfe1ff;font-size:11px;">
                {{ $title ?? '' }}
            </td>
        </tr>
    </table>
</header>

<footer class="page-footer">
    <table style="width:100%;border:0;">
        <tr>
            <td style="border:0;padding:0;">
                (470) 633-2816 · info@getasecurityquote.com · getasecurityquotenow.com
            </td>
            <td style="border:0;padding:0;text-align:right;">Page <span class="pageno"></span></td>
        </tr>
    </table>
</footer>

<main>

    @hasSection('cover_summary')
    <div class="cover">
        <h1>{{ $title ?? 'GASQ Report' }}</h1>
        <div class="subtitle">{{ $subtitle ?? '' }}</div>

        <table class="meta">
            @isset($preparedFor)
            <tr><td class="label">Prepared for</td><td><strong>{{ $preparedFor }}</strong></td></tr>
            @endisset
            @isset($preparedBy)
            <tr><td class="label">Prepared by</td><td><strong>{{ $preparedBy }}</strong></td></tr>
            @endisset
            @isset($reportDate)
            <tr><td class="label">Report date</td><td>{{ $reportDate }}</td></tr>
            @endisset
            @isset($referenceNumber)
            <tr><td class="label">Reference #</td><td class="mono">{{ $referenceNumber }}</td></tr>
            @endisset
        </table>

        @yield('cover_summary')

        <p class="small muted" style="margin-top:36px;">
            This report is generated by the GASQ Workforce-to-Post™ Qualification System. All figures, qualifications, and verifications reflect the data captured at the time of submission. Backed by the GASQ Price Lock Guarantee and Vendor Replacement Guarantee.
        </p>
    </div>
    @endif

    @yield('content')

</main>
</body>
</html>
