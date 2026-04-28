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

    $outsourcedHourly  = (float)($kpis['outsourcedHourly']  ?? $result['hourly_rate']    ?? 0);
    $outsourcedWeekly  = (float)($kpis['outsourcedWeekly']  ?? $result['weekly_total']   ?? 0);
    $outsourcedMonthly = (float)($kpis['outsourcedMonthly'] ?? $result['monthly_total']  ?? 0);
    $outsourcedAnnual  = (float)($kpis['outsourcedAnnual']  ?? $result['annual_total']   ?? 0);
    $outsourcedTerm    = (float)($kpis['outsourcedTerm']    ?? $result['annual_total']   ?? 0);

    $internalHourly  = (float)($kpis['internalTrueHourly'] ?? 0);
    $internalWeekly  = (float)($kpis['internalWeekly']     ?? 0);
    $internalMonthly = (float)($kpis['internalMonthly']    ?? 0);
    $internalAnnual  = (float)($kpis['internalAnnual']     ?? 0);
    $internalTerm    = (float)($kpis['internalTerm']       ?? 0);

    $recoveredCapital  = (float)($kpis['recoveredCapitalTerm'] ?? 0);
    $appraisalFee      = (float)($kpis['appraisalFee']         ?? 0);
    $efficiencyGain    = (float)($kpis['efficiencyGain']        ?? 0);
    $paybackMonths     = (float)($kpis['breakevenMonths']       ?? 0);
    $weeklyCovHours    = (float)($kpis['weeklyCoverageHours']   ?? 0);
    $termCovHours      = (float)($kpis['termCoverageHours']     ?? 0);
    $workforce         = (float)($kpis['totalWorkforceRequired'] ?? 0);

    $serviceLabel  = $kpis['serviceLabel'] ?? 'Instant Estimate';
    $weeksCovered  = $kpis['weeksCoveredRounded']      ?? null;
    $monthsCovered = $kpis['monthsOfCoverageRounded']  ?? null;
    $coverageText  = ($weeksCovered && $monthsCovered)
        ? ($weeksCovered . ' weeks / ' . $monthsCovered . ' months')
        : 'Directional estimate';

    $reportNum = 'GASQ ' . now()->format('Y-m-d') . '-' . str_pad(($reportId ?? rand(1,9999)), 4, '0', STR_PAD_LEFT);
    $logoPath  = 'file://' . public_path('images/site-logo.png');

    $cName    = trim($requestData['name']    ?? '');
    $cCompany = trim($requestData['company'] ?? '');
    $cEmail   = trim($requestData['email']   ?? '');
    $cPhone   = trim($requestData['phone']   ?? '');
    $cLoc     = trim($requestData['location'] ?? '');
@endphp

