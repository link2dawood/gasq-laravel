{{-- PAGE 3 — Executive summary, what the estimate covers, savings and payback. --}}
@php
    use App\Support\ReportSvg;

    $cards = [
        ['stack', 'Buyer Internal Cost to Protect', 'bg-navy', 'tint-navy', $moneyK($d['totalAnnualInternal']), 'Total annual in-house cost', false],
        ['users', 'Vendor Outsourcing Cost to Protect', 'bg-orange', 'tint-orange', $lock($moneyK($d['totalAnnualVendor'])), 'Total annual vendor cost', false],
        ['chart', 'Capital Recovered', 'bg-green', 'tint-green', $lock($moneyK($d['annualCapitalRecovery'])), 'Recovered vs in-house', true],
        ['pie', 'Operational Capital Recovered', 'bg-green', 'tint-green', $lock($num($d['recoveryPct']) . '%'), 'Lower cost with vendor outsourcing', true],
        ['clock', 'Payback & Recovery Period', 'bg-navy', 'tint-navy', $lock($numDec($d['paybackMonths'], 1)), 'Months to recover capital investment', false],
        ['calendar', 'Total Annual Coverage Hours', 'bg-navy', 'tint-navy', $num($d['annualCoverageHours']), 'Hours of security coverage', false],
    ];

    $includes = [
        ['$', 'Livable Base Wages'],
        ['calculator', 'Employer-Paid Payroll Taxes (FICA, FUTA, SUTA)'],
        ['shield', 'Workers Compensation'],
        ['badge-check', 'General Liability Insurance'],
        ['users', 'Unemployment Insurance'],
        ['calendar', 'Paid Time Off (Holidays, Vacation, Sick Leave)'],
        ['heart', 'Healthcare and Fringe Benefits'],
        ['shirt', 'Uniforms and Equipment'],
        ['graduation', 'Onboarding and Training'],
        ['user', 'Site Supervision'],
        ['search', 'Quality Assurance Oversight'],
        ['gear', 'Management and Administrative Support'],
        ['antenna', '24/7 Dispatch Capability'],
        ['doc', 'Compliance with Federal, State & Local Labor Laws'],
        ['handshake', 'Service-Level Guarantees (open post protection, vendor replacement, price lock)'],
    ];

    // Savings chart (same basis as page 1, sized for this panel).
    $sAxis = $niceAxis(max($d['totalAnnualInternal'], $d['totalAnnualVendor']), 7);
    $sPlotW = 176;
    $sPlotH = $masked ? 100 : 112;
    $sBlockH = $masked ? 144 : 156;
    $sAxisW = 52;
    $sBarH = fn (float $v) => max(2, (int) round($sPlotH * ($sAxis['max'] > 0 ? $v / $sAxis['max'] : 0)));

    // Buyer edition plots the buyer's own cost alone — see page 1.
    $sSeries = $masked
        ? [[$d['totalAnnualInternal'], '#12294f', 'Buyer Internal<br>Cost to Protect']]
        : [
            [$d['totalAnnualInternal'], '#12294f', 'Buyer Internal<br>Cost to Protect'],
            [$d['totalAnnualVendor'], '#ef6c1f', 'Vendor Outsourcing<br>Cost to Protect'],
        ];

    // Payback rail runs over a 12-month horizon (clamped for longer paybacks).
    $paybackHorizon = max(12, ceil($d['paybackMonths']));
    $paybackPct = $paybackHorizon > 0 ? 100 * $d['paybackMonths'] / $paybackHorizon : 0;
@endphp

