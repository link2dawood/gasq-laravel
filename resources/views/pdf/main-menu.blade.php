@php
    $res = $result ?? [];
    $budgetRoi = data_get($scenario ?? [], 'meta.roiCostBasis') === 'budget'
        ? data_get($res, 'tabs.economicJustification') : null;
    if ($budgetRoi) unset($res['tabs']['economicJustification']);
    $format = static function ($value, string $type = 'number'): string {
        if (!is_numeric($value)) return '—';
        return match ($type) {
            'money' => \App\Support\Currency::format((float) $value),
            'percent' => number_format((float) $value, 2).'%',
            default => rtrim(rtrim(number_format((float) $value, 2), '0'), '.'),
        };
    };
    $sections = [
        'Workforce Overview' => ['kpis', [
            'annualCoverageHours' => ['Annual Coverage Hours', 'number'],
            'annualLaborHours' => ['Annual Labor Hours', 'number'],
            'ftesRequiredAtPaidHoursBasis' => ['Required FTEs (Paid Hours Basis)', 'number'],
        ]],
        'Security Costs' => ['tabs.securityCost', [
            'hourlyRate' => ['Hourly Rate', 'money'],
            'weeklyTotal' => ['Weekly Cost', 'money'],
            'monthlyTotal' => ['Monthly Cost', 'money'],
            'annualTotal' => ['Annual Cost', 'money'],
            'livingWageBase' => ['Living Wage Base / Hour', 'money'],
            'withOverheadHourly' => ['Hourly Cost Including Overhead', 'money'],
        ]],
        'Manpower' => ['tabs.manpowerHours', [
            'weeklyHours' => ['Weekly Hours', 'number'],
            'monthlyHours' => ['Monthly Hours', 'number'],
            'annualHours' => ['Annual Hours', 'number'],
            'estimatedGuardsPartTime28hr' => ['Estimated Guards (28 Hours / Week)', 'number'],
            'shiftMultiplierUsed' => ['Shift Multiplier', 'number'],
        ]],
        'Bill Rate' => ['tabs.billRate', [
            'basePayRate' => ['Base Pay Rate / Hour', 'money'],
            'costWithBenefits' => ['Hourly Cost Including Benefits', 'money'],
            'finalBillRate' => ['Final Bill Rate / Hour', 'money'],
            'markupPct' => ['Markup', 'percent'],
            'weeklyAt40' => ['Weekly Billings (40 Hours)', 'money'],
        ]],
    ];
    $reportNumber = 'GASQ ' . now()->format('Y-m-d') . '-MM' . str_pad((string) (rand(1000, 9999)), 4, '0', STR_PAD_LEFT);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Main Menu Calculator Report',
    'subtitle' => 'Workforce + Cost Snapshot',
    'reportNumber' => $reportNumber,
    'reportType' => 'Vendor — Main Menu Report',
    'contactName' => $user?->name ?? null,
    'contactCompany' => $user?->company ?? null,
    'contactEmail' => $user?->email ?? null,
    'contactPhone' => $user?->phone ?? null,
])

@section('content')

@if($budgetRoi)
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Budget Summary &amp; ROI Analysis</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr class="hl"><td>Metric</td><td class="v">In-House</td><td class="v">Vendor</td></tr>
  @foreach([
    ['Workforce Baseline Assumption Labor Rate', 'baselineHourly', 'baselineHourly', true],
    ['Hourly Bill / Cost to Protect Rate', 'inHouseHourly', 'vendorHourly', true],
    ['Total Weekly Hours', 'totalWeeklyHours', 'totalWeeklyHours', false],
    ['Total Monthly Hours', 'totalMonthlyHours', 'totalMonthlyHours', false],
    ['Total Annual Hours', 'totalAnnualHours', 'totalAnnualHours', false],
    ['Total Staff Required', 'totalStaffRequired', 'totalStaffRequired', false],
    ['Total Weekly Cost', 'inHouseWeekly', 'vendorWeekly', true],
    ['Total Monthly Cost', 'inHouseMonthly', 'vendorMonthly', true],
    ['Total Annual Cost', 'inHouseAnnual', 'vendorAnnual', true],
  ] as [$label, $internalKey, $vendorKey, $money])
    <tr class="{{ $loop->last ? 'total' : ($loop->even ? 'alt' : '') }}">
      <td>{{ $label }}</td>
      @foreach([$internalKey, $vendorKey] as $key)
        <td class="v">{{ $money ? \App\Support\Currency::format($budgetRoi[$key]) : rtrim(rtrim(number_format($budgetRoi[$key], 2), '0'), '.') }}</td>
      @endforeach
    </tr>
  @endforeach
  <tr class="alt"><td>Capital Recovery Opportunity — Annual</td><td class="v">—</td><td class="v">{{ \App\Support\Currency::format($budgetRoi['costSavings']) }}</td></tr>
  <tr><td>Operational Capital Recovered (%)</td><td class="v">—</td><td class="v">{{ number_format($budgetRoi['roiPct']) }}%</td></tr>
  <tr class="alt"><td>Payback &amp; Recovery Period</td><td class="v">—</td><td class="v">{{ number_format($budgetRoi['paybackMonths'], 1) }} months</td></tr>