{{-- HEADER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff; border-bottom:3px solid #1e3558;">
  <tr>
    <td width="110" style="padding:14px 12px 14px 20px; vertical-align:middle;">
      <img src="{{ $logoPath }}" alt="GASQ" style="width:80px; height:auto; display:block;">
    </td>
    <td style="padding:14px 12px; vertical-align:middle;">
      <p style="font-size:18px; font-weight:bold; color:#1e3558; letter-spacing:0.01em; margin-bottom:4px;">GASQ Instant Estimator Report</p>
      <p style="font-size:8.5px; color:#6b7280; text-transform:uppercase; letter-spacing:0.12em;">{{ $serviceLabel }} &nbsp;·&nbsp; {{ $coverageText }}</p>
    </td>
    <td style="padding:14px 20px 14px 12px; vertical-align:middle; text-align:right;">
      <p style="font-size:11px; font-weight:bold; color:#1e3558; margin-bottom:4px;">{{ $reportNum }}</p>
      <p style="font-size:9px; color:#6b7280;">{{ now()->format('F j, Y') }}</p>
    </td>
  </tr>
</table>

{{-- CONTACT --}}
@if($cName || $cCompany || $cEmail)
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb; border-bottom:1px solid #d8dff0;">
  <tr>
    <td width="40%" style="padding:11px 10px 11px 20px; vertical-align:top; border-right:1px solid #d8dff0;">
      <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Prepared For</p>
      @if($cName)    <p style="font-size:12px; font-weight:bold; color:#1e3558;">{{ $cName }}</p>@endif
      @if($cCompany) <p style="font-size:10px; color:#374151; margin-top:2px;">{{ $cCompany }}</p>@endif
      @if($cLoc)     <p style="font-size:9px; color:#6b7280; margin-top:2px;">{{ $cLoc }}</p>@endif
    </td>
    <td style="padding:11px 10px; vertical-align:top; border-right:1px solid #d8dff0;">
      @if($cEmail)<p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Email</p><p style="font-size:10px; color:#1e3558;">{{ $cEmail }}</p>@endif
      @if($cPhone)<p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-top:6px; margin-bottom:2px;">Phone</p><p style="font-size:10px; color:#1e3558;">{{ $cPhone }}</p>@endif
    </td>
    <td style="padding:11px 20px 11px 10px; vertical-align:middle; text-align:right;">
      <p style="font-size:7.5px; text-transform:uppercase; letter-spacing:0.1em; color:#6b7280; font-weight:bold; margin-bottom:2px;">Date</p>
      <p style="font-size:10px; color:#1e3558;">{{ now()->format('M j, Y') }}</p>
    </td>
  </tr>
</table>
@endif

{{-- KPI GRID ROW 1 --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-top:3px solid #1e3558; margin-top:16px;">
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
      <p style="font-size:8px; color:#6b7280; margin-top:4px;">per hour · outsourced</p>
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
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Term Cost</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center; border-right:2px solid #ffffff;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Recovered Capital</p>
    </td>
    <td width="33%" style="background:#1e3558; padding:9px 16px; text-align:center;">
      <p style="font-size:8.5px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.1em;">Weekly Coverage Hours</p>
    </td>
  </tr>
  <tr>
    <td style="background:#fde8f5; padding:16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:26px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $moneyK($outsourcedTerm) }}</p>
      <p style="font-size:8px; color:#7a3a6a; margin-top:4px;">outsourced term total</p>
    </td>
    <td style="background:#ede8fd; padding:16px; text-align:center; border-right:2px solid #ffffff; border-bottom:3px solid #1e3558;">
      <p style="font-size:26px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $recoveredCapital > 0 ? $moneyK($recoveredCapital) : '—' }}</p>
      <p style="font-size:8px; color:#3a2d7a; margin-top:4px;">capital recovered over term</p>
    </td>
    <td style="background:#e0eaf8; padding:16px; text-align:center; border-bottom:3px solid #1e3558;">
      <p style="font-size:26px; font-weight:bold; color:#1e3558; font-variant-numeric:tabular-nums;">{{ $num($weeklyCovHours, 0) }}</p>
      <p style="font-size:8px; color:#3a5a8a; margin-top:4px;">hours / week covered</p>
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
  {{-- Sub-headers --}}
  <tr style="background:#eef2fb;">
    <td style="padding:7px 16px; border-bottom:1px solid #d8dff0; font-size:9px; font-weight:bold; color:#1e3558; text-transform:uppercase; letter-spacing:0.08em; width:30%;">Period</td>
    <td style="padding:7px 16px; border-bottom:1px solid #d8dff0; font-size:9px; font-weight:bold; color:#1e3558; text-transform:uppercase; letter-spacing:0.08em; text-align:right; width:35%;">Outsourced</td>
    <td style="padding:7px 16px; border-bottom:1px solid #d8dff0; font-size:9px; font-weight:bold; color:#1e3558; text-transform:uppercase; letter-spacing:0.08em; text-align:right; width:35%;">Internal TCO</td>
  </tr>
  @php
    $comparison = [
      ['Hourly',   $money($outsourcedHourly),   $internalHourly  > 0 ? $money($internalHourly)  : '—'],
      ['Weekly',   $money($outsourcedWeekly),   $internalWeekly  > 0 ? $money($internalWeekly)  : '—'],
      ['Monthly',  $money($outsourcedMonthly),  $internalMonthly > 0 ? $money($internalMonthly) : '—'],
      ['Annual',   $moneyK($outsourcedAnnual),  $internalAnnual  > 0 ? $moneyK($internalAnnual) : '—'],
      ['Full Term',$moneyK($outsourcedTerm),    $internalTerm    > 0 ? $moneyK($internalTerm)   : '—'],
    ];
  @endphp
  @foreach($comparison as $i => [$label, $out, $int])
  <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151;">{{ $label }}</td>
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $out }}</td>
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#2d6a4f; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $int }}</td>
  </tr>
  @endforeach
</table>

{{-- RECOVERY METRICS --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Recovery &amp; Coverage Metrics</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
  @php
    $metrics = [
      ['Recovered Capital (Term)',  $recoveredCapital > 0 ? $moneyK($recoveredCapital) : '—'],
      ['Appraisal Fee',             $appraisalFee > 0     ? $money($appraisalFee)       : '—'],
      ['Efficiency Gain Ratio',     $efficiencyGain > 0   ? $num($efficiencyGain, 0) . ' : 1' : '—'],
      ['Payback Period',            $paybackMonths > 0    ? $num($paybackMonths, 1) . ' months' : '—'],
      ['Weekly Coverage Hours',     $num($weeklyCovHours, 0) . ' hrs'],
      ['Term Coverage Hours',       $num($termCovHours, 0)   . ' hrs'],
      ['Annualized Workforce',      $workforce > 0        ? $num($workforce, 0) : '—'],
    ];
  @endphp
  @foreach($metrics as $i => [$label, $val])
  <tr style="background:{{ $i % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151;">{{ $label }}</td>
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums;">{{ $val }}</td>
  </tr>
  @endforeach
</table>

{{-- REQUEST CONTEXT --}}
@php
  $reqRows = array_filter([
    'Company'          => $requestData['company']       ?? '',
    'Location'         => $requestData['location']      ?? '',
    'Website'          => $requestData['website']       ?? '',
    'Budget'           => $requestData['budgetAmount']  ?? '',
    'Decision Maker'   => $requestData['decisionMaker'] ?? '',
    'Budget Approved'  => $requestData['approvedBudget'] ?? '',
    'Notes'            => $requestData['notes']         ?? '',
  ], static fn ($v) => $v !== '');
@endphp
@if(!empty($reqRows))
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#1e3558; padding:9px 16px;">
    <p style="font-size:11px; font-weight:bold; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;">Request Context</p>
  </td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d8dff0; border-top:none;">
  @php $ri = 0; @endphp
  @foreach($reqRows as $rLabel => $rVal)
  <tr style="background:{{ $ri % 2 === 0 ? '#ffffff' : '#f6f8fb' }};">
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#374151; width:35%;">{{ $rLabel }}</td>
    <td style="padding:8px 16px; border-bottom:1px solid #e8edf5; font-size:10.5px; color:#1e3558; font-weight:bold;">{{ $rVal }}</td>
  </tr>
  @php $ri++; @endphp
  @endforeach
</table>
@endif

{{-- NARRATIVE --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
  <tr><td style="background:#f4f6fb; border-left:4px solid #1e3558; padding:12px 16px;">
    <p style="font-size:10px; color:#374151; line-height:1.6;">
      This estimate covers <strong style="color:#1e3558;">{{ $serviceLabel }}</strong>
      at an outsourced rate of <strong style="color:#1e3558;">{{ $money($outsourcedHourly) }}/hr</strong>
      ({{ $moneyK($outsourcedAnnual) }}/yr) over <strong style="color:#1e3558;">{{ $coverageText }}</strong>.
      @if($internalHourly > 0)
        Internal true cost is <strong style="color:#1e3558;">{{ $money($internalHourly) }}/hr</strong> for comparison.
      @endif
      @if($recoveredCapital > 0)
        Recovered capital over the term is projected at <strong style="color:#1e3558;">{{ $moneyK($recoveredCapital) }}</strong>.
      @endif
      Rates are directional; final values depend on scope, site conditions, and contract structure.
    </p>
  </td></tr>
</table>

{{-- FOOTER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px; border-top:2px solid #1e3558;">
  <tr style="background:#f4f6fb;">
    <td style="padding:10px 20px;">
      <p style="font-size:8.5px; color:#1e3558; font-weight:bold;">GASQ Security</p>
      <p style="font-size:8px; color:#6b7280; margin-top:2px;">{{ $reportNum }} &nbsp;·&nbsp; Instant Estimator &nbsp;·&nbsp; Directional pricing only</p>
    </td>
    <td style="padding:10px 20px; text-align:right;">
      <p style="font-size:8px; color:#6b7280;">{{ now()->format('M j, Y') }}</p>
    </td>
  </tr>
</table>

</body>
</html>
