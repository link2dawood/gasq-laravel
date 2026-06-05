@php
    // ---------- Pull inputs from the calculator's session scenario ----------
    $meta = data_get($scenario ?? [], 'meta', []);
    $alloc = (array) data_get($meta, 'allocations', []);
    $totalBudget = (float) data_get($meta, 'annualBudget', 0);
    $baselineWage = (float) (data_get($meta, 'baselineWage')
        ?? data_get($meta, 'governmentShouldCostHourly')
        ?? 25.00);

    $scope = (array) data_get($meta, 'scope', []);
    $hoursPerDay = max(1, min(24, (int) (data_get($scope, 'hoursOfCoveragePerDay') ?? data_get($meta, 'hoursPerDay') ?? 24)));
    $daysPerWeek = max(1, min(7, (int) (data_get($scope, 'daysOfCoveragePerWeek') ?? data_get($meta, 'daysPerWeek') ?? 7)));
    $weeksPerYear = max(1, min(52, (int) (data_get($scope, 'weeksOfCoverage') ?? data_get($meta, 'weeksPerYear') ?? 52)));
    $staffPerShift = max(1, min(100, (int) (data_get($scope, 'staffPerShift') ?? data_get($meta, 'staffPerShift') ?? 1)));

    // ---------- GASQ TCO formula ----------
    $EMPLOYER_FRINGE_FACTOR = 0.70;
    $PAID_HOURS_PER_FTE = 3744;
    $BILLABLE_HOURS_PER_FTE = 1456;
    $VENDOR_DISCOUNT_FACTOR = 0.70;
    $OT_MULTIPLIER = 1.5;

    $loadedWage = $baselineWage / $EMPLOYER_FRINGE_FACTOR;
    $annualWorkforceCost = $loadedWage * $PAID_HOURS_PER_FTE;
    $internalTcoHourly = $annualWorkforceCost / $BILLABLE_HOURS_PER_FTE;
    $vendorTcoHourly = $internalTcoHourly * $VENDOR_DISCOUNT_FACTOR;

    $weeklyCoverageHours = $hoursPerDay * $daysPerWeek;
    $monthlyCoverageHours = (int) round(($weeklyCoverageHours * $weeksPerYear) / 12);
    $annualCoverageHours = $hoursPerDay * $daysPerWeek * $weeksPerYear * $staffPerShift;
    $ftesRequired = max(1, (int) ceil($annualCoverageHours / $BILLABLE_HOURS_PER_FTE));

    $annualPerInt = $internalTcoHourly * $BILLABLE_HOURS_PER_FTE;
    $annualPerVend = $vendorTcoHourly * $BILLABLE_HOURS_PER_FTE;
    $internalOt = $internalTcoHourly * $OT_MULTIPLIER;
    $vendorOt = $vendorTcoHourly * $OT_MULTIPLIER;

    $totalAnnualInt = $internalTcoHourly * $annualCoverageHours;
    $totalAnnualVend = $vendorTcoHourly * $annualCoverageHours;
    $totalWeeklyInt = $totalAnnualInt / 52;
    $totalWeeklyVend = $totalAnnualVend / 52;
    $totalMonthlyInt = $totalAnnualInt / 12;
    $totalMonthlyVend = $totalAnnualVend / 12;
    $annualCapitalRecovery = $totalAnnualInt - $totalAnnualVend;
    $recoveryPct = $totalAnnualInt > 0 ? round(100 * $annualCapitalRecovery / $totalAnnualInt) : 0;
    $paybackMonths = $totalMonthlyInt > 0.01 ? round($totalAnnualVend / $totalMonthlyInt, 1) : 0;

    // ---------- Allocation group totals ----------
    $directLaborKeys = ['baseDirectLaborWage', 'localityPay', 'laborMarketAdjustment', 'hwCash', 'shiftDifferential', 'otHolidayPremium', 'donDoff'];
    $fringeKeys = ['ficaMedicare', 'futa', 'suta', 'workersCompensation', 'healthWelfare', 'vacation', 'paidHolidays', 'sickLeave'];
    $opsKeys = ['recruitingHiring', 'trainingCertification', 'uniformsEquipment', 'fieldSupervision', 'contractManagement', 'qualityAssurance', 'vehiclesPatrol', 'technologySystems', 'generalLiabilityInsurance', 'umbrellaOtherInsurance'];
    $ohKeys = ['administrativeHrPayroll', 'accountingLegal', 'corporateOverhead', 'ga', 'profitFee'];

    $sumGroup = static function (array $keys, array $alloc): float {
        $sum = 0.0;
        foreach ($keys as $k) {
            if (isset($alloc[$k]) && is_numeric($alloc[$k])) $sum += (float) $alloc[$k];
        }
        return $sum;
    };

    $directLaborPct = $sumGroup($directLaborKeys, $alloc);
    $fringePct = $sumGroup($fringeKeys, $alloc);
    $opsPct = $sumGroup($opsKeys, $alloc);
    $ohPct = $sumGroup($ohKeys, $alloc);

    $totalKnownPct = $directLaborPct + $fringePct + $opsPct + $ohPct;
    if ($totalKnownPct > 0 && abs(100 - $totalKnownPct) > 0.1) {
        $factor = 100 / $totalKnownPct;
        $directLaborPct *= $factor; $fringePct *= $factor; $opsPct *= $factor; $ohPct *= $factor;
    }

    // Line items
    $budgetConfig = (array) config('budget_calculator', []);
    $lineGroups = [];
    foreach (($budgetConfig['groups'] ?? []) as $cfgGroup) {
        $items = [];
        $groupPct = 0.0;
        foreach (($cfgGroup['items'] ?? []) as $item) {
            $key = $item['key'] ?? null; if (! $key) continue;
            $itemPct = isset($alloc[$key]) && is_numeric($alloc[$key]) ? (float) $alloc[$key] : 0.0;
            $itemAmount = $totalBudget * $itemPct / 100;
            $groupPct += $itemPct;
            // Hide $0.00 line items — only list items that carry a real dollar value.
            if (round($itemAmount, 2) <= 0) continue;
            $items[] = ['label' => $item['label'] ?? $key, 'pct' => $itemPct, 'amount' => $itemAmount];
        }
        $lineGroups[] = [
            'label' => $cfgGroup['label'] ?? '',
            'description' => $cfgGroup['description'] ?? '',
            'pct' => $groupPct,
            'amount' => $totalBudget * $groupPct / 100,
            'items' => $items,
        ];
    }

    // ---------- Identity ----------
    $contactName    = $user?->name ?? null;
    $contactCompany = $user?->company ?? ($user?->vendorProfile?->company_name ?? null);
    $contactAddress = $user?->vendorProfile?->address ?? trim(implode(', ', array_filter([$user?->city, $user?->state, $user?->zip_code])));
    $contactEmail   = $user?->email ?? null;
    $contactPhone   = $user?->phone ?? ($user?->vendorProfile?->phone ?? null);

    $reportNumber = $reportNumber ?? ('GASQ ' . now()->format('Y-m-d') . '-V' . ((int) ($vendorId ?? 0)));

    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $moneyK = fn ($v) => '$' . number_format((float) $v, 0);
    $num = fn ($v) => number_format((float) $v);
