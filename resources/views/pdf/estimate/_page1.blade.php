{{-- PAGE 1 — Estimate Dashboard: headline KPIs, coverage assumptions, charts. --}}
@php
    use App\Support\ReportSvg;

    $axis = $niceAxis(max($d['totalAnnualInternal'], $d['totalAnnualVendor']), 7);
    // Comparison panel is 430px wide → 408px of content: 52 axis + 196 plot,
    // with the savings callout laid over the right-hand 146px.
    $axisW = 52;
    $plotW = 196;
    $plotH = 166;
    $chartH = 205;
    $barH = fn (float $v) => max(2, (int) round($plotH * ($axis['max'] > 0 ? $v / $axis['max'] : 0)));

    $assumptions = [
        ['calendar', $num($d['annualCoverageHours']), 'Annual Coverage Hours'],
        ['calendar', $num($d['weeklyCoverageHours']), 'Weekly Hours'],
        ['calendar', $num($d['monthlyCoverageHours']), 'Monthly Hours'],
        ['$', $money($d['baselineWage']), 'Workforce Baseline Assumption Labor Rate'],
        ['%', $d['recoveryPct'] . '%', 'Operational Capital Recovered'],
        ['trend', $numDec($d['paybackMonths'], 1) . ' months', 'Payback & Recovery Period'],
    ];

    // Donut share labels sit inside the ring: place each at the mid-angle of its
    // slice, then emit them top-down as spacer rows (dompdf has no reliable
    // absolute positioning inside table cells).
    $donutSize = 160;
    $donutThickness = 26;
    // Centre-line radius of the ring stroke, matching ReportSvg::donut().
    $ringR = ($donutSize - $donutThickness) / 2 - 1;
    $split = 360 * $d['internalSharePct'] / 100;
    $ringLabel = function (float $angle) use ($donutSize, $ringR) {
        $a = deg2rad($angle - 90);
        return [
            'x' => $donutSize / 2 + $ringR * cos($a),
            'y' => $donutSize / 2 + $ringR * sin($a),
        ];
    };
    $donutLabels = [
        ['pct' => $d['internalSharePct']] + $ringLabel($split / 2),
        ['pct' => $d['vendorSharePct']] + $ringLabel(($split + 360) / 2),
    ];
    usort($donutLabels, fn ($a, $b) => $a['y'] <=> $b['y']);
    // Left edge of the donut inside the breakdown panel (316px column − borders −
    // padding = 290px of content), used to place the ring labels horizontally.
    $donutInset = (290 - $donutSize) / 2;
@endphp

