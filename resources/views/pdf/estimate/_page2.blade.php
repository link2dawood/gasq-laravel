{{-- PAGE 2 — Detailed cost appraisal comparison: full line-by-line internal vs
     vendor table, plus the period and hourly-rate charts. --}}
@php
    use App\Support\ReportSvg;

    $rows = [
        ['Workforce Baseline Assumption Labor Rate', $money($d['baselineWage']), $lock($money($d['baselineWage']))],
        ['Workforce Cost to Protect Hourly Rate', $money($d['internalTcoHourly']), $lock($money($d['vendorTcoHourly']))],
        ['Overtime / Holiday Rate', $money($d['internalOtHourly']), $lock($money($d['vendorOtHourly']))],
        ['Workforce Annual Cost per Security Professional', $money($d['annualPerInternalFte']), $lock($money($d['annualPerVendorFte']))],
        ['Total Weekly Hours of Coverage', $num($d['weeklyCoverageHours']), $lock($num($d['weeklyCoverageHours']))],
        ['Total Monthly Hours of Coverage', $num($d['monthlyCoverageHours']), $lock($num($d['monthlyCoverageHours']))],
        ['Total Annual Hours of Coverage', $num($d['annualCoverageHours']), $lock($num($d['annualCoverageHours']))],
        ['Total Weeks of Coverage', $num($d['weeksPerYear']), $lock($num($d['weeksPerYear']))],
        ['Total Months of Coverage', $numDec($d['monthsOfCoverage'], 1), $lock($numDec($d['monthsOfCoverage'], 1))],
        ['Total Workforce Required for Coverage', $num($d['ftesRequired']), $lock($num($d['ftesRequired']))],
        ['Total Weekly Cost', $money($d['totalWeeklyInternal']), $lock($money($d['totalWeeklyVendor']))],
        ['Total Monthly Cost', $money($d['totalMonthlyInternal']), $lock($money($d['totalMonthlyVendor']))],
    ];

    // Grouped bar charts: [panel title, icon, axis basis, groups]
    $periodGroups = [
        ['Weekly Cost', $d['totalWeeklyInternal'], $d['totalWeeklyVendor']],
        ['Monthly Cost', $d['totalMonthlyInternal'], $d['totalMonthlyVendor']],
        ['Annual Cost', $d['totalAnnualInternal'], $d['totalAnnualVendor']],
    ];
    $rateGroups = [
        ['Baseline Labor Rate', $d['baselineWage'], $d['baselineWage']],
        ['Standard Hourly Rate', $d['internalTcoHourly'], $d['vendorTcoHourly']],
        ['Overtime / Holiday Rate', $d['internalOtHourly'], $d['vendorOtHourly']],
    ];

    // Panel body is 351px wide (373 − borders − padding): 52 for the value
    // axis, the rest for the plot.
    $axisW = 52;
    $groupPlotW = 299;
    $groupPlotH = 108;
@endphp

