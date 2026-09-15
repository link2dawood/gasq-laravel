{{-- PAGE 1 — Executive cost dashboard: the contract total (sum of the four
     allocation groups), the group totals, the allocation mix and the reconciliation read. --}}
<div class="page page-break">
  @include('pdf.workforce._header', [
      'pageTitle' => 'GASQ Workforce-to-Post Report',
      'pageSubtitle' => 'Executive Dashboard · Allocation Group Totals · Line-Item Breakdown',
  ])

  <div class="body-pad">

    {{-- ── Contact strip ──────────────────────────────────── --}}
    <table class="contact" cellpadding="0" cellspacing="0">
      <tr>
        <td style="width:240px;">
          <p class="k">Vendor Contact</p>
          <p class="v">{{ $contactName ?: '—' }}</p>
          @if($contactCompany)<p class="s">{{ $contactCompany }}@if($contactAddress) · {{ $contactAddress }}@endif</p>@endif
          @if(! $contactCompany && $contactAddress)<p class="s">{{ $contactAddress }}</p>@endif
        </td>
        <td style="width:226px;">
          <p class="k">Contact</p>
          <p class="v">{{ $contactEmail ?: '—' }}</p>
          @if($contactPhone)<p class="s">{{ $contactPhone }}</p>@endif
        </td>
        <td class="last" style="width:196px; text-align:right;">
          <p class="k">Report Type</p>
          <p class="v">{{ $reportType }}</p>
          <p class="s">{{ $reportDate }} · Generated {{ $generatedTime }}</p>
        </td>
      </tr>
    </table>

    {{-- ── Section heading ────────────────────────────────── --}}
    <table class="grid" cellpadding="0" cellspacing="0" style="margin-top:18px;">
      <tr>
        <td style="width:608px; vertical-align:middle;"><p class="sec-title">Executive Cost Dashboard</p></td>
        <td style="width:130px; vertical-align:middle;">
          <table class="pill" cellpadding="0" cellspacing="0">
            <tr><td><p>GASQ CERTIFIED</p></td></tr>
          </table>
        </td>
      </tr>
    </table>

    {{-- ── Stated total + the four group totals ───────────── --}}
    <table class="grid" cellpadding="0" cellspacing="0" style="margin-top:12px;">
      <tr>
        <td style="width:280px; vertical-align:top;">
          <table class="hero" cellpadding="0" cellspacing="0">
            <tr><td class="hero-pad" style="height:162px; vertical-align:middle;">
              <p class="k">Total Contract / Budget Value</p>
              <p class="num">{{ $money($contractTotal) }}</p>
              <p class="s">Sum of all allocation groups</p>
            </td></tr>
          </table>
        </td>
        <td style="width:10px;"></td>
        <td style="width:448px; vertical-align:top;">
          <table cellpadding="0" cellspacing="0" style="width:448px;">
            @foreach(array_chunk($lineGroups, 2) as $row)
              <tr>
                @foreach($row as $c => $group)
                  @if($c > 0)<td style="width:10px;"></td>@endif
                  <td style="width:219px; vertical-align:top;">
                    <table class="card" cellpadding="0" cellspacing="0">
                      <tr><td class="card-pad" style="height:74px; vertical-align:top;">
                        <table cellpadding="0" cellspacing="0" style="width:191px;">
                          <tr>
                            <td style="width:145px; vertical-align:top;"><p class="k">{{ $group['label'] }}</p></td>
                            <td style="width:46px; vertical-align:top;"><p class="pct">{{ $pct($group['pct']) }}</p></td>
                          </tr>
                        </table>
                        <p class="num">{{ $money($group['amount']) }}</p>
                      </td></tr>
                    </table>
                  </td>
                @endforeach
              </tr>
              @if(! $loop->last)<tr><td colspan="3" style="height:10px;"></td></tr>@endif
            @endforeach
          </table>
        </td>
      </tr>
    </table>

    {{-- ── Allocation mix + dashboard indicators ──────────── --}}
    <table class="grid" cellpadding="0" cellspacing="0" style="margin-top:18px;">
      <tr>
        <td style="width:430px; vertical-align:top;">
          <table class="panel" cellpadding="0" cellspacing="0" style="width:428px;">
            <tr><td class="panel-pad" style="height:150px;">
              <p class="panel-k">Allocation Mix</p>
              <table cellpadding="0" cellspacing="0" style="width:396px; margin-top:12px;">
                @foreach($lineGroups as $group)
                  @php $fill = max(2, (int) round(200 * min(100, max(0, $group['pct'])) / 100)); @endphp
                  <tr>
                    <td style="width:118px; vertical-align:middle; padding:6px 0;"><p class="mix-label">{{ $group['short'] }}</p></td>
                    <td style="width:224px; vertical-align:middle; padding:6px 0;">
                      <table class="mix-track" cellpadding="0" cellspacing="0">
                        <tr><td><table cellpadding="0" cellspacing="0" style="width:{{ $fill }}px;"><tr><td class="mix-fill" style="background:{{ $group['color'] }};"></td></tr></table></td></tr>
                      </table>
                    </td>
                    <td style="width:54px; vertical-align:middle; padding:6px 0;"><p class="mix-pct">{{ $pct($group['pct']) }}</p></td>
                  </tr>
                @endforeach
              </table>
            </td></tr>
          </table>
        </td>
        <td style="width:10px;"></td>
        <td style="width:298px; vertical-align:top;">
          <table class="panel" cellpadding="0" cellspacing="0" style="width:296px;">
            <tr><td class="panel-pad" style="height:150px;">
              <p class="panel-k">Dashboard Indicators</p>
              <table cellpadding="0" cellspacing="0" style="width:264px; margin-top:6px;">
                @foreach($indicators as $i => [$value, $caption])
                  @if($i > 0)<tr><td colspan="2" class="ind-rule"></td></tr>@endif
                  <tr>
                    <td style="width:104px; vertical-align:middle; padding:9px 0;"><p class="ind-num">{{ $value }}</p></td>
                    <td style="width:152px; vertical-align:middle; padding:9px 0 9px 8px;"><p class="ind-cap">{{ $caption }}</p></td>
                  </tr>
                @endforeach
              </table>
            </td></tr>
          </table>
        </td>
      </tr>
    </table>

    {{-- ── Reconciliation of the displayed amounts ────────── --}}
    <table class="flag ok" cellpadding="0" cellspacing="0" style="margin-top:18px;">
      <tr><td>
        <p class="k">Reconciliation Check</p>
        <p class="t">{{ $reconciliationNote }}</p>
      </td></tr>
    </table>

    {{-- ── Plain-language read of the mix ─────────────────── --}}
    <table class="readout" cellpadding="0" cellspacing="0" style="margin-top:12px;">
      <tr>
        <td style="width:106px;"><p class="k">Executive Readout</p></td>
        <td style="width:576px;"><p class="t">{{ $executiveReadout }}</p></td>
      </tr>
    </table>

  </div>

  @include('pdf.workforce._footer', ['page' => 1])
</div>
