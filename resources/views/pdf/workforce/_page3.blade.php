{{-- PAGE 3 — GASQ Certified™ statement: methodology, certification, IP notice
     and the pricing disclaimer. --}}
@php
    $statements = [
        ['Executive Summary', [
            'This report was prepared using the GASQ Cost to Protect&trade; methodology and includes a side-by-side comparison of the estimated cost to perform security services in-house versus outsourcing to a qualified security provider.',
            'The purpose of this report is to establish a realistic protection budget, identify staffing requirements, evaluate workforce availability, and determine the most cost-effective method to achieve the desired level of protection.',
        ]],
        ['GASQ Certification Statement', [
            'This report has been generated using the GASQ Cost to Protect Model and has been reviewed for pricing realism, workforce availability requirements, staffing assumptions, and coverage sustainability.',
            'The calculations contained within this report are derived from proprietary methodologies, benchmarks, staffing algorithms, and analytical frameworks developed by GASQ.',
            'This report is intended solely for the use of the named recipient.',
        ]],
        ['Intellectual Property Notice', [
            'The concepts, methodologies, calculations, presentation formats, and analytical frameworks contained within this report constitute proprietary intellectual property of GASQ.',
            'Unauthorized reproduction, reverse engineering, redistribution, resale, modification, commercial use, or creation of derivative works is prohibited without written authorization.',
        ]],
        ['Disclaimer', [
            'This report is intended for budgeting, procurement planning, staffing analysis, and cost comparison purposes only. Actual wages, benefits, insurance costs, turnover rates, supervision requirements, market conditions, and customer-specific requirements may impact final pricing.',
            'GASQ makes no guarantee that any vendor will provide services at the estimated pricing levels shown within this report.',
        ]],
    ];
@endphp
<div class="page">
  @include('pdf.workforce._header', [
      'pageTitle' => 'GASQ Certified Statement',
      'pageSubtitle' => 'Methodology · Certification · Intellectual Property · Disclaimer',
  ])

  <div class="body-pad">

    <table class="panel tint" cellpadding="0" cellspacing="0" style="width:736px;">
      <tr><td class="panel-pad">
        <p class="panel-k">Full-Cost Pricing Statement</p>
        <p class="prose" style="margin-top:8px;">All price calculations include the full cost of workforce staffing and support services, including livable base wages, employer-paid payroll taxes (FICA, FUTA, SUTA), workers compensation, general liability insurance, unemployment insurance, paid time off, healthcare and fringe benefits, uniforms and equipment, onboarding and training, site supervision, quality assurance oversight, management and administrative support, 24/7 dispatch capability, compliance with local, state, and federal labor laws, and all service-level guarantees, including open post protection, vendor replacement, and price lock guarantees, unless otherwise specified.</p>
      </td></tr>
    </table>

    <table class="band" cellpadding="0" cellspacing="0" style="margin-top:20px; border-radius:4px;">
      <tr><td><p>GASQ Certified&trade; Statement</p></td></tr>
    </table>

    <table class="grid" cellpadding="0" cellspacing="0" style="margin-top:14px;">
      @foreach(array_chunk($statements, 2) as $row)
        <tr>
          @foreach($row as $c => [$heading, $paragraphs])
            @if($c > 0)<td style="width:12px;"></td>@endif
            <td style="width:363px; vertical-align:top;">
              <table class="stmt" cellpadding="0" cellspacing="0">
                <tr><td class="panel-pad" style="height:196px;">
                  <p class="stmt-k">{!! $heading !!}</p>
                  @foreach($paragraphs as $paragraph)
                    <p class="prose" style="margin-top:8px;">{!! $paragraph !!}</p>
                  @endforeach
                </td></tr>
              </table>
            </td>
          @endforeach
        </tr>
        @if(! $loop->last)<tr><td colspan="3" style="height:12px;"></td></tr>@endif
      @endforeach
    </table>

    <p class="closing" style="margin-top:26px;">
      &copy; {{ now()->format('Y') }} GASQ &nbsp;·&nbsp; ALL RIGHTS RESERVED<br>
      BUILT FOR CFO-LEVEL COST ANALYSIS.<br>
      THE INDUSTRY PRICING REFEREE&trade;
    </p>

  </div>

  @include('pdf.workforce._footer', ['page' => $pages])
</div>
