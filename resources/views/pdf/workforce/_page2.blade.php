{{-- PAGE 2 — Line-item cost composition. The row list is chunked so a long
     breakdown continues onto further pages instead of being clipped; the cost
     elements and pricing-scope blocks close the final chunk. --}}
@foreach($rowChunks as $chunkIndex => $chunk)
@php $isLastChunk = $chunkIndex === count($rowChunks) - 1; @endphp
<div class="page page-break">
  @include('pdf.workforce._header', [
      'pageTitle' => 'Line-Item Cost Composition' . (count($rowChunks) > 1 ? ' (' . ($chunkIndex + 1) . ' of ' . count($rowChunks) . ')' : ''),
      'pageSubtitle' => 'Detailed Allocation Dashboard · Source Values Preserved',
  ])

  <div class="body-pad">

    <table class="band" cellpadding="0" cellspacing="0">
      <tr><td><p>Line-Item Breakdown</p></td></tr>
    </table>
    <table class="ltable" cellpadding="0" cellspacing="0">
      <tr class="head">
        <td style="width:160px;">Allocation Group</td>
        <td style="width:320px;">Line Item</td>
        <td class="v" style="width:100px;">Amount</td>
        <td class="v" style="width:44px;">%</td>
      </tr>
      @foreach($chunk as $row)
        @if($row['type'] === 'group')
          <tr class="group">
            <td style="width:160px;">{{ $row['label'] }}</td>
            <td class="desc" style="width:320px;">{{ $row['description'] }}</td>
            <td class="v" style="width:100px;">{{ $money($row['amount']) }}</td>
            <td class="v" style="width:44px;">{{ $pct($row['pct']) }}</td>
          </tr>
        @else
          <tr class="{{ $row['alt'] ? 'alt' : '' }}">
            <td style="width:160px;"></td>
            <td style="width:320px;">{{ $row['label'] }}</td>
            <td class="v" style="width:100px;">{{ $money($row['amount']) }}</td>
            <td class="v" style="width:44px;">{{ $pct($row['pct']) }}</td>
          </tr>
        @endif
      @endforeach
    </table>

    @if($isLastChunk)
      {{-- ── Stated-included elements + what the itemisation covers ── --}}
      <table class="grid" cellpadding="0" cellspacing="0" style="margin-top:14px;">
        <tr>
          <td style="width:430px; vertical-align:top;">
            <table class="panel" cellpadding="0" cellspacing="0" style="width:428px;">
              <tr><td class="panel-pad" style="height:210px;">
                <p class="panel-k">Cost Elements Stated as Included</p>
                <table cellpadding="0" cellspacing="0" style="width:396px; margin-top:9px;">
                  @foreach(array_chunk($costElements, 2) as $pair)
                    <tr>
                      @foreach($pair as $c => $element)
                        @if($c > 0)<td style="width:8px;"></td>@endif
                        <td style="width:194px; padding-bottom:4px;">
                          <table class="chip" cellpadding="0" cellspacing="0"><tr><td><p>{{ $element }}</p></td></tr></table>
                        </td>
                      @endforeach
                      @if(count($pair) === 1)<td style="width:8px;"></td><td style="width:194px;"></td>@endif
                    </tr>
                  @endforeach
                </table>
              </td></tr>
            </table>
          </td>
          <td style="width:10px;"></td>
          <td style="width:298px; vertical-align:top;">
            <table class="panel tint" cellpadding="0" cellspacing="0" style="width:296px;">
              <tr><td class="panel-pad" style="height:210px;">
                <p class="lead">Pricing Scope</p>
                <p class="prose" style="margin-top:9px;">The certification statement covers the full cost of workforce staffing and support services. Several of those elements are not carried as separate lines in this breakdown, so this page separates itemized costs from the broader stated-included costs.</p>
              </td></tr>
            </table>
          </td>
        </tr>
      </table>

      <table class="flag {{ $percentsReconcile ? 'ok' : '' }}" cellpadding="0" cellspacing="0" style="margin-top:12px;">
        <tr><td>
          <p class="k">Source Presentation Note</p>
          <p class="t">{{ $sourceNote }}</p>
        </td></tr>
      </table>
    @endif

  </div>

  @include('pdf.workforce._footer', ['page' => 2 + $chunkIndex])
</div>
@endforeach
