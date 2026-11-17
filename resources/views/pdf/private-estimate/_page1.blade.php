{{-- PAGE 1 — Scope priced, side-by-side comparison, budget reconciliation. --}}
@php
    use App\Support\ReportSvg;

    $c = $r['coverage'];
    $in = $r['internal'];
    $ve = $r['vendor'];
    $rec = $r['recovery'];
    $budget = $r['budget'];
    $agreement = $r['agreement'];
    $scope = $r['scope'];

    $serviceLabel = \App\Support\PrivateEstimate::serviceTypes()[$scope->service_type] ?? ($scope->service_type ? ucwords(str_replace('_', ' ', $scope->service_type)) : 'Security services');

    $rows = [
        ['Workforce Baseline Assumption Labor Rate', $in['baselineWage'] > 0 ? $money($in['baselineWage']) : '—', $ve['baseWage'] > 0 ? $money($ve['baseWage']) : '—'],
        ['Cost to Protect Hourly Rate', $money($in['hourly']), $money($ve['hourly'])],
        ['Overtime / Holiday Rate', $money($in['otHourly']), $money($ve['otHourly'])],
        ['Total Weekly Hours of Coverage', $num($c['weeklyHours']), $num($c['weeklyHours'])],
        ['Total Annual Hours of Coverage', $num($c['annualHours']), $num($c['annualHours'])],
        ['Total Workforce Required for Coverage', $num($c['ftesRequired']), $num($c['ftesRequired'])],
        ['Total Weekly Cost', $money($in['weekly']), $money($ve['weekly'])],
        ['Total Monthly Cost', $money($in['monthly']), $money($ve['monthly'])],
    ];
@endphp