@endphp

@extends('pdf.layouts.gasq-report', [
    'title' => 'GASQ Workforce-to-Post Report',
    'subtitle' => 'Bill Rate Breakdown · Internal TCO vs Vendor TCO',
    'reportNumber' => $reportNumber,
    'reportType' => 'Vendor — Full Report',
    'contactName' => $contactName,
    'contactCompany' => $contactCompany,
    'contactAddress' => $contactAddress,
    'contactEmail' => $contactEmail,
    'contactPhone' => $contactPhone,
])

@section('stat_grid')
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="33%" class="stat-grid-label"><p>Total Contract / Budget</p></td>
    <td width="34%" class="stat-grid-label"><p>Annual Capital Recovery</p></td>
    <td width="33%" class="stat-grid-label last"><p>Payback Period</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-blue">
      <p class="num">{{ $moneyK($totalBudget) }}</p>
      <p class="sub">{{ $num($annualCoverageHours) }} annual coverage hrs</p>
    </td>
    <td class="stat-grid-value bg-green">
      <p class="num">{{ $moneyK($annualCapitalRecovery) }}</p>
      <p class="sub">{{ $recoveryPct }}% recovered vs in-house</p>
    </td>
    <td class="stat-grid-value bg-pink last">
      <p class="num">{{ number_format($paybackMonths, 1) }} mo</p>
      <p class="sub">return on outsourcing</p>
    </td>
  </tr>
  <tr>
    <td class="stat-grid-label"><p>Internal TCO / hr</p></td>
    <td class="stat-grid-label"><p>Vendor TCO / hr</p></td>
    <td class="stat-grid-label last"><p>Total Staff Required</p></td>
  </tr>
  <tr>
    <td class="stat-grid-value bg-purple">
      <p class="num">{{ $money($internalTcoHourly) }}</p>
      <p class="sub">buyer in-house cost</p>
    </td>
    <td class="stat-grid-value bg-peach">
      <p class="num">{{ $money($vendorTcoHourly) }}</p>
      <p class="sub">vendor rate offered</p>
    </td>
    <td class="stat-grid-value bg-sky last">
      <p class="num">{{ $ftesRequired }}</p>
      <p class="sub">FTEs to deliver scope</p>
    </td>
  </tr>
</table>
@endsection

@section('content')

{{-- Allocation Group Totals --}}
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Allocation Group Totals</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  @foreach($lineGroups as $i => $group)
    <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
      <td>
        <span style="font-weight:bold; color:#1e3558;">{{ $group['label'] }}</span>
        @if($group['description'])
          <span style="display:block; font-size:9px; color:#6b7280; margin-top:1px;">{{ $group['description'] }}</span>
        @endif
      </td>
      <td class="v">{{ $money($group['amount']) }}<span style="font-weight:normal; color:#6b7280; margin-left:8px;">{{ number_format($group['pct'], 2) }}%</span></td>
    </tr>
  @endforeach
  <tr class="total">
    <td>Total Contract / Budget Value</td>
    <td class="v">{{ $money($totalBudget) }}<span style="margin-left:8px;">100%</span></td>
  </tr>
