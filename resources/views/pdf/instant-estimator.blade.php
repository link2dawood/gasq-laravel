@php
    $kpis        = (array) ($result['kpis'] ?? $result ?? []);
    $requestData = (array) ($result['request'] ?? []);

    $money  = static fn ($v) => \App\Support\Currency::format($v ?? 0, 2);
    $moneyK = static fn ($v) => \App\Support\Currency::format($v ?? 0, 0);
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
    $weeksCovered  = $kpis['weeksCoveredRounded']      ?? null;
    $monthsCovered = $kpis['monthsOfCoverageRounded']  ?? null;
    $coverageText  = ($weeksCovered && $monthsCovered)
        ? ($weeksCovered . ' weeks / ' . $monthsCovered . ' months')
        : 'Directional estimate';

    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-IE' . str_pad((string) ($reportId ?? rand(1000, 9999)), 4, '0', STR_PAD_LEFT);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Instant Estimator Report',
    'subtitle' => $serviceLabel . ' · ' . $coverageText,
    'reportNumber' => $reportNumber,
    'reportType' => 'Vendor — Instant Estimate',
    'contactName' => trim($requestData['name'] ?? ''),
    'contactCompany' => trim($requestData['company'] ?? ''),
    'contactAddress' => trim($requestData['location'] ?? ''),
    'contactEmail' => trim($requestData['email'] ?? ''),
    'contactPhone' => trim($requestData['phone'] ?? ''),
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%" class="stat-grid-label"><p>Outsourced Hourly</p></td>
    <td width="34%" class="stat-grid-label"><p>Outsourced Annual</p></td>
    <td width="33%" class="stat-grid-label last"><p>Capital Recovered (Annual)</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-blue">
      <p class="num">{{ $money($outsourcedHourly) }}</p>
      <p class="sub">per hour</p>
    </td>
    <td class="stat-grid-value bg-pink">
      <p class="num">{{ $moneyK($outsourcedAnnual) }}</p>
      <p class="sub">vendor delivery cost</p>
    </td>
    <td class="stat-grid-value bg-green last">
      <p class="num">{{ $moneyK($recoveredCapitalAnnual) }}</p>
      <p class="sub">savings vs in-house</p>
    </td>
  </tr>
  <tr>
    <td class="stat-grid-label"><p>Internal True Hourly</p></td>
    <td class="stat-grid-label"><p>Internal Annual</p></td>
    <td class="stat-grid-label last"><p>Workforce Required</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-purple">
      <p class="num">{{ $money($internalHourly) }}</p>
      <p class="sub">true buyer cost / hr</p>
    </td>
    <td class="stat-grid-value bg-peach">
      <p class="num">{{ $moneyK($internalAnnual) }}</p>
      <p class="sub">in-house annual cost</p>
    </td>
    <td class="stat-grid-value bg-sky last">
      <p class="num">{{ $num($workforce, 0) }}</p>
      <p class="sub">FTEs to deliver scope</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Outsourced Pricing</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Hourly</td><td class="v">{{ $money($outsourcedHourly) }}</td></tr>
  <tr class="alt"><td>Weekly</td><td class="v">{{ $money($outsourcedWeekly) }}</td></tr>
  <tr><td>Monthly</td><td class="v">{{ $money($outsourcedMonthly) }}</td></tr>
  <tr class="alt"><td>Annual</td><td class="v">{{ $money($outsourcedAnnual) }}</td></tr>
  <tr class="total"><td>Full Term</td><td class="v">{{ $money($outsourcedTerm) }}</td></tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Internal True Cost (In-House)</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>True Hourly</td><td class="v">{{ $money($internalHourly) }}</td></tr>
  <tr class="alt"><td>Weekly</td><td class="v">{{ $money($internalWeekly) }}</td></tr>
  <tr><td>Monthly</td><td class="v">{{ $money($internalMonthly) }}</td></tr>
  <tr class="alt"><td>Annual</td><td class="v">{{ $money($internalAnnual) }}</td></tr>
  <tr class="total"><td>Full Term</td><td class="v">{{ $money($internalTerm) }}</td></tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Capital Recovery Summary</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Recovered Capital (Annual)</td><td class="v">{{ $money($recoveredCapitalAnnual) }}</td></tr>
  <tr class="alt"><td>Recovered Capital (Term)</td><td class="v">{{ $money($recoveredCapitalTerm) }}</td></tr>
  @if($efficiencyGain > 0)<tr><td>Efficiency Gain</td><td class="v">{{ $money($efficiencyGain) }}</td></tr>@endif
  @if($appraisalFee > 0)<tr class="alt"><td>Appraisal Fee</td><td class="v">{{ $money($appraisalFee) }}</td></tr>@endif
  @if($paybackMonths > 0)<tr style="background:#e8f5eb;"><td style="font-weight:bold; color:#1e3558;">Payback / Breakeven</td><td class="v" style="color:#1e3558;">{{ $num($paybackMonths, 1) }} months</td></tr>@endif
</table>

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Coverage Scope</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Service Type</td><td class="v" style="font-weight:normal; color:#374151;">{{ $serviceLabel }}</td></tr>
  <tr class="alt"><td>Coverage Period</td><td class="v" style="font-weight:normal; color:#374151;">{{ $coverageText }}</td></tr>
  <tr><td>Weekly Coverage Hours</td><td class="v">{{ $num($weeklyCovHours, 0) }}</td></tr>
  <tr class="alt"><td>Term Coverage Hours</td><td class="v">{{ $num($termCovHours, 0) }}</td></tr>
  <tr><td>Total Workforce Required</td><td class="v">{{ $num($workforce, 0) }}</td></tr>
</table>

<p class="gasq-note">
    Instant Estimator results are directional estimates intended to size the engagement. Final pricing will be locked in upon vendor selection through the GASQ Workforce-to-Post™ qualification flow.
</p>

@endsection
