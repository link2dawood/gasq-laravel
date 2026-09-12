{{-- Brand bar repeated at the top of every page of the master estimate. --}}
@php
    $logoPath = 'file://' . public_path('images/site-logo.png');
    // Optional photographic band behind the right-hand tagline. Drop a JPG/PNG at
    // public/images/report-header-band.jpg and it is picked up automatically;
    // without it the bar renders as a flat brand tint.
    $bandFile = collect(['report-header-band.jpg', 'report-header-band.png'])
        ->map(fn ($f) => public_path('images/' . $f))
        ->first(fn ($p) => is_file($p));
@endphp
<table class="brandbar" width="794" cellpadding="0" cellspacing="0">
  <tr>
    <td width="140" style="padding:6px 6px 6px 20px; vertical-align:middle; background:#fff; height:66px;">
      <img src="{{ $logoPath }}" alt="GASQ" style="width:114px; height:auto;">
    </td>
    <td width="180" style="padding:6px 10px; vertical-align:middle; background:#fff; border-left:1px solid #c9d6ea; height:66px;">
      <p class="tag">INDEPENDENT.<br>DATA-DRIVEN.<br>HIGHER STANDARDS.<br>STRONGER OUTCOMES.</p>
    </td>
    <td style="vertical-align:middle; height:66px; background:#dbe5f3; padding:6px 20px 6px 10px; text-align:right;">
      @if($bandFile)
        <img src="file://{{ $bandFile }}" style="width:100%; height:66px;">
        <div style="margin-top:-60px;">
      @endif
      <p class="tag-right">REAL DATA.<br>REAL INSIGHTS.<br>A MORE SECURE TOMORROW.</p>
      <table cellpadding="0" cellspacing="0" width="146" align="right" style="margin-top:4px;">
        <tr><td style="height:3px; background:#ef6c1f;"></td></tr>
      </table>
      @if($bandFile)</div>@endif
    </td>
  </tr>
</table>

@if(! empty($masked))
  {{-- Buyer edition: unmistakable on every page, so the free version is never
       mistaken for the complete estimate. --}}
  <table width="794" cellpadding="0" cellspacing="0" style="background:#12294f;">
    <tr>
      <td style="padding:5px 20px;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>
          <td width="22" style="vertical-align:middle;"><img src="{{ \App\Support\ReportSvg::icon('shield', '#ef6c1f') }}" style="width:13px;height:13px;"></td>
          <td style="vertical-align:middle;">
            <p style="font-size:8px; font-weight:bold; color:#ffffff; letter-spacing:.09em;">BUYER EDITION — YOUR IN-HOUSE COST TO PROTECT</p>
          </td>
          <td style="vertical-align:middle; text-align:right;">
            <p style="font-size:7.5px; color:#a9bad4;">Vendor cost, capital recovered and payback withheld</p>
          </td>
        </tr></table>
      </td>
    </tr>
  </table>
@endif