<div class="page page-break">
  @include('pdf.estimate._brandbar')

  <div class="body-pad">

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:{{ $masked ? 4 : 14 }}px;">
      <tr>
        <td style="vertical-align:top;">
          <p class="h1" style="font-size:25px;">DETAILED COST APPRAISAL COMPARISON</p>
          <p class="h1-sub">{{ $masked ? 'Buyer Internal Cost to Protect — vendor figures withheld' : 'Buyer Internal Cost to Protect vs Vendor Outsourcing Cost to Protect' }}</p>
        </td>
      </tr>
    </table>

    {{-- ── Summary cards ──────────────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
      <tr>
        @foreach([
            ['stack', 'Buyer Internal Annual Cost', 'bg-navy', 'tint-navy', $moneyK($d['totalAnnualInternal']), 'Total annual in-house cost', false],
            ['users', 'Vendor Outsourcing Annual Cost', 'bg-orange', 'tint-orange', $lock($moneyK($d['totalAnnualVendor'])), 'Total annual vendor cost', false],
            ['chart', 'Operational Capital Recovered', 'bg-green', 'tint-green', $lock($moneyK($d['annualCapitalRecovery'])), $lock($num($d['recoveryPct']) . '% recovered vs in-house'), true],
            ['users', 'Total Staff Required', 'bg-navy', 'tint-navy', $num($d['ftesRequired']), 'FTEs to deliver scope', false],
        ] as $c => [$icon, $label, $headClass, $bodyClass, $value, $sub, $isGreen])
          @if($c > 0)<td width="8"></td>@endif
          <td width="24%" style="vertical-align:top;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr><td class="kpi-head {{ $headClass }}">
                <table width="100%" cellpadding="0" cellspacing="0"><tr>
                  <td width="22" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($icon, '#ffffff') }}" style="width:14px;height:14px;"></td>
                  <td style="vertical-align:middle;"><p>{{ $label }}</p></td>
                </tr></table>
              </td></tr>
              <tr><td class="kpi-body {{ $bodyClass }} {{ $isGreen ? 'green' : '' }}" style="padding:12px 8px 11px;">
                <p class="num" style="font-size:23px;">{{ $value }}</p>
                <p class="sub">{{ $sub }}</p>
              </td></tr>
            </table>
          </td>
        @endforeach
      </tr>
    </table>

    {{-- ── Line-by-line comparison ────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
      <tr><td class="panel-head">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('doc', '#ffffff') }}" style="width:13px;height:13px;"></td>
          <td style="vertical-align:middle;"><p>Detailed Cost Appraisal Comparison</p></td>
        </tr></table>
      </td></tr>
    </table>
    <table class="dtable" cellpadding="0" cellspacing="0">
      <tr class="head">
        <td>Description</td>
        <td class="v" width="168">Buyer Internal Cost to Protect</td>
        <td class="v" width="168">Vendor Outsourcing Cost to Protect</td>
      </tr>
      @foreach($rows as $i => [$label, $internal, $vendor])
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
          <td>{{ $label }}</td>
          <td class="v">{{ $internal }}</td>
          <td class="v">{{ $vendor }}</td>
        </tr>
      @endforeach
      <tr class="total">
        <td>Total Annual Cost</td>
        <td class="v">{{ $money($d['totalAnnualInternal']) }}</td>
        <td class="v">{{ $lock($money($d['totalAnnualVendor'])) }}</td>
      </tr>
      <tr class="recover">
        <td>Operational Capital Recovered</td>
        <td class="v">—</td>
        <td class="v">{{ $lock($money($d['annualCapitalRecovery'])) }}</td>
      </tr>
      <tr class="recover">
        <td>Operational Capital Recovered (%)</td>
        <td class="v">—</td>
        <td class="v">{{ $lock($num($d['recoveryPct']) . '%') }}</td>
      </tr>
      <tr class="recover">
        <td>Payback &amp; Recovery Period</td>
        <td class="v">—</td>
        <td class="v">{{ $lock($numDec($d['paybackMonths'], 1) . ' months') }}</td>
      </tr>
    </table>

    {{-- ── Comparison charts ──────────────────────────────
         The plot is built from divs with negative margins rather than table
         columns: dompdf stretches an image to its table cell (which pushes the
         chart past its panel) but leaves it alone inside a fixed-width div. --}}
    <div style="margin-top:12px;">
      @foreach([
          ['Cost Comparison by Time Period', 'chart', $periodGroups, 'left'],
          ['Hourly Rate Comparison', 'clock', $rateGroups, 'right'],
      ] as [$panelTitle, $panelIcon, $groups, $side])
        @php
          $gAxis = $niceAxis(collect($groups)->flatMap(fn ($g) => [$g[1], $g[2]])->max(), 4);
          $gBarH = fn (float $v) => max(1, (int) round($groupPlotH * ($gAxis['max'] > 0 ? $v / $gAxis['max'] : 0)));
          $gRowH = round($groupPlotH / (count($gAxis['labels']) - 1), 2);
        @endphp
        <div style="width:373px; float:{{ $side }};">
          <table class="panel" cellpadding="0" cellspacing="0" style="width:100%;">
            <tr><td class="panel-head">
              <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($panelIcon, '#ffffff') }}" style="width:13px;height:13px;"></td>
                <td style="vertical-align:middle;"><p>{{ $panelTitle }}</p></td>
              </tr></table>
            </td></tr>
            <tr><td style="padding:9px 10px 10px;">
              {{-- legend --}}
              <table cellpadding="0" cellspacing="0" style="margin-bottom:7px;">
                <tr>
                  @foreach($masked ? [['#12294f', 'Buyer Internal Cost']] : [['#12294f', 'Buyer Internal Cost'], ['#ef6c1f', 'Vendor Outsourcing Cost']] as $lg)
                    <td width="12" style="vertical-align:middle;"><table cellpadding="0" cellspacing="0" width="7"><tr><td style="height:7px; background:{{ $lg[0] }};"></td></tr></table></td>
                    <td style="vertical-align:middle; padding-right:12px;"><p style="font-size:7px; color:#3c4a5e;">{{ $lg[1] }}</p></td>
                  @endforeach
                </tr>
              </table>
              {{-- room for the value labels sitting above the tallest bar --}}
              <div style="height:12px;"></div>
              <div style="margin-left:{{ $axisW }}px; width:{{ $groupPlotW }}px;">
                <img src="{{ ReportSvg::gridlines($groupPlotW, $groupPlotH, count($gAxis['labels']) - 1) }}" style="width:{{ $groupPlotW }}px; height:{{ $groupPlotH }}px;">
              </div>
              {{-- bars, laid back over the gridlines --}}
              <div style="margin-top:-{{ $groupPlotH + 11 }}px; margin-left:{{ $axisW }}px; width:{{ $groupPlotW }}px; height:{{ $groupPlotH + 11 }}px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    @foreach($groups as $g)
                      <td width="33%" style="vertical-align:top;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            @foreach($masked ? [[$g[1], '#12294f']] : [[$g[1], '#12294f'], [$g[2], '#ef6c1f']] as [$val, $color])
                              <td width="{{ $masked ? 100 : 50 }}%" style="vertical-align:top; text-align:center;">
                                <div style="height:{{ $groupPlotH - $gBarH($val) }}px;"></div>
                                <p style="font-size:7px; font-weight:bold; color:#12294f; height:11px;">{{ $moneyK($val) }}</p>
                                <table cellpadding="0" cellspacing="0" width="24" align="center">
                                  <tr><td style="height:{{ $gBarH($val) }}px; background:{{ $color }};"></td></tr>
                                </table>
                              </td>
                            @endforeach
                          </tr>
                        </table>
                      </td>
                    @endforeach
                  </tr>
                </table>
              </div>
              {{-- value axis, laid back over the same band --}}
              <div style="margin-top:-{{ $groupPlotH }}px; width:{{ $axisW - 6 }}px; height:{{ $groupPlotH }}px;">
                @foreach(array_slice(array_reverse($gAxis['labels']), 0, count($gAxis['labels']) - 1) as $l)
                  <div style="height:{{ $gRowH }}px;"><p class="axis" style="margin-top:-3px;">{{ $moneyK($l) }}</p></div>
                @endforeach
                <p class="axis" style="margin-top:-3px;">{{ $moneyK(0) }}</p>
              </div>
              <div style="margin-left:{{ $axisW }}px; width:{{ $groupPlotW }}px; margin-top:5px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    @foreach($groups as $g)
                      <td width="33%" style="text-align:center;"><p style="font-size:7px; color:#5b6779;">{{ $g[0] }}</p></td>
                    @endforeach
                  </tr>
                </table>
              </div>
            </td></tr>
          </table>
        </div>
      @endforeach
      <div style="clear:both;"></div>
    </div>

  </div>

  @include('pdf.estimate._footer', ['page' => 2])
</div>
