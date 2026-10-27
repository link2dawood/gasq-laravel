@php
    $res = $result ?? [];
    $budgetRoi = data_get($scenario ?? [], 'meta.roiCostBasis') === 'budget'
        ? data_get($res, 'tabs.economicJustification') : null;
    if ($budgetRoi) unset($res['tabs']['economicJustification']);
    $flatten = function (array $values, string $prefix = '') use (&$flatten): array {
        $out = [];
        foreach ($values as $key => $value) {
            if (in_array($key, ['vendorComparison', 'vendorWeightedRates'], true)) continue;
            $label = trim($prefix . ' / ' . \Illuminate\Support\Str::headline((string) $key), ' /');
            if (is_array($value)) $out += $flatten($value, $label);
            else $out[$label] = $value;
        }
        return $out;
    };
    $res = $flatten($res);
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

<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Result Summary</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  @foreach($res as $key => $val)
    <tr class="{{ $loop->iteration % 2 === 0 ? 'alt' : '' }}">
      <td>{{ str_replace('_', ' ', ucfirst((string) $key)) }}</td>
      <td class="v">
        @if(is_array($val))
          {{ json_encode($val) }}
        @elseif(is_numeric($val) && str_contains((string) $val, '.'))
          {{ number_format((float) $val, 2) }}
        @else
          {{ $val }}
        @endif
      </td>
    </tr>
  @endforeach
</table>

@endsection
