{{--
    Report header repeated at the top of every page: brand logo, the page's own
    title, the report number and the GASQ Certified™ seal.

    Vars: $pageTitle, $pageSubtitle, $reportNumber.
--}}
@php
    $logoPath = 'file://' . public_path('images/site-logo.png');
    $sealPath = 'file://' . public_path('images/gasq-certified-seal.png');
@endphp
<table cellpadding="0" cellspacing="0" style="width:794px; background:#ffffff;">
  <tr>
    <td style="width:150px; padding:16px 12px 12px 28px; vertical-align:middle;">
      <img src="{{ $logoPath }}" alt="GASQ" style="width:110px; height:auto;">
    </td>
    <td style="width:422px; padding:16px 12px 12px 0; vertical-align:middle;">
      <p class="rpt-title">{{ $pageTitle }}</p>
      @if($pageSubtitle)<p class="rpt-sub">{{ $pageSubtitle }}</p>@endif
    </td>
    <td style="width:130px; padding:12px 28px 12px 12px; vertical-align:middle; text-align:right;">
      <img src="{{ $sealPath }}" alt="GASQ Certified" style="width:60px; height:auto; margin-left:70px; margin-bottom:6px;">
      <p class="rpt-id">{{ $reportNumber }}</p>
    </td>
  </tr>
</table>
<div class="rule-navy"></div>