<div class="page page-break">
  @include('pdf.estimate._brandbar')

  <div class="body-pad">

    {{-- ── Title + report meta ─────────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
      <tr>
        <td style="vertical-align:top;">
          <p class="h1">GASQ COST TO PROTECT</p>
          <p class="h1 h1-accent">ESTIMATE DASHBOARD</p>
          <p class="h1-sub">Buyer Internal vs Vendor Outsourcing Cost to Protect</p>
        </td>
        <td width="248" style="vertical-align:top; padding-top:4px;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td width="26" style="vertical-align:middle; padding-bottom:10px;"><img src="{{ ReportSvg::icon('doc', '#12294f') }}" style="width:17px;height:17px;"></td>
              <td style="vertical-align:middle; padding-bottom:10px;">
                <p class="meta-label">Report ID</p>
                <p class="meta-value">{{ $reportNumber }}</p>
              </td>
            </tr>
            <tr>
              <td style="vertical-align:middle;"><img src="{{ ReportSvg::icon('calendar', '#12294f') }}" style="width:17px;height:17px;"></td>
              <td style="vertical-align:middle;">
                <p class="meta-label">Report Date</p>
                <p class="meta-value">{{ $reportDate }}</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    {{-- ── Contact strip ───────────────────────────────────── --}}
    <table class="contact" width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px;">
      <tr>
        @foreach([
            ['user', 'Vendor Contact', $d['contact']['name'] ?: '—', $d['contact']['company'], '25%'],
            ['mail', 'Email', $d['contact']['email'] ?: '—', null, '25%'],
            ['phone', 'Phone', $d['contact']['phone'] ?: '—', null, '24%'],
            ['doc', 'Report Type', $reportType, null, '26%'],
        ] as $i => [$icon, $label, $value, $second, $w])
          <td width="{{ $w }}" class="{{ $i === 3 ? 'last' : '' }}">
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

    {{-- ── Headline KPI cards ──────────────────────────────── --}}
    @php
      $kpiRows = [
        [
          ['stack', 'Buyer Internal Cost to Protect', 'bg-navy', 'tint-navy', $moneyK($d['totalAnnualInternal']), 'Total annual in-house cost', false],
          ['chart', 'Annual Capital Recovery', 'bg-green', 'tint-green', $moneyK($d['annualCapitalRecovery']), $d['recoveryPct'] . '% recovered vs in-house', true],
          ['users', 'Vendor Outsourcing Cost to Protect', 'bg-orange', 'tint-orange', $moneyK($d['totalAnnualVendor']), 'Total annual vendor cost', false],
        ],
        [
          ['clock', 'Buyer Internal Cost to Protect Hourly Rate', 'bg-navy', 'tint-navy', $money($d['internalTcoHourly']), 'Buyer in-house cost per hour', false],
          ['users', 'Total Staff Required', 'bg-navy', 'tint-navy', $d['ftesRequired'] . ' FTEs', 'To deliver scope', false],
          ['clock', 'Vendor Outsourcing Cost to Protect Hourly Rate', 'bg-orange', 'tint-orange', $money($d['vendorTcoHourly']), 'Vendor rate offered', false],
        ],
      ];
    @endphp
    @foreach($kpiRows as $r => $cards)
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:{{ $r === 0 ? 14 : 8 }}px;">
        <tr>
          @foreach($cards as $c => [$icon, $label, $headClass, $bodyClass, $value, $sub, $isGreen])
            @if($c > 0)<td width="8"></td>@endif
            <td width="32%" style="vertical-align:top;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td class="kpi-head {{ $headClass }}">
                  <table width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td width="22" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($icon, '#ffffff') }}" style="width:14px;height:14px;"></td>
                    <td style="vertical-align:middle;"><p>{{ $label }}</p></td>
                  </tr></table>
                </td></tr>
                <tr><td class="kpi-body {{ $bodyClass }} {{ $isGreen ? 'green' : '' }}">
                  <p class="num">{{ $value }}</p>
                  <p class="sub">{{ $sub }}</p>
                </td></tr>
              </table>
            </td>
          @endforeach
        </tr>
      </table>
    @endforeach

    {{-- ── Key assumptions & coverage metrics ──────────────── --}}
    <table class="panel" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
      <tr><td class="panel-head light">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('doc', '#12294f') }}" style="width:13px;height:13px;"></td>
          <td style="vertical-align:middle;"><p>Key Assumptions &amp; Coverage Metrics</p></td>
        </tr></table>
      </td></tr>
      <tr><td>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            @foreach($assumptions as $i => [$icon, $value, $label])
              <td width="16.6%" style="padding:11px 6px; text-align:center; border-right:{{ $i === 5 ? '0' : '1px solid #e6ebf3' }};">
                @if(in_array($icon, ['$', '%'], true))
                  <p style="font-size:16px; font-weight:bold; color:#12294f; line-height:1;">{{ $icon }}</p>
                @else
                  <table cellpadding="0" cellspacing="0" align="center"><tr><td><img src="{{ ReportSvg::icon($icon, '#12294f') }}" style="width:16px;height:16px;"></td></tr></table>
                @endif
                <p style="font-size:13.5px; font-weight:bold; color:#12294f; margin-top:5px;">{{ $value }}</p>
                <p style="font-size:7px; color:#5b6779; margin-top:3px; line-height:1.35;">{{ $label }}</p>
              </td>
            @endforeach
          </tr>
        </table>
      </td></tr>
    </table>

    {{-- ── Charts ──────────────────────────────────────────── --}}
    {{-- Floated fixed-width columns, not a table: dompdf's table layout resizes
         cells to their content (and its table-layout:fixed is unreliable), while a
         floated div honours an exact pixel width — which the donut inset below
         depends on. --}}
    <div style="margin-top:12px;">
      <div style="width:430px; float:left;">
        {{-- Annual cost comparison --}}
        <table class="panel" cellpadding="0" cellspacing="0" style="width:100%;">
            <tr><td class="panel-head">
              <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('chart', '#ffffff') }}" style="width:13px;height:13px;"></td>
                <td style="vertical-align:middle;"><p>Annual Cost Comparison</p></td>
              </tr></table>
            </td></tr>
            <tr><td style="padding:12px 10px 12px;">
              {{-- Chart in a fixed-height block with the savings callout laid over
                   it: dompdf re-proportions table columns, which pushes the callout
                   past the panel edge. --}}
              <div style="height:{{ $chartH }}px;">
                <div style="height:14px;"></div>
                <div style="margin-left:{{ $axisW }}px; width:{{ $plotW }}px;">
                  <img src="{{ ReportSvg::gridlines($plotW, $plotH, count($axis['labels']) - 1) }}" style="width:{{ $plotW }}px; height:{{ $plotH }}px;">
                </div>
                {{-- Bars start one label-height above the plot so a full-height bar
                     still has room for its value label. --}}
                <div style="margin-top:-{{ $plotH + 13 }}px; margin-left:{{ $axisW }}px; width:{{ $plotW }}px; height:{{ $plotH + 13 }}px;">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      @foreach([[$d['totalAnnualInternal'], '#12294f'], [$d['totalAnnualVendor'], '#ef6c1f']] as [$val, $color])
                        <td width="50%" style="vertical-align:top; text-align:center;">
                          <div style="height:{{ $plotH - $barH($val) }}px;"></div>
                          <p style="font-size:9px; font-weight:bold; color:#12294f; height:13px;">{{ $moneyK($val) }}</p>
                          <table cellpadding="0" cellspacing="0" width="58" align="center">
                            <tr><td style="height:{{ $barH($val) }}px; background:{{ $color }};"></td></tr>
                          </table>
                        </td>
                      @endforeach
                    </tr>
                  </table>
                </div>
                <div style="margin-top:-{{ $plotH }}px; width:{{ $axisW - 6 }}px; height:{{ $plotH }}px;">
                  @foreach(array_slice(array_reverse($axis['labels']), 0, count($axis['labels']) - 1) as $l)
                    <div style="height:{{ round($plotH / (count($axis['labels']) - 1), 2) }}px;"><p class="axis" style="margin-top:-3px;">{{ $moneyK($l) }}</p></div>
                  @endforeach
                  <p class="axis" style="margin-top:-3px;">{{ $moneyK(0) }}</p>
                </div>
                <div style="margin-left:{{ $axisW }}px; width:{{ $plotW }}px; margin-top:6px;">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td width="50%" style="text-align:center;"><p style="font-size:7px; color:#5b6779; line-height:1.35;">Buyer Internal<br>Cost to Protect</p></td>
                      <td width="50%" style="text-align:center;"><p style="font-size:7px; color:#5b6779; line-height:1.35;">Vendor Outsourcing<br>Cost to Protect</p></td>
                    </tr>
                  </table>
                </div>
              </div>
              <div style="margin-top:-{{ $chartH }}px; height:{{ $chartH }}px;">
                <div style="height:14px;"></div>
                {{-- 262 + 126 content + 16 padding + 2 border = the panel's 408px of content --}}
                <div style="margin-left:262px; width:126px; background:#e8f5ec; border:1px solid #bfe0cb; padding:14px 8px; text-align:center;">
                  <table cellpadding="0" cellspacing="0" align="center"><tr>
                    <td style="vertical-align:middle;"><img src="{{ ReportSvg::icon('arrow-down', '#16794a', 2.4) }}" style="width:20px;height:20px;"></td>
                    <td style="vertical-align:middle; padding-left:3px;"><p style="font-size:27px; font-weight:bold; color:#16794a; line-height:1;">{{ $d['recoveryPct'] }}%</p></td>
                  </tr></table>
                  <p style="font-size:8px; font-weight:bold; color:#15794a; letter-spacing:.06em; margin-top:6px; line-height:1.4;">LOWER COST WITH<br>VENDOR OUTSOURCING</p>
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin:10px 0;"><tr><td style="height:1px; background:#bfe0cb;"></td></tr></table>
                  <p style="font-size:17px; font-weight:bold; color:#16794a;">{{ $moneyK($d['annualCapitalRecovery']) }}</p>
                  <p style="font-size:7px; color:#3f6b53; margin-top:4px; line-height:1.4;">in capital recovered<br>vs in-house</p>
                </div>
              </div>
            </td></tr>
          </table>
      </div>
      <div style="width:316px; float:right;">
        {{-- Cost breakdown donut --}}
        <table class="panel" cellpadding="0" cellspacing="0" style="width:100%;">
            <tr><td class="panel-head">
              <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('pie', '#ffffff') }}" style="width:13px;height:13px;"></td>
                <td style="vertical-align:middle;"><p>Cost Breakdown (Annual)</p></td>
              </tr></table>
            </td></tr>
            <tr><td style="padding:12px 12px 14px;">
              {{-- Donut. The image is laid down first and the labels pulled back
                   over it with negative margins; the whole stack sits in a
                   fixed-height box so the legend below is unaffected by them. --}}
              @php
                $ringCursor = 0;
                $ringRows = [];
                foreach ($donutLabels as $lab) {
                    $top = max(0, (int) round($lab['y'] - 5) - $ringCursor);
                    $ringCursor += $top + 11;
                    $ringRows[] = ['top' => $top, 'left' => max(0, (int) round($donutInset + $lab['x'] - 17)), 'pct' => $lab['pct']];
                }
                $centreTop = (int) round($donutSize / 2) - 22;
              @endphp
              <div style="height:{{ $donutSize }}px;">
                <div style="margin-left:{{ (int) round($donutInset) }}px;">
                  <img src="{{ ReportSvg::donut($d['internalSharePct'], '#12294f', '#ef6c1f', $donutSize, $donutThickness) }}" style="width:{{ $donutSize }}px; height:{{ $donutSize }}px;">
                </div>
                <div style="margin-top:-{{ $donutSize }}px;">
                  @foreach($ringRows as $row)
                    <div style="height:{{ $row['top'] }}px;"></div>
                    <p class="ring-pct" style="width:34px; padding-left:{{ $row['left'] }}px;">{{ $numDec($row['pct'], 1) }}%</p>
                  @endforeach
                </div>
                <div style="margin-top:-{{ max(0, $ringCursor - $centreTop) }}px; text-align:center;">
                  <p style="font-size:11.5px; font-weight:bold; color:#12294f;">{{ $moneyK($d['combinedAnnual']) }}</p>
                  <p style="font-size:6.5px; color:#5b6779; margin-top:3px; line-height:1.35;">Total Combined<br>Annual Cost</p>
                </div>
              </div>
              {{-- legend --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:10px;">
                @foreach([
                    ['#12294f', 'Buyer Internal Cost to Protect', $d['totalAnnualInternal']],
                    ['#ef6c1f', 'Vendor Outsourcing Cost to Protect', $d['totalAnnualVendor']],
                ] as $legend)
                  <tr>
                    <td width="14" style="vertical-align:middle; padding:4px 0;">
                      <table cellpadding="0" cellspacing="0" width="8"><tr><td style="height:8px; background:{{ $legend[0] }};"></td></tr></table>
                    </td>
                    <td style="vertical-align:middle; padding:4px 0;"><p style="font-size:7.5px; color:#3c4a5e;">{{ $legend[1] }}</p></td>
                    <td style="vertical-align:middle; padding:4px 0; text-align:right;"><p style="font-size:8px; font-weight:bold; color:#12294f;">{{ $moneyK($legend[2]) }}</p></td>
                  </tr>
                @endforeach
              </table>
            </td></tr>
        </table>
      </div>
      <div style="clear:both;"></div>
    </div>

  </div>

  @include('pdf.estimate._footer', ['page' => 1])
</div>