</table>

{{-- Appraisal Comparison --}}
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Appraisal Comparison — Internal TCO vs Vendor TCO</p></td></tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-kv">
  <tr style="background:#f0f4fb;">
    <td style="font-weight:bold; color:#1e3558;">Description</td>
    <td class="v" style="color:#1e3558;">Internal TCO</td>
    <td class="v" style="color:#1e3558;">Vendor TCO</td>
  </tr>
  @php
    $rows = [
        ['Workforce Baseline Assumption Labor Rate', $money($baselineWage), $money($baselineWage)],
        ['Direct Labor + Full Burden Hourly Rate', $money($internalTcoHourly), $money($vendorTcoHourly)],
        ['Overtime / Holiday Rate', $money($internalOt), $money($vendorOt)],
        ['Workforce Annual Cost per Security Professional', $money($annualPerInt), $money($annualPerVend)],
        ['Total Weekly Hours of Coverage', $num($weeklyCoverageHours), $num($weeklyCoverageHours)],
        ['Total Monthly Hours of Coverage', $num($monthlyCoverageHours), $num($monthlyCoverageHours)],
        ['Total Annual Hours of Coverage', $num($annualCoverageHours), $num($annualCoverageHours)],
        ['Total Workforce Required for Coverage', (string) $ftesRequired, (string) $ftesRequired],
        ['Total Weekly Cost', $money($totalWeeklyInt), $money($totalWeeklyVend)],
        ['Total Monthly Cost', $money($totalMonthlyInt), $money($totalMonthlyVend)],
        ['Total Annual Cost', $money($totalAnnualInt), $money($totalAnnualVend)],
    ];
  @endphp
  @foreach($rows as $i => [$label, $intVal, $vendVal])
    <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
      <td>{{ $label }}</td>
      <td class="v">{{ $intVal }}</td>
      <td class="v">{{ $vendVal }}</td>
    </tr>
  @endforeach
  <tr style="background:#e8f5eb;"><td style="font-weight:bold; color:#1e3558; border-top:2px solid #1e3558;">Operational Capital Recovered</td><td class="v" style="border-top:2px solid #1e3558;">—</td><td class="v" style="color:#1e3558; font-size:12px; border-top:2px solid #1e3558;">{{ $money($annualCapitalRecovery) }}</td></tr>
  <tr style="background:#e8f5eb;"><td style="font-weight:bold; color:#1e3558;">Operational Capital Recovered (%)</td><td class="v">—</td><td class="v" style="color:#1e3558;">{{ $recoveryPct }}%</td></tr>
  <tr style="background:#e8f5eb;"><td style="font-weight:bold; color:#1e3558;">Payback &amp; Recovery Period</td><td class="v">—</td><td class="v" style="color:#1e3558;">{{ number_format($paybackMonths, 1) }} months</td></tr>
</table>

{{-- Line-Item Breakdown --}}
<table width="100%" cellpadding="0" cellspacing="0" class="gasq-mt">
  <tr><td class="gasq-section-band"><p>Line-Item Breakdown</p></td></tr>
</table>
@foreach($lineGroups as $group)
@continue(empty($group['items']))
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:6px; border:1px solid #d8dff0; border-collapse:collapse;">
  <tr style="background:#1e3558;">
    <td style="padding:7px 16px; font-size:10.5px; font-weight:bold; color:#fff;">{{ $group['label'] }}</td>
    <td style="padding:7px 16px; font-size:10.5px; font-weight:bold; color:#fff; text-align:right;">{{ $money($group['amount']) }} · {{ number_format($group['pct'], 2) }}%</td>
  </tr>
  @foreach($group['items'] as $j => $item)
    <tr style="background:{{ $j % 2 === 0 ? '#fff' : '#f6f8fb' }};">
      <td style="padding:5px 16px; font-size:10px; color:#374151; border-bottom:1px solid #e8edf5;">{{ $item['label'] }}</td>
      <td style="padding:5px 16px; font-size:10px; color:#1e3558; font-weight:bold; text-align:right; font-variant-numeric:tabular-nums; border-bottom:1px solid #e8edf5;">{{ $money($item['amount']) }}<span style="font-weight:normal; color:#6b7280; margin-left:8px;">{{ number_format($item['pct'], 2) }}%</span></td>
    </tr>
  @endforeach
</table>
@endforeach

<p class="gasq-note">
    All price calculations include the full cost of workforce staffing and support services, including livable base wages, employer-paid payroll taxes (FICA, FUTA, SUTA), workers compensation, general liability insurance, unemployment insurance, paid time off, healthcare and fringe benefits, uniforms and equipment, onboarding and training, site supervision, quality assurance oversight, management and administrative support, 24/7 dispatch capability, compliance with local, state, and federal labor laws, and all service-level guarantees, including open post protection, vendor replacement, and price lock guarantees, unless otherwise specified.
</p>

@endsection
