@php
    $scenarioMeta = (array) ($scenario['meta'] ?? []);
    $contact      = (array) ($scenario['contact'] ?? []);
    $kpis         = (array) ($result['kpis'] ?? $result ?? []);

    $money  = static fn ($v) => '$' . number_format((float)($v ?? 0), 2);
    $moneyK = static fn ($v) => '$' . number_format((float)($v ?? 0), 0);
    $num    = static fn ($v, $d = 2) => number_format((float)($v ?? 0), $d);

    $rosInput = (float)($scenarioMeta['returnOnSalesPct'] ?? 0);
    $rosRate  = $rosInput > 1 ? $rosInput / 100 : $rosInput;
    $rosPct   = (float)($kpis['returnOnSalesPercentDisplay'] ?? ($rosRate * 100));

    $hourlyRate  = (float)($kpis['costPerHour']                     ?? 0);
    $totalCost   = (float)($kpis['totalAnnualCost']                 ?? 0);
    $totalRos    = (float)($kpis['totalAnnualCostWithReturnOnSales'] ?? 0);
    $annualHours = (float)($scenarioMeta['annualHours']              ?? 0);

    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-MPB' . str_pad((string) ($reportId ?? rand(1000, 9999)), 4, '0', STR_PAD_LEFT);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Mobile Patrol Quote',
    'subtitle' => 'Buyer-Facing Pricing Summary',
    'reportNumber' => $reportNumber,
    'reportType' => 'Buyer-Facing Quote',
    'contactName' => trim($contact['contactName'] ?? ''),
    'contactCompany' => trim($contact['companyName'] ?? ''),
    'contactAddress' => trim($contact['contactAddress'] ?? ''),
    'contactEmail' => trim($contact['contactEmail'] ?? ''),
    'contactPhone' => trim($contact['contactPhone'] ?? ''),
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%" class="stat-grid-label"><p>Hourly Bill Rate</p></td>
    <td width="34%" class="stat-grid-label"><p>Total Annual Cost</p></td>
    <td width="33%" class="stat-grid-label last"><p>With Return on Sales</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-blue">
      <p class="num">{{ $money($hourlyRate) }}</p>
      <p class="sub">per hour</p>
    </td>
    <td class="stat-grid-value bg-pink">
      <p class="num">{{ $moneyK($totalCost) }}</p>
      <p class="sub">annual operating total</p>
    </td>
    <td class="stat-grid-value bg-green last">
      <p class="num">{{ $moneyK($totalRos) }}</p>
      <p class="sub">basis for bill rate · {{ $num($rosPct,1) }}%</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Quote Summary</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr><td>Annual Hours</td><td class="v">{{ $num($annualHours, 0) }}</td></tr>
  <tr class="alt"><td>Hourly Bill Rate</td><td class="v">{{ $money($hourlyRate) }}</td></tr>
  <tr><td>Total Annual Cost</td><td class="v">{{ $money($totalCost) }}</td></tr>
  <tr class="alt"><td>Return on Sales</td><td class="v">{{ $num($rosPct, 2) }}%</td></tr>
  <tr class="total"><td>Total Annual Cost + Return on Sales</td><td class="v">{{ $money($totalRos) }}</td></tr>
</table>

<p class="gasq-note">
    All price calculations include the full cost of workforce staffing and support services, including livable base wages,
    employer-paid payroll taxes, workers compensation, general liability insurance, paid time off, healthcare and fringe benefits,
    uniforms and equipment, onboarding and training, vehicle and patrol operating cost, 24/7 dispatch capability, and compliance with all
    applicable labor laws and service-level guarantees.
</p>

@endsection