</table>
@endif

@foreach($sections as $heading => [$path, $fields])
  @php
    $values = data_get($res, $path, []);
    $available = array_filter($fields, fn ($key) => is_numeric($values[$key] ?? null), ARRAY_FILTER_USE_KEY);
  @endphp
  @if($available)
  <table class="gasq-kv gasq-mt main-menu-section" width="100%" cellpadding="0" cellspacing="0">
    <thead><tr><td colspan="2" class="gasq-section-band"><p>{{ $heading }}</p></td></tr></thead>
    <tbody>
    @foreach($available as $key => [$label, $type])
      <tr class="{{ $loop->even ? 'alt' : '' }}"><td>{{ $label }}</td><td class="v">{{ $format($values[$key], $type) }}</td></tr>
    @endforeach
    </tbody>
  </table>
  @endif
@endforeach

@if(!$budgetRoi && data_get($res, 'tabs.economicJustification'))
<table class="gasq-kv gasq-mt main-menu-section" width="100%" cellpadding="0" cellspacing="0">
  <thead><tr><td colspan="3" class="gasq-section-band"><p>Economic Justification</p></td></tr>
  <tr class="hl"><td>Metric</td><td class="v">In-House</td><td class="v">Vendor</td></tr></thead>
  <tbody>
  @foreach(['Hourly' => 'Hourly Cost', 'Weekly' => 'Weekly Cost', 'Monthly' => 'Monthly Cost', 'Annual' => 'Annual Cost'] as $suffix => $label)
    <tr class="{{ $loop->even ? 'alt' : '' }}"><td>{{ $label }}</td><td class="v">{{ $format(data_get($res, 'tabs.economicJustification.inHouse'.$suffix), 'money') }}</td><td class="v">{{ $format(data_get($res, 'tabs.economicJustification.vendor'.$suffix), 'money') }}</td></tr>
  @endforeach
  @foreach(['costSavings' => ['Annual Savings', 'money'], 'roiPct' => ['Operational Capital Recovered', 'percent'], 'paybackMonths' => ['Payback Period (Months)', 'number'], 'dollarForDollarReturn' => ['Dollar-for-Dollar Return', 'number']] as $key => [$label, $type])
    <tr><td>{{ $label }}</td><td class="v">—</td><td class="v">{{ $format(data_get($res, 'tabs.economicJustification.'.$key), $type) }}</td></tr>
  @endforeach
  </tbody>
</table>
@endif

@if(data_get($res, 'tabs.billRateComponents.components'))
<table class="gasq-kv gasq-mt main-menu-section" width="100%" cellpadding="0" cellspacing="0">
  <thead><tr><td colspan="3" class="gasq-section-band"><p>Bill Rate Components</p></td></tr>
  <tr class="hl"><td>Component</td><td class="v">Cost / Hour</td><td class="v">Share of Bill Rate</td></tr></thead>
  <tbody>
  @foreach(data_get($res, 'tabs.billRateComponents.components', []) as $component)
    <tr class="{{ $loop->even ? 'alt' : '' }}"><td>{{ $component['label'] }}</td><td class="v">{{ $format($component['value'] ?? null, 'money') }}</td><td class="v">{{ $format($component['pct'] ?? null, 'percent') }}</td></tr>
  @endforeach
  <tr class="total"><td>Total Bill Rate / Hour</td><td class="v">{{ $format(data_get($res, 'tabs.billRateComponents.totalBillRate'), 'money') }}</td><td class="v">{{ $format(data_get($res, 'tabs.billRateComponents.totalBillRate') > 0 ? 100 : 0, 'percent') }}</td></tr>
  </tbody>
</table>
@endif

@if(data_get($res, 'tabs.contractSummary'))
<table class="gasq-kv gasq-mt main-menu-section" width="100%" cellpadding="0" cellspacing="0">
  <thead><tr><td colspan="2" class="gasq-section-band"><p>Contract Summary</p></td></tr></thead>
  <tbody>
  @foreach(data_get($res, 'tabs.contractSummary.tableRows', []) as $row)
    <tr class="{{ !empty($row['highlight']) ? 'total' : ($loop->even ? 'alt' : '') }}"><td>{{ $row['label'] }}</td><td class="v">{{ $format($row['value'] ?? null, $row['label'] === 'Weekly Hours' ? 'number' : 'money') }}</td></tr>
  @endforeach
  @foreach(['contributoryProfit' => ['Contributory Profit', 'money'], 'profitPctOfRevenue' => ['Profit / Revenue', 'percent'], 'profitPerHour' => ['Profit / Hour', 'money']] as $key => [$label, $type])
    <tr><td>{{ $label }}</td><td class="v">{{ $format(data_get($res, 'tabs.contractSummary.'.$key), $type) }}</td></tr>
  @endforeach
  </tbody>
</table>
@endif

@endsection

@section('report_styles')
<style>
  .main-menu-section { page-break-inside: avoid; }
  .main-menu-section thead { display: table-header-group; }
  .main-menu-section tr { page-break-inside: avoid; }
  .main-menu-section td.gasq-section-band { background: #1e3558; }
  .main-menu-section td.v { white-space: nowrap; }
</style>
@endsection