<div class="page page-break">
  @include('pdf.estimate._brandbar')

  <div class="body-pad">

    {{-- ── Title + report information ──────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:10px;">
      <tr>
        <td style="vertical-align:top;">
          <p class="h1" style="font-size:25px;">COST COMPARISON</p>
          <p class="h1 h1-accent" style="font-size:25px;">FOR YOUR SCOPE</p>
          <p class="h1-sub">{{ $in['label'] }} vs {{ $ve['label'] }}</p>
        </td>
        <td width="250" style="vertical-align:top;">
          <table width="100%" cellpadding="0" cellspacing="0">
            @foreach([
                ['doc', 'Estimate ID', $estimate->public_id],
                ['calendar', 'Report Date', $reportDate],
                ['clock', 'Pricing Valid Until', $agreement['validUntil'] ? $agreement['validUntil']->format('F j, Y') : 'On request'],
            ] as $i => [$icon, $label, $value])
              <tr>
                <td width="26" style="vertical-align:middle; padding-bottom:{{ $i === 2 ? 0 : 6 }}px;"><img src="{{ ReportSvg::icon($icon, '#12294f') }}" style="width:16px;height:16px;"></td>
                <td style="vertical-align:middle; padding-bottom:{{ $i === 2 ? 0 : 6 }}px;">
                  <p class="meta-label">{{ $label }}</p>
                  <p class="meta-value">{{ $value }}</p>
                </td>
              </tr>
            @endforeach
          </table>
        </td>
      </tr>
    </table>

    {{-- ── Parties ────────────────────────────────────────── --}}
    <table class="contact" width="100%" cellpadding="0" cellspacing="0" style="margin-top:10px;">
      <tr>
        @foreach([
            ['user', 'Prepared For', $parties['buyerContact'] ?: ($parties['buyerCompany'] ?: $parties['buyerEmail']), $parties['buyerCompany']],
            ['stack', 'Site', $parties['siteName'] ?: '—', $parties['serviceAddress']],
            ['users', 'Security Vendor', $parties['vendorName'], $parties['vendorContact']],
        ] as $i => [$icon, $label, $value, $second])
          <td width="33%" class="{{ $i === 2 ? 'last' : '' }}">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="22" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($icon, '#5b7096') }}" style="width:14px;height:14px;"></td>
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

    {{-- ── Agreed rate band ───────────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px; background:{{ $agreement['agreed'] ? '#12294f' : '#8a4b10' }};">
      <tr>
        <td style="padding:6px 12px;">
          <table width="100%" cellpadding="0" cellspacing="0"><tr>
            <td width="22" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($agreement['agreed'] ? 'handshake' : 'clock', '#ef6c1f') }}" style="width:14px;height:14px;"></td>
            <td style="vertical-align:middle;">
              <p style="font-size:8px; font-weight:bold; color:#ffffff; letter-spacing:.07em;">{{ strtoupper($agreement['agreed'] ? 'AGREED RATE — SCOPE VERSION '.$agreement['scopeVersion'].' APPROVED BY BOTH PARTIES' : 'PROVISIONAL RATE — SCOPE VERSION '.$agreement['scopeVersion'].' AWAITING APPROVAL') }}</p>
            </td>
            <td style="vertical-align:middle; text-align:right;">
              <p style="font-size:7.5px; color:#cdd9ec;">Rate calculated by the GASQ platform</p>
            </td>
          </tr></table>
        </td>
      </tr>
    </table>

    {{-- ── Scope priced ───────────────────────────────────── --}}
    <table class="panel" width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;">
      <tr><td class="panel-head">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('shield', '#ffffff') }}" style="width:13px;height:13px;"></td>
          <td style="vertical-align:middle;"><p>Scope Priced</p></td>
        </tr></table>
      </td></tr>
      <tr><td style="padding:9px 12px 10px; background:#f7f9fc;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            @foreach([
                ['Service', $serviceLabel],
                ['Posts', $num($c['posts'])],
                ['Locations', $num($c['locations'])],
                ['Hours / Day', $c['hoursPerDay'] > 0 ? $numDec($c['hoursPerDay'], 1) : '—'],
                ['Days / Week', $c['daysPerWeek'] > 0 ? $numDec($c['daysPerWeek'], 1) : '—'],
                ['Weeks / Year', $num($c['weeksPerYear'])],
                ['Annual Hours', $num($c['annualHours'])],
                ['Start', $scope->start_date ? $scope->start_date->format('M j, Y') : 'On agreement'],
            ] as $i => [$label, $value])
              @if($i > 0)<td width="6"></td>@endif
              <td style="vertical-align:top;">
                <p class="k" style="font-size:7px; color:#7b8798; letter-spacing:.08em; text-transform:uppercase;">{{ $label }}</p>
                <p style="font-size:10.5px; font-weight:bold; color:#12294f; margin-top:2px;">{{ $value }}</p>
              </td>
            @endforeach
          </tr>
        </table>
      </td></tr>
    </table>

    {{-- ── Summary cards ──────────────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;">
      <tr>
        @foreach([
            ['stack', 'Your In-House Annual Cost', 'bg-navy', 'tint-navy', $moneyK($in['annual']), $in['source'] === 'stated' ? 'As you stated it' : 'GASQ modelled', false],
            ['handshake', 'Agreed Vendor Annual Cost', 'bg-orange', 'tint-orange', $moneyK($ve['annual']), $money($ve['hourly']).' per hour', false],
            [$rec['positive'] ? 'chart' : 'trend', $rec['positive'] ? 'Operational Capital Recovered' : 'Additional Annual Cost', $rec['positive'] ? 'bg-green' : 'bg-navy', $rec['positive'] ? 'tint-green' : 'tint-navy', $moneyK(abs($rec['annual'])), $numDec(abs($rec['pct']), 0).'% vs in-house', $rec['positive']],
            ['users', 'Workforce Required', 'bg-navy', 'tint-navy', $num($c['ftesRequired']), 'FTEs to deliver scope', false],
        ] as $i => [$icon, $label, $headClass, $bodyClass, $value, $sub, $isGreen])
          @if($i > 0)<td width="8"></td>@endif
          <td width="24%" style="vertical-align:top;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr><td class="kpi-head {{ $headClass }}">
                <table width="100%" cellpadding="0" cellspacing="0"><tr>
                  <td width="22" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($icon, '#ffffff') }}" style="width:14px;height:14px;"></td>
                  <td style="vertical-align:middle;"><p>{{ $label }}</p></td>
                </tr></table>
              </td></tr>
              <tr><td class="kpi-body {{ $bodyClass }} {{ $isGreen ? 'green' : '' }}" style="padding:10px 8px 9px;">
                <p class="num" style="font-size:22px;">{{ $value }}</p>
                <p class="sub">{{ $sub }}</p>
              </td></tr>
            </table>
          </td>
        @endforeach
      </tr>
    </table>

    {{-- ── Line-by-line comparison ────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:9px;">
      <tr><td class="panel-head">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('doc', '#ffffff') }}" style="width:13px;height:13px;"></td>
          <td style="vertical-align:middle;"><p>Side-by-Side Cost Comparison</p></td>
        </tr></table>
      </td></tr>
    </table>
    <table class="dtable" cellpadding="0" cellspacing="0">
      <tr class="head">
        <td>Description</td>
        <td class="v" width="168">{{ $in['label'] }}</td>
        <td class="v" width="168">{{ $ve['label'] }}</td>
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
        <td class="v">{{ $money($in['annual']) }}</td>
        <td class="v">{{ $money($ve['annual']) }}</td>
      </tr>
      <tr class="recover">
        <td>{{ $rec['positive'] ? 'Operational Capital Recovered' : 'Additional Cost vs In-House' }}</td>
        <td class="v">&mdash;</td>
        <td class="v">{{ $money(abs($rec['annual'])) }}</td>
      </tr>
      <tr class="recover">
        <td>{{ $rec['positive'] ? 'Operational Capital Recovered (%)' : 'Additional Cost (%)' }}</td>
        <td class="v">&mdash;</td>
        <td class="v">{{ $numDec(abs($rec['pct']), 1) }}%</td>
      </tr>
      <tr class="recover">
        <td>Payback &amp; Recovery Period</td>
        <td class="v">&mdash;</td>
        <td class="v">{{ $rec['positive'] ? $numDec($rec['paybackMonths'], 1).' months' : 'No recovery at this price' }}</td>
      </tr>
    </table>

    {{-- ── Budget reconciliation ──────────────────────────── --}}
    <table class="panel" width="100%" cellpadding="0" cellspacing="0" style="margin-top:9px;">
      <tr><td class="panel-head">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('calculator', '#ffffff') }}" style="width:13px;height:13px;"></td>
          <td style="vertical-align:middle;"><p>Budget Reconciliation</p></td>
        </tr></table>
      </td></tr>
      <tr><td style="padding:10px 12px 11px; background:{{ $budget['known'] ? ($budget['reconciles'] ? '#eff7f1' : '#fdf1ec') : '#f7f9fc' }};">
        @if($budget['known'])
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              @foreach([
                  [$budget['statusLabel'], $moneyK($budget['amount']).' '.$budget['basisLabel']],
                  ['Approved Budget (Annual)', $moneyK($budget['annual'])],
                  ['Agreed Annual Cost', $moneyK($ve['annual'])],
                  [$budget['withinBudget'] ? 'Remaining Against Budget' : 'Over Budget By', $moneyK(abs((float) $budget['variance']))],
              ] as $i => [$label, $value])
                @if($i > 0)<td width="10"></td>@endif
                <td width="24%" style="vertical-align:top;">
                  <p class="k" style="font-size:7px; color:#7b8798; letter-spacing:.08em; text-transform:uppercase;">{{ $label }}</p>
                  <p style="font-size:14px; font-weight:bold; color:{{ $i === 3 ? ($budget['withinBudget'] ? '#15794a' : '#b3400f') : '#12294f' }}; margin-top:2px;">{{ $value }}</p>
                </td>
              @endforeach
            </tr>
          </table>
        @endif
        <p class="prose" style="margin-top:{{ $budget['known'] ? 8 : 0 }}px;">{{ $budget['statement'] }}</p>
      </td></tr>
    </table>

  </div>

  @include('pdf.estimate._footer', ['page' => 1, 'compact' => false])
</div>