<div class="page page-break">
  @include('pdf.estimate._brandbar')

  <div class="body-pad">

    {{-- ── Title + report meta ─────────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:{{ $masked ? 4 : 14 }}px;">
      <tr>
        <td style="vertical-align:top;">
          <p class="h1" style="font-size:26px;">EXECUTIVE SUMMARY &amp;</p>
          <p class="h1 h1-accent" style="font-size:26px;">RECOVERY INSIGHTS</p>
          <p class="h1-sub">Key Findings from the GASQ Cost to Protect Analysis</p>
        </td>
        <td width="248" style="vertical-align:top; padding-top:2px;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td width="26" style="vertical-align:middle; padding-bottom:9px;"><img src="{{ ReportSvg::icon('doc', '#12294f') }}" style="width:16px;height:16px;"></td>
              <td style="vertical-align:middle; padding-bottom:9px;">
                <p class="meta-label">Report ID</p>
                <p class="meta-value">{{ $reportNumber }}</p>
              </td>
            </tr>
            <tr>
              <td style="vertical-align:middle;"><img src="{{ ReportSvg::icon('calendar', '#12294f') }}" style="width:16px;height:16px;"></td>
              <td style="vertical-align:middle;">
                <p class="meta-label">Report Date</p>
                <p class="meta-value">{{ $reportDate }}</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    {{-- ── Six headline findings ──────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
      <tr>
        @foreach($cards as $c => [$icon, $label, $headClass, $bodyClass, $value, $sub, $isGreen])
          @if($c > 0)<td width="6"></td>@endif
          <td width="15.5%" style="vertical-align:top;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr><td class="kpi-head {{ $headClass }}" style="height:34px; padding:5px 7px;">
                <p style="font-size:7.5px; font-weight:bold; color:#fff; letter-spacing:.06em; text-transform:uppercase; line-height:1.3;">{{ $label }}</p>
              </td></tr>
              <tr><td class="kpi-body {{ $bodyClass }} {{ $isGreen ? 'green' : '' }}" style="padding:10px 5px 9px;">
                <p class="num" style="font-size:17px;">{{ $value }}</p>
                <p class="sub" style="font-size:7px; line-height:1.3;">{{ $sub }}</p>
              </td></tr>
            </table>
          </td>
        @endforeach
      </tr>
    </table>

    {{-- ── Executive summary ──────────────────────────────── --}}
    <table class="panel" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
      <tr><td class="panel-head light">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('doc', '#12294f') }}" style="width:13px;height:13px;"></td>
          <td style="vertical-align:middle;"><p>Executive Summary</p></td>
        </tr></table>
      </td></tr>
      <tr><td style="padding:9px 14px 10px;">
        <p class="prose">This report was prepared using the GASQ Cost to Protect™ methodology and includes a side-by-side comparison of the estimated cost to perform security services in-house versus outsourcing to a qualified security provider.</p>
        <p class="prose">The purpose of this report is to establish a realistic protection budget, identify staffing requirements, evaluate workforce availability, and determine the most cost-effective method to achieve the desired level of protection.</p>
        @if($masked)
          {{-- One paragraph, not two: a third block of prose pushes the panels
               below into the footer. --}}
          <p class="prose">This edition reports your in-house Cost to Protect in full — the annual and hourly cost of delivering this scope with your own workforce, the staff required, and the coverage hours behind both figures. The vendor outsourcing cost, the operational capital recovered and the payback period are withheld; unlock the complete estimate to see them set against the figures shown here.</p>
        @else
          <p class="prose">The analysis shows that outsourcing security services can reduce annual costs by {{ $num($d['recoveryPct']) }}%, resulting in {{ $moneyK($d['annualCapitalRecovery']) }} in capital recovered compared to an in-house model, with a payback period of {{ $numDec($d['paybackMonths'], 1) }} months. These findings support a more efficient, scalable, and financially responsible approach to security operations.</p>
        @endif
      </td></tr>
    </table>

    {{-- ── What the estimate includes ─────────────────────── --}}
    <table class="panel" width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;">
      <tr><td class="panel-head light">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('check', '#12294f') }}" style="width:13px;height:13px;"></td>
          <td style="vertical-align:middle;"><p>What the Estimate Includes</p></td>
        </tr></table>
      </td></tr>
      <tr><td style="padding:8px 10px 10px;">
        <p style="font-size:8.5px; color:#5b6779; margin-bottom:8px;">All price calculations include the full cost of workforce staffing and support services, including:</p>
        <table width="100%" cellpadding="0" cellspacing="0">
          @foreach(array_chunk($includes, 5) as $chunk)
            <tr>
              @foreach($chunk as [$icon, $label])
                <td width="20%" style="padding:{{ $masked ? 3 : 4 }}px 6px; vertical-align:top; text-align:center;">
                  <table cellpadding="0" cellspacing="0" align="center">
                    <tr><td style="width:24px; height:24px; background:#eaf0f9; border-radius:12px; text-align:center; vertical-align:middle;">
                      @if($icon === '$')
                        <p style="font-size:11px; font-weight:bold; color:#12294f;">$</p>
                      @else
                        <table cellpadding="0" cellspacing="0" align="center"><tr><td style="padding:5px;"><img src="{{ ReportSvg::icon($icon, '#12294f') }}" style="width:14px;height:14px;"></td></tr></table>
                      @endif
                    </td></tr>
                  </table>
                  <p style="font-size:7px; color:#3c4a5e; margin-top:5px; line-height:1.4;">{{ $label }}</p>
                </td>
              @endforeach
            </tr>
          @endforeach
        </table>
      </td></tr>
    </table>

    {{-- ── Savings + payback ──────────────────────────────── --}}
    <div style="margin-top:8px;">
      <div style="width:373px; float:left;">
        <table class="panel" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr><td class="panel-head">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
              <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('chart', '#ffffff') }}" style="width:13px;height:13px;"></td>
              <td style="vertical-align:middle;"><p>Cost Savings Impact</p></td>
            </tr></table>
          </td></tr>
          <tr><td style="padding:12px 10px 10px;">
            {{-- Chart in a fixed-height block, callout laid over it to the right:
                 table cells here get re-proportioned by dompdf and floats inside a
                 cell do not grow it, so both push the callout out of the panel. --}}
            <div style="height:{{ $sBlockH }}px;">
              <div style="height:12px;"></div>
              <div style="margin-left:{{ $sAxisW }}px; width:{{ $sPlotW }}px;">
                <img src="{{ ReportSvg::gridlines($sPlotW, $sPlotH, count($sAxis['labels']) - 1) }}" style="width:{{ $sPlotW }}px; height:{{ $sPlotH }}px;">
              </div>
              <div style="margin-top:-{{ $sPlotH + 12 }}px; margin-left:{{ $sAxisW }}px; width:{{ $sPlotW }}px; height:{{ $sPlotH + 12 }}px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    @foreach($sSeries as [$val, $color, $sLabel])
                      <td width="{{ (int) round(100 / count($sSeries)) }}%" style="vertical-align:top; text-align:center;">
                        <div style="height:{{ $sPlotH - $sBarH($val) }}px;"></div>
                        <p style="font-size:8px; font-weight:bold; color:#12294f; height:12px;">{{ $moneyK($val) }}</p>
                        <table cellpadding="0" cellspacing="0" width="46" align="center">
                          <tr><td style="height:{{ $sBarH($val) }}px; background:{{ $color }};"></td></tr>
                        </table>
                      </td>
                    @endforeach
                  </tr>
                </table>
              </div>
              <div style="margin-top:-{{ $sPlotH }}px; width:{{ $sAxisW - 6 }}px; height:{{ $sPlotH }}px;">
                @foreach(array_slice(array_reverse($sAxis['labels']), 0, count($sAxis['labels']) - 1) as $l)
                  <div style="height:{{ round($sPlotH / (count($sAxis['labels']) - 1), 2) }}px;"><p class="axis" style="margin-top:-3px;">{{ $moneyK($l) }}</p></div>
                @endforeach
                <p class="axis" style="margin-top:-3px;">{{ $moneyK(0) }}</p>
              </div>
              <div style="margin-left:{{ $sAxisW }}px; width:{{ $sPlotW }}px; margin-top:5px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    @foreach($sSeries as [$val, $color, $sLabel])
                      <td width="{{ (int) round(100 / count($sSeries)) }}%" style="text-align:center;"><p style="font-size:7px; color:#5b6779; line-height:1.35;">{!! $sLabel !!}</p></td>
                    @endforeach
                  </tr>
                </table>
              </div>
            </div>
            <div style="margin-top:-{{ $sBlockH - 8 }}px; height:{{ $sBlockH - 8 }}px;">
              {{-- 238 + 97 content + 12 padding + 2 border = the panel's 351px of content --}}
              @if($masked)
              <div style="margin-left:238px; width:97px; background:#eef3fb; border:1px solid #c9d6ea; padding:12px 6px; text-align:center;">
                <table cellpadding="0" cellspacing="0" align="center"><tr>
                  <td><img src="{{ ReportSvg::icon('shield', '#ef6c1f', 2.2) }}" style="width:22px;height:22px;"></td>
                </tr></table>
                <p style="font-size:7.5px; font-weight:bold; color:#12294f; letter-spacing:.05em; margin-top:6px; line-height:1.4;">SAVINGS<br>WITHHELD</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0;"><tr><td style="height:1px; background:#c9d6ea;"></td></tr></table>
                <p style="font-size:7px; color:#5b6779; line-height:1.5;">Unlock the full estimate to see it.</p>
              </div>
              @else
              <div style="margin-left:238px; width:97px; background:#e8f5ec; border:1px solid #bfe0cb; padding:12px 6px; text-align:center;">
                <table cellpadding="0" cellspacing="0" align="center"><tr>
                  <td style="vertical-align:middle;"><img src="{{ ReportSvg::icon('arrow-down', '#16794a', 2.4) }}" style="width:16px;height:16px;"></td>
                  <td style="vertical-align:middle; padding-left:2px;"><p style="font-size:21px; font-weight:bold; color:#16794a; line-height:1;">{{ $num($d['recoveryPct']) }}%</p></td>
                </tr></table>
                <p style="font-size:7.5px; font-weight:bold; color:#15794a; letter-spacing:.05em; margin-top:5px; line-height:1.4;">LOWER COST WITH<br>VENDOR OUTSOURCING</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0;"><tr><td style="height:1px; background:#bfe0cb;"></td></tr></table>
                <p style="font-size:13px; font-weight:bold; color:#16794a;">{{ $moneyK($d['annualCapitalRecovery']) }}</p>
                <p style="font-size:7px; color:#3f6b53; margin-top:3px; line-height:1.4;">in capital recovered<br>vs in-house</p>
              </div>
              @endif
            </div>
          </td></tr>
        </table>
      </div>
      <div style="width:373px; float:right;">
        <table class="panel" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr><td class="panel-head">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
              <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('clock', '#ffffff') }}" style="width:13px;height:13px;"></td>
              <td style="vertical-align:middle;"><p>Payback Timeline</p></td>
            </tr></table>
          </td></tr>
          <tr><td style="padding:14px 16px 14px; text-align:center;">
            @if($masked)
            {{-- The rail's knob position would reveal the payback even with the
                 number masked, so the buyer edition replaces the whole panel. --}}
            <table cellpadding="0" cellspacing="0" align="center" style="margin-top:10px;"><tr>
              <td><img src="{{ ReportSvg::icon('shield', '#ef6c1f', 2.2) }}" style="width:30px;height:30px;"></td>
            </tr></table>
            <p style="font-size:23px; font-weight:bold; color:#8592a5; margin-top:10px;">{{ $lock($numDec($d['paybackMonths'], 1) . ' MONTHS') }}</p>
            <p style="font-size:8.5px; font-weight:bold; color:#2f4467; letter-spacing:.06em; margin-top:4px;">TO RECOVER CAPITAL INVESTMENT</p>
            <p class="prose" style="margin-top:14px; text-align:left;">The payback period is worked out from the vendor cost, which this edition withholds. Unlock the complete estimate to see both.</p>
            @else
            <p style="font-size:23px; font-weight:bold; color:#12294f;">{{ $numDec($d['paybackMonths'], 1) }} MONTHS</p>
            <p style="font-size:8.5px; font-weight:bold; color:#2f4467; letter-spacing:.06em; margin-top:4px;">TO RECOVER CAPITAL INVESTMENT</p>
            <div style="margin-top:14px;">
              <img src="{{ ReportSvg::progressRail($paybackPct, 339, 20) }}" style="width:339px; height:20px;">
            </div>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:5px;">
              <tr>
                <td width="33%" style="text-align:left;"><p style="font-size:7.5px; color:#5b6779;">0</p></td>
                <td width="34%" style="text-align:center;"><p style="font-size:7.5px; font-weight:bold; color:#12294f;">{{ $numDec($d['paybackMonths'], 1) }} months</p></td>
                <td width="33%" style="text-align:right;"><p style="font-size:7.5px; color:#5b6779;">{{ $num($paybackHorizon) }} months</p></td>
              </tr>
            </table>
            <p class="prose" style="margin-top:12px; text-align:left;">The initial investment in outsourcing can be recovered in {{ $numDec($d['paybackMonths'], 1) }} months through annual cost savings of {{ $moneyK($d['annualCapitalRecovery']) }}, improving cash flow and operational efficiency.</p>
            @endif
          </td></tr>
        </table>
      </div>
      <div style="clear:both;"></div>
    </div>

  </div>

  @include('pdf.estimate._footer', ['page' => 3])
</div>
