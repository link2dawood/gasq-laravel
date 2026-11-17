{{-- PAGE 2 — How each column was arrived at, then certification, IP, disclaimer. --}}
@php
    use App\Support\ReportSvg;

    $in = $r['internal'];
    $ve = $r['vendor'];
    $c = $r['coverage'];
    $agreement = $r['agreement'];

    // profit_margin is stored as decimal(6,4) and has been written both as a
    // fraction (0.12) and as a percentage (12). Anything at or below 1 is read
    // as a fraction.
    $marginPct = $ve['profitMargin'] > 0
        ? ($ve['profitMargin'] <= 1 ? $ve['profitMargin'] * 100 : $ve['profitMargin'])
        : 0.0;

    $buildUp = array_values(array_filter([
        $ve['baseWage'] > 0 ? ['Base wage', $money($ve['baseWage']).' / hr'] : null,
        $ve['employerBurden'] > 0 ? ['Employer burden', $money($ve['employerBurden']).' / hr'] : null,
        $ve['workforceMaintenanceCost'] > 0 ? ['Workforce maintenance', $money($ve['workforceMaintenanceCost']).' / hr'] : null,
        $ve['costToDeliver'] > 0 ? ['Cost to deliver', $money($ve['costToDeliver']).' / hr'] : null,
        $marginPct > 0 ? ['Profit margin', $numDec($marginPct, 1).'%'] : null,
    ]));

    $sections = [
        ['gear', 'How the Agreed Vendor Rate Was Calculated', [
            'The vendor rate is the rate the GASQ platform calculated from the vendor\'s own workforce build-up for the scope on page 1, and which both parties agreed. It is not a discount applied to an in-house figure, and not a number typed in by either party. Because it is calculated per scope, the capital recovery and payback figures are specific to this engagement: a change to the hours, posts, wage or term creates a new scope version, which is priced and reported again.',
        ]],
        ['chart', 'How Your In-House Cost Was Arrived At', [
            $in['basis'].' '.($in['source'] === 'modelled'
                ? 'The GASQ Cost to Protect method loads the baseline wage to a fully burdened wage, spreads it across the paid hours behind one full-time security professional, and reduces it to the hours actually billable to a post.'
                : 'It is used exactly as provided, with no adjustment, so the comparison reflects the cost you carry today rather than a modelled figure.'),
        ]],
        ['doc', 'GASQ Certified Statement', [
            'This report was prepared using the GASQ Cost to Protect™ methodology and compares the estimated cost of performing the security function in-house against outsourcing it to a qualified provider. It has been reviewed for pricing realism, workforce availability, staffing assumptions and coverage sustainability.',
        ]],
        ['shield', 'Intellectual Property & Disclaimer', [
            'The concepts, methodologies, calculations, presentation formats and analytical frameworks in this report are proprietary intellectual property of GASQ. Unauthorized reproduction, reverse engineering, redistribution, resale, modification, commercial use or creation of derivative works is prohibited without written authorization.',
            'This report is for budgeting, procurement planning and cost comparison. Actual wages, benefits, insurance, turnover, supervision, market conditions and site-specific requirements may affect final pricing. Pricing is valid until the date on page 1, and this report is intended solely for the named recipient.',
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
          <p class="h1" style="font-size:25px;">METHOD, CERTIFICATION</p>
          <p class="h1 h1-accent" style="font-size:25px;">&amp; DISCLAIMER</p>
          <p class="h1-sub">How both columns on page 1 were calculated</p>
        </td>
        <td width="250" style="vertical-align:top;">
          <p style="font-size:11px; font-weight:bold; color:#12294f; margin-bottom:6px;">Report Information</p>
          <table width="100%" cellpadding="0" cellspacing="0">
            @foreach([
                ['doc', 'Estimate ID', $estimate->public_id],
                ['doc', 'Report Type', $reportType],
                ['calendar', 'Report Date', $reportDate],
                ['check', 'Scope Version', 'v'.$agreement['scopeVersion'].($agreement['agreed'] ? ' — agreed' : ' — provisional')],
            ] as $i => [$icon, $label, $value])
              <tr>
                <td width="26" style="vertical-align:middle; padding-bottom:{{ $i === 3 ? 0 : 6 }}px;"><img src="{{ ReportSvg::icon($icon, '#12294f') }}" style="width:16px;height:16px;"></td>
                <td style="vertical-align:middle; padding-bottom:{{ $i === 3 ? 0 : 6 }}px;">
                  <p class="meta-label">{{ $label }}</p>
                  <p class="meta-value">{{ $value }}</p>
                </td>
              </tr>
            @endforeach
          </table>
        </td>
      </tr>
    </table>

    {{-- ── Agreement statement ────────────────────────────── --}}
    <table class="contact" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
      <tr>
        @foreach([
            ['user', 'Scope Approved By Buyer', $agreement['buyerApprovedAt'] ? $agreement['buyerApprovedAt']->format('M j, Y') : 'Pending'],
            ['users', 'Scope Approved By Vendor', $agreement['vendorApprovedAt'] ? $agreement['vendorApprovedAt']->format('M j, Y') : 'Pending'],
            ['calculator', 'Rate Calculated', $agreement['pricedAt'] ? $agreement['pricedAt']->format('M j, Y') : '—'],
        ] as $i => [$icon, $label, $value])
          <td width="33%" class="{{ $i === 2 ? 'last' : '' }}">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="22" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($icon, '#5b7096') }}" style="width:14px;height:14px;"></td>
                <td style="vertical-align:middle;">
                  <p class="k">{{ $label }}</p>
                  <p class="v">{{ $value }}</p>
                </td>
              </tr>
            </table>
          </td>
        @endforeach
      </tr>
    </table>

    {{-- ── Agreed rate build-up ───────────────────────────── --}}
    @if($buildUp)
      <table class="panel" width="100%" cellpadding="0" cellspacing="0" style="margin-top:11px;">
        <tr><td class="panel-head">
          <table width="100%" cellpadding="0" cellspacing="0"><tr>
            <td width="20" style="vertical-align:middle;"><img src="{{ ReportSvg::icon('handshake', '#ffffff') }}" style="width:13px;height:13px;"></td>
            <td style="vertical-align:middle;"><p>Agreed Rate Build-Up</p></td>
          </tr></table>
        </td></tr>
      </table>
      <table class="dtable" cellpadding="0" cellspacing="0">
        @foreach($buildUp as $i => [$label, $value])
          <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td>{{ $label }}</td>
            <td class="v" width="200">{{ $value }}</td>
          </tr>
        @endforeach
        <tr class="total">
          <td>Agreed Bill Rate</td>
          <td class="v">{{ $money($ve['hourly']) }} / hr</td>
        </tr>
        <tr class="recover">
          <td>Agreed Annual Cost ({{ $num($c['annualHours']) }} hours)</td>
          <td class="v">{{ $money($ve['annual']) }}</td>
        </tr>
      </table>
    @endif

    {{-- ── Statements ─────────────────────────────────────── --}}
    @foreach($sections as [$icon, $heading, $paras])
      <table class="panel" width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;">
        <tr><td class="panel-head">
          <table width="100%" cellpadding="0" cellspacing="0"><tr>
            <td width="22" style="vertical-align:middle;"><img src="{{ ReportSvg::icon($icon, '#ffffff') }}" style="width:14px;height:14px;"></td>
            <td style="vertical-align:middle;"><p>{{ $heading }}</p></td>
          </tr></table>
        </td></tr>
        <tr><td style="padding:9px 13px 10px; background:#f7f9fc;">
          @foreach($paras as $para)
            <p class="prose">{{ $para }}</p>
          @endforeach
        </td></tr>
      </table>
    @endforeach

  </div>

  @include('pdf.estimate._footer', ['page' => 2, 'lastPage' => true, 'compact' => true])
</div>
