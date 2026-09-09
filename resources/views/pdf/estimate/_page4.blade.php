{{-- PAGE 4 — Certification, intellectual property and disclaimer statements. --}}
@php
    use App\Support\ReportSvg;

    $sections = [
        ['doc', 'GASQ Certified Statement', [
            'This report was prepared using the GASQ Cost to Protect™ methodology and includes a side-by-side comparison of the estimated cost to perform security services in-house versus outsourcing to a qualified security provider.',
        ]],
        ['chart', 'Executive Summary', [
            'The purpose of this report is to establish a realistic protection budget, identify staffing requirements, evaluate workforce availability, and determine the most cost-effective method to achieve the desired level of protection.',
        ]],
        ['gear', 'GASQ Certification Statement', [
            'This report has been generated using the GASQ Cost to Protect™ Model and has been reviewed for pricing realism, workforce availability requirements, staffing assumptions, and coverage sustainability.',
            'The calculations contained within this report are derived from proprietary methodologies, benchmarks, staffing algorithms, and analytical frameworks developed by GASQ.',
            'This report is intended solely for the use of the named recipient.',
        ]],
        ['shield', 'Intellectual Property Notice', [
            'The concepts, methodologies, calculations, presentation formats, and analytical frameworks contained within this report constitute proprietary intellectual property of GASQ.',
            'Unauthorized reproduction, reverse engineering, redistribution, resale, modification, commercial use, or creation of derivative works is prohibited without written authorization.',
        ]],
        ['badge-check', 'Disclaimer', [
            'This report is intended for budgeting, procurement planning, staffing analysis, and cost comparison purposes only. Actual wages, benefits, insurance costs, turnover rates, supervision requirements, market conditions, and customer-specific requirements may impact final pricing.',
            'GASQ makes no guarantee that any vendor will provide services at the estimated pricing levels shown within this report.',
        ]],
    ];
@endphp

<div class="page">
  @include('pdf.estimate._brandbar')

  <div class="body-pad">

    {{-- ── Title + report information ──────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
      <tr>
        <td style="vertical-align:top;">
          <p class="h1" style="font-size:25px;">CERTIFICATION, INTELLECTUAL</p>
          <p class="h1 h1-accent" style="font-size:25px;">PROPERTY &amp; DISCLAIMER</p>
          <p class="h1-sub">Final Report Information and Legal Statements</p>
        </td>
        <td width="260" style="vertical-align:top;">
          <p style="font-size:11px; font-weight:bold; color:#12294f; margin-bottom:7px;">Report Information</p>
          <table width="100%" cellpadding="0" cellspacing="0">
            @foreach([
                ['doc', 'Report ID', $reportNumber],
                ['doc', 'Report Type', $reportType],
                ['calendar', 'Report Date', $reportDate],
            ] as $i => [$icon, $label, $value])
              <tr>
                <td width="26" style="vertical-align:middle; padding-bottom:{{ $i === 2 ? 0 : 7 }}px;"><img src="{{ ReportSvg::icon($icon, '#12294f') }}" style="width:16px;height:16px;"></td>
                <td style="vertical-align:middle; padding-bottom:{{ $i === 2 ? 0 : 7 }}px;">
                  <p class="meta-label">{{ $label }}</p>
                  <p class="meta-value">{{ $value }}</p>
                </td>
              </tr>
            @endforeach
          </table>
        </td>
      </tr>
    </table>

    {{-- ── Recipient ──────────────────────────────────────── --}}
    <table class="contact" width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px;">
      <tr>
        @foreach([
            ['user', 'Vendor Contact', $d['contact']['name'] ?: '—', $d['contact']['company']],
            ['mail', 'Email', $d['contact']['email'] ?: '—', null],
        ] as $i => [$icon, $label, $value, $second])
          <td width="50%" class="{{ $i === 1 ? 'last' : '' }}">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="24" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($icon, '#5b7096') }}" style="width:15px;height:15px;"></td>
                <td style="vertical-align:middle;">
                  <p class="k">{{ $label }}</p>
                  <p class="v">{{ $value }}</p>
                  @if($second)<p class="k" style="margin-top:1px; text-transform:none; letter-spacing:0;">{{ $second }}</p>@endif
                </td>
              </tr>
            </table>
          </td>
        @endforeach
      </tr>
    </table>

    {{-- ── Statements ─────────────────────────────────────── --}}
    @foreach($sections as [$icon, $heading, $paras])
      <table class="panel" width="100%" cellpadding="0" cellspacing="0" style="margin-top:11px;">
        <tr><td class="panel-head">
          <table width="100%" cellpadding="0" cellspacing="0"><tr>
            <td width="22" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($icon, '#ffffff') }}" style="width:14px;height:14px;"></td>
            <td style="vertical-align:middle;"><p>{{ $heading }}</p></td>
          </tr></table>
        </td></tr>
        <tr><td style="padding:12px 14px 13px; background:#f7f9fc;">
          @foreach($paras as $para)
            <p class="prose">{{ $para }}</p>
          @endforeach
        </td></tr>
      </table>
    @endforeach

  </div>

  @include('pdf.estimate._footer', ['page' => 4, 'lastPage' => true])
</div>
