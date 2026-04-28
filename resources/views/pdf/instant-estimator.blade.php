<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GASQ Instant Estimator Report</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 0; background: #fff; }
    table { border-collapse: collapse; }
    td, th { padding: 0; margin: 0; }
    p { margin: 0; padding: 0; }
</style>
</head>
<body>
@php
    $kpis        = (array) ($result['kpis'] ?? $result ?? []);
    $requestData = (array) ($result['request'] ?? []);

    $money  = static fn ($v) => '$' . number_format((float)($v ?? 0), 2);
    $moneyK = static fn ($v) => '$' . number_format((float)($v ?? 0), 0);
    $num    = static fn ($v, $d = 2) => number_format((float)($v ?? 0), $d);

    $outsourcedHourly  = (float)($kpis['outsourcedHourly']  ?? $result['hourly_rate']   ?? 0);
    $outsourcedWeekly  = (float)($kpis['outsourcedWeekly']  ?? $result['weekly_total']  ?? 0);
    $outsourcedMonthly = (float)($kpis['outsourcedMonthly'] ?? $result['monthly_total'] ?? 0);
    $outsourcedAnnual  = (float)($kpis['outsourcedAnnual']  ?? $result['annual_total']  ?? 0);
    $outsourcedTerm    = (float)($kpis['outsourcedTerm']    ?? $result['annual_total']  ?? 0);

    $internalHourly  = (float)($kpis['internalTrueHourly'] ?? 0);
    $internalWeekly  = (float)($kpis['internalWeekly']     ?? 0);
    $internalMonthly = (float)($kpis['internalMonthly']    ?? 0);
    $internalAnnual  = (float)($kpis['internalAnnual']     ?? 0);
    $internalTerm    = (float)($kpis['internalTerm']       ?? 0);

    $recoveredCapitalTerm  = (float)($kpis['recoveredCapitalTerm']  ?? 0);
    $recoveredCapitalAnnual= (float)($kpis['recoveredCapitalAnnual'] ?? 0);
    $appraisalFee          = (float)($kpis['appraisalFee']           ?? 0);
    $efficiencyGain        = (float)($kpis['efficiencyGain']         ?? 0);
    $paybackMonths         = (float)($kpis['breakevenMonths']        ?? 0);
    $weeklyCovHours        = (float)($kpis['weeklyCoverageHours']    ?? 0);
    $termCovHours          = (float)($kpis['termCoverageHours']      ?? 0);
    $workforce             = (float)($kpis['totalWorkforceRequired'] ?? 0);

    $serviceLabel  = $kpis['serviceLabel']             ?? 'Security Service';
    $coverageModel = $kpis['coverageModel']            ?? '';
    $weeksCovered  = $kpis['weeksCoveredRounded']      ?? null;
    $monthsCovered = $kpis['monthsOfCoverageRounded']  ?? null;
    $coverageText  = ($weeksCovered && $monthsCovered)
        ? ($weeksCovered . ' weeks / ' . $monthsCovered . ' months')
        : 'Directional estimate';

    $baselinePay = (float)($kpis['directLabor'] ?? 0);

    $reportNum = 'GASQ ' . now()->format('Y-m-d') . '-' . str_pad(($reportId ?? rand(1,9999)), 4, '0', STR_PAD_LEFT);
    $logoPath  = 'file://' . public_path('images/site-logo.png');

    $cName    = trim($requestData['name']     ?? '');
    $cCompany = trim($requestData['company']  ?? '');
    $cLoc     = trim($requestData['location'] ?? '');
    $cEmail   = trim($requestData['email']    ?? '');
    $cPhone   = trim($requestData['phone']    ?? '');
    $cBudget  = trim($requestData['budgetAmount'] ?? '');
    $cAttach  = !empty($requestData['attachments']) ? implode(', ', (array)$requestData['attachments']) : '';
@endphp

