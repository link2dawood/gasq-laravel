{{--
    Footer block for the master estimate: the four value props, the report
    identity bar and the certification strip. Pinned to the bottom of .page.

    Vars: $page (int), $pages (int), $reportNumber, $reportType, $reportDate,
          $orgName, $lastPage (bool) — the closing page swaps the identity bar
          for the copyright line.
--}}
@php
    use App\Support\ReportSvg;

    $props = [
        ['target', 'Know your true cost to protect', 'Make informed decisions with real data.'],
        ['chart', 'Compare internal cost vs vendor cost', 'See the full financial picture.'],
        ['gear', 'Recover capital through smarter procurement', 'Unlock value and improve ROI.'],
        ['shield-check', 'CFO tested. CFO approved.', 'Independent. Objective. Trusted.'],
    ];
@endphp
<div class="footer">
  <table class="valueprops" cellpadding="0" cellspacing="0">
    <tr>
      @foreach($props as $i => [$icon, $title, $sub])
        <td width="25%" class="{{ $i === 3 ? 'last' : '' }}">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td width="30" style="vertical-align:top;">
                <img src="{{ ReportSvg::icon($icon, '#ef6c1f', 2) }}" style="width:21px; height:21px;">
              </td>
              <td style="vertical-align:top; padding-left:2px;">
                <p class="t">{{ $title }}</p>
                <p class="s">{{ $sub }}</p>
              </td>
            </tr>
          </table>
        </td>
      @endforeach
    </tr>
  </table>

  <table class="footbar" cellpadding="0" cellspacing="0">
    <tr>
      <td style="vertical-align:middle;">
        <p class="org">{{ $orgName }}</p>
        <p class="meta">{{ $reportNumber }} &nbsp;·&nbsp; {{ $reportType }} &nbsp;·&nbsp; Confidential — for authorized recipients only</p>
      </td>
      <td style="vertical-align:middle; text-align:right;">
        <p class="org">{{ $reportDate }}</p>
        <p class="meta">Page {{ $page }} of {{ $pages }}</p>
      </td>
    </tr>
  </table>

  <table class="certstrip" cellpadding="0" cellspacing="0">
    <tr><td>
      @if(! empty($lastPage))
        © {{ now()->format('Y') }} GASQ &nbsp;•&nbsp; ALL RIGHTS RESERVED &nbsp;•&nbsp; CFO TESTED. CFO APPROVED. &nbsp;•&nbsp; THE INDUSTRY PRICING REFEREE™
      @else
        GASQ CERTIFIED™ &nbsp;•&nbsp; CONFIDENTIAL &nbsp;•&nbsp; PROPRIETARY &nbsp;•&nbsp; THE INDUSTRY PRICING REFEREE™
      @endif
    </td></tr>
  </table>
</div>