{{-- HEADER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff; border-bottom:3px solid #1e3558;">
  <tr>
    <td width="110" style="padding:14px 12px 14px 20px; vertical-align:middle;">
      <img src="{{ $logoPath }}" alt="GASQ" style="width:80px; height:auto; display:block;">
    </td>
    <td style="padding:14px 12px; vertical-align:middle;">
      <p style="font-size:18px; font-weight:bold; color:#1e3558; letter-spacing:0.01em; margin-bottom:4px;">GASQ Instant Estimator Report</p>
      <p style="font-size:8.5px; color:#6b7280; text-transform:uppercase; letter-spacing:0.12em;">Estimate Summary &nbsp;·&nbsp; {{ $coverageText }}</p>
    </td>
    <td style="padding:14px 20px 14px 12px; vertical-align:middle; text-align:right;">
      <p style="font-size:11px; font-weight:bold; color:#1e3558; margin-bottom:4px;">{{ $reportNum }}</p>
      <p style="font-size:9px; color:#6b7280;">{{ now()->format('F j, Y') }}</p>
    </td>
  </tr>
</table>

{{-- REQUESTER + ESTIMATE SUMMARY (mirrors the report tab layout) --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:0;">
  <tr>
    {{-- Left: Requester --}}
    <td width="50%" style="vertical-align:top; padding:0; border-right:1px solid #d8dff0;">
      <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:1px solid #d8dff0;">
        <tr><td style="background:#1e3558; padding:8px 16px;">
          <p style="font-size:10px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Requester</p>
        </td></tr>
      </table>
      <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:1px solid #d8dff0;">
        @php $rItems = array_filter(['Name' => $cName, 'Company' => $cCompany, 'Location' => $cLoc, 'Email' => $cEmail, 'Phone' => $cPhone], fn($v) => $v !== ''); $ri = 0; @endphp
        @if(empty($rItems))
        <tr><td style="padding:10px 16px; font-size:10px; color:#6b7280; font-style:italic;">No requester information entered.</td></tr>
        @else
          @foreach($rItems as $label => $val)
          <tr style="background:{{ $ri % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
            <td style="padding:7px 16px; border-bottom:1px solid #eef2f8; font-size:10px; color:#6b7280; width:40%;">{{ $label }}</td>
            <td style="padding:7px 16px; border-bottom:1px solid #eef2f8; font-size:10.5px; color:#1e3558; font-weight:bold;">{{ $val }}</td>
          </tr>
          @php $ri++; @endphp
          @endforeach
        @endif
      </table>
    </td>
    {{-- Right: Estimate Summary --}}
    <td width="50%" style="vertical-align:top; padding:0;">
      <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:1px solid #d8dff0;">
        <tr><td style="background:#1e3558; padding:8px 16px;">
          <p style="font-size:10px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Estimate Summary</p>
        </td></tr>
      </table>
      <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:1px solid #d8dff0;">
        @php
          $sumItems = array_filter([
            'Service'       => $serviceLabel,
            'Baseline Pay'  => $baselinePay > 0 ? $money($baselinePay) . '/hr' : '',
            'Coverage'      => $coverageText,
            'Budget'        => $cBudget,
            'Attachments'   => $cAttach,
          ], fn($v) => $v !== '');
          $si = 0;
        @endphp
        @foreach($sumItems as $label => $val)
        <tr style="background:{{ $si % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
          <td style="padding:7px 16px; border-bottom:1px solid #eef2f8; font-size:10px; color:#6b7280; width:40%;">{{ $label }}</td>
          <td style="padding:7px 16px; border-bottom:1px solid #eef2f8; font-size:10.5px; color:#1e3558; font-weight:bold;">{{ $val }}</td>
        </tr>
        @php $si++; @endphp
        @endforeach
      </table>
    </td>
  </tr>
</table>

{{-- KPI GRID ROW 1 --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:3px solid #1e3558; margin-top:18px;">
  <tr>
    <td width="34%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Outsourced Hourly Rate</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Outsourced Annual Cost</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Internal True Hourly</p>
    </td>
  </tr>
  <tr>
    <td style="background:#fdf8ee; padding:20px 16px; text-align:center; border-right:2px solid #ffffff; border-bottom:2px solid #e8e0cc;">
      <p style="font-size:44px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $money($outsourcedHourly) }}</p>
      <p style="font-size:8px; color:#6b7280; margin-top:4px;">estimated hourly rate</p>
    </td>
    <td style="background:#fde8e0; padding:20px 16px; text-align:center; border-right:2px solid #ffffff; border-bottom:2px solid #e8e0cc;">
      <p style="font-size:32px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($outsourcedAnnual) }}</p>
      <p style="font-size:8px; color:#7a4a3a; margin-top:4px;">annual outsourced cost</p>
    </td>
    <td style="background:#e4f5e9; padding:20px 16px; text-align:center; border-bottom:2px solid #e8e0cc;">
      <p style="font-size:32px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $internalHourly > 0 ? $money($internalHourly) : '—' }}</p>
      <p style="font-size:8px; color:#2d6a4f; margin-top:4px;">internal true cost/hr</p>
    </td>
  </tr>
</table>

{{-- KPI GRID ROW 2 --}}
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="34%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Outsourced Term Cost</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Capital Recovered</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Weekly Coverage Hours</p>
    </td>
  </tr>
  <tr>
    <td style="background:#fde8f5; padding:16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:26px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($outsourcedTerm) }}</p>
      <p style="font-size:8px; color:#7a3a6a; margin-top:4px;">full term total</p>
    </td>
    <td style="background:#ede8fd; padding:16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:26px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $recoveredCapitalTerm > 0 ? $moneyK($recoveredCapitalTerm) : '—' }}</p>
      <p style="font-size:8px; color:#3a2d7a; margin-top:4px;">term savings vs in-house</p>
    </td>
    <td style="background:#e0eaf8; padding:16px; text-align:center; border-bottom:3px solid #1e3558;">
      <p style="font-size:26px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $num($weeklyCovHours, 0) }}</p>
      <p style="font-size:8px; color:#3a5a8a; margin-top:4px;">hours / week</p>
    </td>
  </tr>
</table>

{{-- COST COMPARISON --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Cost Comparison: Outsourced vs Internal TCO</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
  <tr style="background:#eef2fb;">
    <td style="padding:7px 16px; border-bottom:1px solid #d8dff0; font-size:9px; font-weight:bold; color:#1e3558; text-transform:uppercase; letter-spacing:0.08em; width:25%;">Period</td>
    <td style="padding:7px 16px; border-bottom:1px solid #d8dff0; font-size:9px; font-weight:bold; color:#c0392b; text-transform:uppercase; letter-spacing:0.08em; text-align:right; width:37.5%;">Outsourced</td>
    <td style="padding:7px 16px; border-bottom:1px solid #d8dff0; font-size:9px; font-weight:bold; color:#2d6a4f; text-transform:uppercase; letter-spacing:0.08em; text-align:right; width:37.5%;">Internal TCO</td>
  </tr>
  @php
    $comparison = [
      ['Hourly',    $money($outsourcedHourly),   $internalHourly  > 0 ? $money($internalHourly)  : '—'],
      ['Weekly',    $money($outsourcedWeekly),   $internalWeekly  > 0 ? $money($internalWeekly)  : '—'],
      ['Monthly',   $money($outsourcedMonthly),  $internalMonthly > 0 ? $money($internalMonthly) : '—'],
      ['Annual',    $moneyK($outsourcedAnnual),  $internalAnnual  > 0 ? $moneyK($internalAnnual) : '—'],
      ['Full Term', $moneyK($outsourcedTerm),    $internalTerm    > 0 ? $moneyK($internalTerm)   : '—'],
    ];
  @endphp
  @foreach($comparison as $i => [$label, $out, $int])
  <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151;">{{ $label }}</td>
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#c0392b; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $out }}</td>
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#2d6a4f; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $int }}</td>
  </tr>
  @endforeach
</table>

{{-- ROI & RECOVERY --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">ROI, Payback &amp; Recovery</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
  @php
    $roi = [
      ['Capital Recovered (Term)',   $recoveredCapitalTerm   > 0 ? $moneyK($recoveredCapitalTerm)            : '—'],
      ['Capital Recovered (Annual)', $recoveredCapitalAnnual > 0 ? $moneyK($recoveredCapitalAnnual)          : '—'],
      ['Price Permit Fee',           $appraisalFee           > 0 ? $money($appraisalFee) . '/yr'             : '—'],
      ['Efficiency Gain',            $efficiencyGain         > 0 ? $num($efficiencyGain, 0) . ' : 1'         : '—'],
      ['Payback Period',             $paybackMonths          > 0 ? $num($paybackMonths, 1) . ' months'        : '—'],
      ['Annualized Workforce',       $workforce              > 0 ? $num($workforce, 0) . ' officers'          : '—'],
      ['Term Coverage Hours',        $num($termCovHours, 0) . ' hrs'],
    ];
  @endphp
  @foreach($roi as $i => [$label, $val])
  <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151;">{{ $label }}</td>
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $val }}</td>
  </tr>
  @endforeach
</table>

{{-- DISCLAIMER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#f4f6fb; border-left:4px solid #1e3558; padding:12px 16px;">
    <p style="font-size:10px; color:#374151; line-height:1.6;">
      Directional output only. Actual proposal pricing will vary by location conditions, contract structure, overtime exposure, and vendor requirements.
    </p>
  </td></tr>
</table>

{{-- FOOTER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px; border-top:2px solid #1e3558;">
  <tr style="background:#f4f6fb;">
    <td style="padding:10px 20px;">
      <p style="font-size:8.5px; color:#1e3558; font-weight:bold;">GASQ Security</p>
      <p style="font-size:8px; color:#6b7280; margin-top:2px;">{{ $reportNum }} &nbsp;·&nbsp; Instant Estimator &nbsp;·&nbsp; {{ $generatedAt ?? now()->format('M j, Y g:i A') }}</p>
    </td>
    <td style="padding:10px 20px; text-align:right;">
      <p style="font-size:8px; color:#6b7280;">{{ now()->format('M j, Y') }}</p>
    </td>
  </tr>
</table>

</body>
</html>
