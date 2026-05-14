@php
    use App\Support\LeadFormatting;

    // ---------- Pull inputs from the calculator's session scenario ----------
    $meta = data_get($scenario ?? [], 'meta', []);
    $alloc = (array) data_get($meta, 'allocations', []);
    $totalBudget = (float) data_get($meta, 'annualBudget', 0);
    $baselineWage = (float) (data_get($meta, 'baselineWage')
        ?? data_get($meta, 'governmentShouldCostHourly')
        ?? 25.00);

    // Scope inputs (4 scope sliders).
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

    // Coverage
    $weeklyCoverageHours = $hoursPerDay * $daysPerWeek;
    $monthlyCoverageHours = (int) round(($weeklyCoverageHours * $weeksPerYear) / 12);
    $annualCoverageHours = $hoursPerDay * $daysPerWeek * $weeksPerYear * $staffPerShift;
    $ftesRequired = max(1, (int) ceil($annualCoverageHours / $BILLABLE_HOURS_PER_FTE));

    // Per-FTE
    $annualPerInt = $internalTcoHourly * $BILLABLE_HOURS_PER_FTE;
    $annualPerVend = $vendorTcoHourly * $BILLABLE_HOURS_PER_FTE;
    $internalOt = $internalTcoHourly * $OT_MULTIPLIER;
    $vendorOt = $vendorTcoHourly * $OT_MULTIPLIER;

    // Coverage cost
    $totalAnnualInt = $internalTcoHourly * $annualCoverageHours;
    $totalAnnualVend = $vendorTcoHourly * $annualCoverageHours;
    $totalWeeklyInt = $totalAnnualInt / 52;
    $totalWeeklyVend = $totalAnnualVend / 52;
    $totalMonthlyInt = $totalAnnualInt / 12;
    $totalMonthlyVend = $totalAnnualVend / 12;
    $annualCapitalRecovery = $totalAnnualInt - $totalAnnualVend;
    $recoveryPct = $totalAnnualInt > 0 ? round(100 * $annualCapitalRecovery / $totalAnnualInt) : 0;
    $paybackMonths = $totalMonthlyInt > 0.01 ? round($totalAnnualVend / $totalMonthlyInt, 1) : 0;

    // ---------- Allocation group totals (for cover-page stats) ----------
    // Keys mirror config/budget_calculator.php exactly.
    $directLaborKeys = ['baseDirectLaborWage', 'localityPay', 'laborMarketAdjustment', 'hwCash', 'shiftDifferential', 'otHolidayPremium', 'donDoff'];
    $fringeKeys = ['ficaMedicare', 'futa', 'suta', 'workersCompensation', 'healthWelfare', 'vacation', 'paidHolidays', 'sickLeave'];
    $opsKeys = ['recruitingHiring', 'trainingCertification', 'uniformsEquipment', 'fieldSupervision', 'contractManagement', 'qualityAssurance', 'vehiclesPatrol', 'technologySystems', 'generalLiabilityInsurance', 'umbrellaOtherInsurance'];
    $ohKeys = ['administrativeHrPayroll', 'accountingLegal', 'corporateOverhead', 'ga', 'profitFee'];

    $sumGroup = static function (array $keys, array $alloc): float {
        $sum = 0.0;
        foreach ($keys as $k) {
            if (isset($alloc[$k]) && is_numeric($alloc[$k])) {
                $sum += (float) $alloc[$k];
            }
        }
        return $sum;
    };

    $directLaborPct = $sumGroup($directLaborKeys, $alloc);
    $fringePct = $sumGroup($fringeKeys, $alloc);
    $opsPct = $sumGroup($opsKeys, $alloc);
    $ohPct = $sumGroup($ohKeys, $alloc);

    $totalKnownPct = $directLaborPct + $fringePct + $opsPct + $ohPct;
    if ($totalKnownPct > 0 && abs(100 - $totalKnownPct) > 0.1) {
        // Rebalance proportionally so cover stats add up to 100%
        $factor = 100 / $totalKnownPct;
        $directLaborPct *= $factor;
        $fringePct *= $factor;
        $opsPct *= $factor;
        $ohPct *= $factor;
    }

    $directLaborAmt = $totalBudget * $directLaborPct / 100;
    $fringeAmt = $totalBudget * $fringePct / 100;
    $opsAmt = $totalBudget * $opsPct / 100;
    $ohAmt = $totalBudget * $ohPct / 100;

    // ---------- Line-Item Breakdown rows for the dedicated breakdown page ----------
    // Walks config/budget_calculator.php and renders each item with its $ and %.
    $budgetConfig = (array) config('budget_calculator', []);
    $lineGroups = [];
    foreach (($budgetConfig['groups'] ?? []) as $cfgGroup) {
        $items = [];
        $groupPct = 0.0;
        foreach (($cfgGroup['items'] ?? []) as $item) {
            $key = $item['key'] ?? null;
            if (! $key) continue;
            $itemPct = isset($alloc[$key]) && is_numeric($alloc[$key]) ? (float) $alloc[$key] : 0.0;
            $itemAmount = $totalBudget * $itemPct / 100;
            $items[] = [
                'label' => $item['label'] ?? $key,
                'color' => $item['color'] ?? '#94a3b8',
                'pct' => $itemPct,
                'amount' => $itemAmount,
            ];
            $groupPct += $itemPct;
        }
        $lineGroups[] = [
            'key' => $cfgGroup['key'] ?? '',
            'label' => $cfgGroup['label'] ?? '',
            'description' => $cfgGroup['description'] ?? '',
            'pct' => $groupPct,
            'amount' => $totalBudget * $groupPct / 100,
            'items' => $items,
        ];
    }

    // ---------- Branding / identity ----------
    $logoPath = public_path('images/site-logo.png');
    $logoData = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $vendorLogoData = null;
    $vendorLogoPath = $user?->vendorProfile?->logo_path;
    if ($vendorLogoPath) {
        $abs = storage_path('app/public/' . $vendorLogoPath);
        if (file_exists($abs)) {
            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
            $mime = match ($ext) { 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', default => 'image/jpeg' };
            $vendorLogoData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
        }
    }

    $vendorName = $user?->name ?? null;
    $vendorCompany = $user?->company ?? ($user?->vendorProfile?->company_name ?? null);
    $vendorEmail = $user?->email ?? null;
    $vendorPhone = $user?->phone ?? ($user?->vendorProfile?->phone ?? null);

    $preparedFor = $preparedFor ?? trim(($vendorName ?? '') . ($vendorCompany ? ' - ' . strtoupper($vendorCompany) : ''));
    if ($preparedFor === '') $preparedFor = 'GASQ Network Vendor';
    $reportDate = $generatedAt ?? now()->format('m/d/Y');
    $reportNumber = $reportNumber ?? ('GASQ-' . now()->format('Ymd-His') . '-V' . ((int) ($vendorId ?? 0)));

    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $pct = fn ($v) => number_format((float) $v, 2) . '%';
    $num = fn ($v) => number_format((float) $v);
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Workforce-to-Post Bill Rate Breakdown Report</title>
<style>
    @page { margin: 20px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color:#1f2937; font-size:10.5px; line-height:1.35; }
    .page { border:1.5px solid #111827; padding:14px 18px; page-break-after:always; box-sizing:border-box; }
    .page:last-child { page-break-after:auto; }

    .brand-bar { text-align:center; margin-bottom:2px; }
    .brand-bar img { height:42px; }

    h1.title { text-align:center; font-size:17px; color:#8b0a0a; margin:6px 0 8px; text-transform:uppercase; letter-spacing:.5px; }

    .meta-bar { background:#f3f4f6; border:1px solid #d1d5db; padding:7px 12px; margin-bottom:8px; font-size:10.5px; }
    .meta-bar table { width:100%; border-collapse:collapse; }
    .meta-bar td { padding:1px 0; border:0; }

    .preparedby { width:100%; border-collapse:collapse; margin:0 0 8px; border:1px solid #d1d5db; background:#fafbfd; }
    .preparedby td { border:0; padding:6px 10px; vertical-align:middle; font-size:10px; }

    /* Cover stat boxes — tightened */
    .stat-row { display:table; width:100%; border-collapse:separate; border-spacing:6px 0; margin-bottom:6px; }
    .stat-cell { display:table-cell; background:#fdf2f2; border:1.5px solid #b91c1c; padding:8px 6px; text-align:center; width:25%; }
    .stat-cell .label { font-size:8px; text-transform:uppercase; letter-spacing:.4px; color:#1f2937; font-weight:700; margin-bottom:3px; }
    .stat-cell .value { font-size:14px; font-weight:800; color:#8b0a0a; }

    .triple-row { display:table; width:100%; border-collapse:collapse; margin-bottom:8px; }
    .triple-cell { display:table-cell; border:1px solid #d1d5db; padding:8px 10px; text-align:center; width:33.3%; }
    .triple-cell.head { background:#f3f4f6; font-size:9.5px; text-transform:uppercase; letter-spacing:.4px; font-weight:700; padding:5px 8px; }
    .triple-cell.value { font-size:16px; font-weight:800; color:#8b0a0a; padding:10px 8px; }

    .blurb-box { background:#fdf2f2; border:1.5px solid #b91c1c; padding:8px 12px; margin:8px 0; color:#8b0a0a; font-size:10px; }
    .blurb-box strong { color:#8b0a0a; }

    .footer-info { text-align:center; font-size:10px; margin-top:6px; }
    .footer-info .approved { font-weight:700; }

    /* Page 2: appraisal comparison — tightened so the whole table + meta fit on a single sheet */
    .page--compare { padding:16px 22px; }
    .page--compare .brand-bar { margin-bottom:0; }
    .page--compare .brand-bar img { height:36px; }
    .page--compare h1.title { font-size:15px; margin:6px 0 6px; }
    .page--compare .compare-meta { text-align:center; color:#8b0a0a; font-weight:700; font-size:10px; margin-bottom:2px; }
    .page--compare .compare-subtitle { text-align:center; color:#4b5563; font-size:9.5px; margin:0 0 8px; }

    .compare-table { width:100%; border-collapse:collapse; }
    .compare-table th { background:#1f2937; color:#fff; padding:5px 10px; text-align:left; font-size:9px; text-transform:uppercase; letter-spacing:.5px; }
    .compare-table th.right { text-align:right; font-family:"DejaVu Sans Mono", monospace; }
    .compare-table td { padding:3.5px 10px; border-bottom:1px solid #e5e7eb; font-size:10px; line-height:1.2; }
    .compare-table td.right { text-align:right; font-family:"DejaVu Sans Mono", monospace; }
    .compare-table tr.emphasis td { background:#fff4e6; font-weight:700; border-top:1.5px solid #8b0a0a; }

    .legal { font-size:8.5px; color:#4b5563; margin-top:8px; line-height:1.4; }

    /* Allocation Group Totals summary block (above Line-Item Breakdown) */
    .alloc-totals { width:100%; border-collapse:collapse; margin-bottom:14px; border:1px solid #e5e7eb; border-radius:4px; overflow:hidden; }
    .alloc-totals th { background:#1f2937; color:#fff; padding:8px 12px; font-size:10px; text-align:left; text-transform:uppercase; letter-spacing:.4px; }
    .alloc-totals th.right { text-align:right; }
    .alloc-totals td { padding:9px 12px; border-bottom:1px solid #f1f5f9; font-size:10.5px; vertical-align:top; }
    .alloc-totals td.amount { text-align:right; font-family:"DejaVu Sans Mono", monospace; white-space:nowrap; width:120px; }
    .alloc-totals td.pct { text-align:right; font-family:"DejaVu Sans Mono", monospace; color:#6b7280; white-space:nowrap; width:70px; }
    .alloc-totals .group-name { font-weight:700; color:#1f2937; }
    .alloc-totals .group-desc { display:block; font-size:9.5px; color:#6b7280; font-weight:400; margin-top:1px; }
    .alloc-totals tr.total td { background:#eef2ff; font-weight:800; border-top:1.5px solid #1f2937; border-bottom:0; color:#1f2937; }
    .alloc-totals tr.total td.amount { color:#1d4ed8; font-size:13px; }

    /* Line-Item Breakdown */
    .line-group { margin-bottom:14px; border:1px solid #e5e7eb; border-radius:4px; overflow:hidden; }
    .line-group-head {
        background:#1f2937; color:#fff; padding:8px 12px; font-size:11px; font-weight:700;
    }
    .line-group-head .group-totals { float:right; font-weight:600; opacity:.9; }
    .line-group-head .group-desc { display:block; font-size:9px; opacity:.7; font-weight:400; margin-top:2px; }
    .line-table { width:100%; border-collapse:collapse; }
    .line-table td { padding:5px 12px; border-bottom:1px solid #f1f5f9; font-size:10.5px; }
    .line-table tr:last-child td { border-bottom:0; }
    .line-table td.dot { width:14px; }
    .line-table td.amount { text-align:right; font-family:"DejaVu Sans Mono", monospace; width:120px; }
    .line-table td.pct { text-align:right; font-family:"DejaVu Sans Mono", monospace; color:#6b7280; width:70px; }
    .line-dot { display:inline-block; width:10px; height:10px; border-radius:50%; }

    /* Page 3 components */
    .components-grid { display:table; width:100%; border-spacing:18px 0; }
    .components-col { display:table-cell; width:50%; vertical-align:top; }
    .component-block { margin-bottom:14px; }
    .component-block .title-line { font-weight:800; font-size:13px; color:#1f2937; margin-bottom:6px; }
    .component-block .body { font-size:11px; color:#374151; }
    .component-block .body strong { color:#1f2937; }
    .components-footer { margin-top:28px; text-align:center; font-weight:700; font-size:12px; }
</style>
</head>
<body>

{{-- ============ PAGE 1: COVER ============ --}}
<div class="page">
    <div class="brand-bar">
        @if($logoData)
            <img src="{{ $logoData }}" alt="GASQ">
        @else
            <div style="font-size:28px;font-weight:900;letter-spacing:2px;color:#1f2937;">GASQ</div>
        @endif
    </div>

    <h1 class="title">Workforce-to-Post™ Bill Rate Breakdown Report</h1>

    <div class="meta-bar">
        <table>
            <tr>
                <td><strong>Prepared for:</strong> {{ $preparedFor }}</td>
                <td style="text-align:center;"><strong>Date:</strong> {{ $reportDate }}</td>
                <td style="text-align:right;"><strong>Vendor ID:</strong> V{{ (int) ($vendorId ?? 0) }}</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:right;padding-top:4px;font-size:10px;color:#6b7280;">
                    <strong>Report #:</strong> {{ $reportNumber }}
                </td>
            </tr>
        </table>
    </div>

    @if($vendorName || $vendorCompany || $vendorEmail || $vendorPhone || $vendorLogoData)
    <table class="preparedby">
        <tr>
            <td style="width:130px;">
                @if($vendorLogoData)
                    <img src="{{ $vendorLogoData }}" alt="{{ $vendorCompany ?? 'Vendor' }}" style="max-height:54px;max-width:120px;">
                @else
                    <div style="font-size:9px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">No logo</div>
                @endif
            </td>
            <td>
                <div style="font-size:9px;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;margin-bottom:3px;">Prepared by</div>
                @if($vendorName)<div><strong>{{ $vendorName }}</strong>@if($vendorCompany) · {{ $vendorCompany }}@endif</div>@endif
                @if($vendorEmail || $vendorPhone)
                    <div style="color:#4b5563;">
                        @if($vendorEmail){{ $vendorEmail }}@endif
                        @if($vendorEmail && $vendorPhone) · @endif
                        @if($vendorPhone){{ $vendorPhone }}@endif
                    </div>
                @endif
            </td>
        </tr>
    </table>
    @endif

    <div class="stat-row">
        <div class="stat-cell">
            <div class="label">Direct Labor Cost</div>
            <div class="value">{{ $money($directLaborAmt) }}</div>
        </div>
        <div class="stat-cell">
            <div class="label">Fringe &amp; Employer Burden</div>
            <div class="value">{{ $money($fringeAmt) }}</div>
        </div>
        <div class="stat-cell">
            <div class="label">Operations &amp; Contract Support</div>
            <div class="value">{{ $money($opsAmt) }}</div>
        </div>
        <div class="stat-cell">
            <div class="label">OH, G&amp;A, &amp; Profit</div>
            <div class="value">{{ $money($ohAmt) }}</div>
        </div>
    </div>

    <div class="triple-row">
        <div class="triple-cell head">Total Contract / Budget Value</div>
        <div class="triple-cell head">Total Annual Hours</div>
        <div class="triple-cell head">Total Staff Required</div>
    </div>
    <div class="triple-row">
        <div class="triple-cell value">{{ $money($totalBudget) }}</div>
        <div class="triple-cell value">{{ number_format($annualCoverageHours) }}</div>
        <div class="triple-cell value">{{ $ftesRequired }}</div>
    </div>

    {{-- Side-by-side Appraisal Comparison directly under the cover stats --}}
    <table class="compare-table" style="margin-top:6px;">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Internal TCO</th>
                <th class="right">Vendor TCO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Workforce Baseline Assumption Labor Rate</td>
                <td class="right">{{ $money($baselineWage) }}</td>
                <td class="right">{{ $money($baselineWage) }}</td>
            </tr>
            <tr>
                <td>Direct Labor + Full Burden Hourly Rate</td>
                <td class="right">{{ $money($internalTcoHourly) }}</td>
                <td class="right">{{ $money($vendorTcoHourly) }}</td>
            </tr>
            <tr>
                <td>Overtime / Holiday Rate</td>
                <td class="right">{{ $money($internalOt) }}</td>
                <td class="right">{{ $money($vendorOt) }}</td>
            </tr>
            <tr>
                <td>Workforce Annual Cost per Security Professional</td>
                <td class="right">{{ $money($annualPerInt) }}</td>
                <td class="right">{{ $money($annualPerVend) }}</td>
            </tr>
            <tr>
                <td>Total Weekly Hours of Coverage</td>
                <td class="right">{{ $num($weeklyCoverageHours) }}</td>
                <td class="right">{{ $num($weeklyCoverageHours) }}</td>
            </tr>
            <tr>
                <td>Total Monthly Hours of Coverage</td>
                <td class="right">{{ $num($monthlyCoverageHours) }}</td>
                <td class="right">{{ $num($monthlyCoverageHours) }}</td>
            </tr>
            <tr>
                <td>Total Annual Hours of Coverage</td>
                <td class="right">{{ $num($annualCoverageHours) }}</td>
                <td class="right">{{ $num($annualCoverageHours) }}</td>
            </tr>
            <tr>
                <td>Total Workforce Required for Coverage</td>
                <td class="right">{{ $ftesRequired }}</td>
                <td class="right">{{ $ftesRequired }}</td>
            </tr>
            <tr>
                <td>Total Weekly Cost</td>
                <td class="right">{{ $money($totalWeeklyInt) }}</td>
                <td class="right">{{ $money($totalWeeklyVend) }}</td>
            </tr>
            <tr>
                <td>Total Monthly Cost</td>
                <td class="right">{{ $money($totalMonthlyInt) }}</td>
                <td class="right">{{ $money($totalMonthlyVend) }}</td>
            </tr>
            <tr>
                <td>Total Annual Cost</td>
                <td class="right">{{ $money($totalAnnualInt) }}</td>
                <td class="right">{{ $money($totalAnnualVend) }}</td>
            </tr>
            <tr class="emphasis">
                <td>Operational Capital Recovered</td>
                <td class="right">—</td>
                <td class="right">{{ $money($annualCapitalRecovery) }}</td>
            </tr>
            <tr class="emphasis">
                <td>Operational Capital Recovered (%)</td>
                <td class="right">—</td>
                <td class="right">{{ $recoveryPct }}%</td>
            </tr>
            <tr class="emphasis">
                <td>Payback &amp; Recovery Period</td>
                <td class="right">—</td>
                <td class="right">{{ number_format($paybackMonths, 1) }} months</td>
            </tr>
        </tbody>
    </table>

    <div class="blurb-box" style="margin:12px 0;padding:10px 14px;font-size:10.5px;">
        <strong>Our outsourced cost calculations are equal to or less than what it would cost to perform the security function in-house.</strong>
        This security capital recovery report is independent and designed to help you budget with confidence – no obligation, no commitment required.
    </div>

    <div class="footer-info" style="margin-top:8px;font-size:10px;">
        <div class="approved">"CFO Tested. CFO Approved"</div>
        <div>(470) 633-2816 | info@getasecurityquote.com | getasecurityquotenow.com</div>
    </div>
</div>

{{-- ============ PAGE 2: LINE-ITEM BILL RATE BREAKDOWN ============ --}}
<div class="page">
    <div class="brand-bar">
        @if($logoData)
            <img src="{{ $logoData }}" alt="GASQ" style="height:36px;">
        @else
            <div style="font-size:18px;font-weight:900;letter-spacing:2px;color:#1f2937;">GASQ</div>
        @endif
    </div>

    <h1 class="title" style="font-size:15px;margin:6px 0 4px;">Bill Rate — Allocation &amp; Line-Item Breakdown</h1>
    <div style="text-align:center;color:#8b0a0a;font-weight:700;font-size:10px;margin-bottom:2px;">
        Vendor ID: V{{ (int) ($vendorId ?? 0) }} · Report #: {{ $reportNumber }}
    </div>
    <p style="text-align:center;color:#4b5563;font-size:9.5px;margin:0 0 10px;">
        Every cost component contributing to the {{ $money($totalBudget) }} total contract value.
    </p>

    {{-- Allocation Group Totals — 4 groups with description + $ + % --}}
    <table class="alloc-totals">
        <thead>
            <tr>
                <th>Allocation Group Totals</th>
                <th class="right">Amount</th>
                <th class="right">% of Budget</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lineGroups as $group)
                <tr>
                    <td>
                        <span class="group-name">{{ $group['label'] }}</span>
                        @if(! empty($group['description']))
                            <span class="group-desc">{{ $group['description'] }}</span>
                        @endif
                    </td>
                    <td class="amount">{{ $money($group['amount']) }}</td>
                    <td class="pct">{{ number_format($group['pct'], 2) }}%</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>Total Contract / Budget Value <span class="group-desc">Sum of all allocation groups · auto-calculated</span></td>
                <td class="amount">{{ $money($totalBudget) }}</td>
                <td class="pct">100%</td>
            </tr>
        </tbody>
    </table>

    <h2 style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#1f2937;margin:14px 0 8px;border-bottom:1.5px solid #1f2937;padding-bottom:3px;">
        Line-Item Breakdown
    </h2>

    @foreach($lineGroups as $group)
        <div class="line-group">
            <div class="line-group-head">
                {{ $group['label'] }}
                <span class="group-totals">{{ $money($group['amount']) }} · {{ number_format($group['pct'], 2) }}%</span>
                @if(! empty($group['description']))
                    <span class="group-desc">{{ $group['description'] }}</span>
                @endif
            </div>
            <table class="line-table">
                @foreach($group['items'] as $item)
                    <tr>
                        <td class="dot"><span class="line-dot" style="background:{{ $item['color'] }};"></span></td>
                        <td>{{ $item['label'] }}</td>
                        <td class="amount">{{ $money($item['amount']) }}</td>
                        <td class="pct">{{ number_format($item['pct'], 2) }}%</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

    <div class="footer-info" style="margin-top:8px;">
        <div class="approved">"CFO Tested. CFO Approved"</div>
        <div>(470) 633-2816 | info@getasecurityquote.com | getasecurityquotenow.com</div>
    </div>
</div>

{{-- ============ PAGE 3: KEY COMPONENTS EXPLAINER ============ --}}
<div class="page">
    <div class="brand-bar">
        @if($logoData)
            <img src="{{ $logoData }}" alt="GASQ" style="height:50px;">
        @else
            <div style="font-size:22px;font-weight:900;letter-spacing:2px;color:#1f2937;">GASQ</div>
        @endif
    </div>

    <h1 class="title" style="font-size:18px; color:#1f2937;">
        Key Components of the GetASecurityQuote Security Capital Recovery Report
    </h1>
    <div style="text-align:center; color:#4b5563; font-size:13px; margin-bottom:24px;">
        Comparison of Internal In-House -vs- Outsourced Security Services
    </div>

    <div class="components-grid">
        <div class="components-col">
            <div class="component-block">
                <div class="title-line">1. Direct Labor Costs</div>
                <div class="body">
                    <strong>Wages:</strong> Hourly pay for each guard based on market, union scale, or livable wage rates.<br>
                    <strong>Overtime:</strong> If scheduling exceeds 40 hours/week per guard or during holidays.<br>
                    <strong>Paid Non-Worked Hours:</strong> Includes holidays, sick leave, vacation, training.
                </div>
            </div>
            <div class="component-block">
                <div class="title-line">2. Mandatory Payroll Burden</div>
                <div class="body">
                    <strong>Employer Taxes:</strong> FICA, FUTA, SUTA, Medicare.<br>
                    <strong>Workers Compensation Insurance:</strong> Risk-rated by job classification (high for security).<br>
                    <strong>Unemployment Insurance General</strong><br>
                    <strong>Liability Insurance</strong>
                </div>
            </div>
            <div class="component-block">
                <div class="title-line">3. Employee Benefits</div>
                <div class="body">
                    Health Insurance<br>
                    Retirement Contributions (401k, pensions) Life &amp; Disability Insurance<br>
                    Uniforms &amp; Equipment<br>
                    Background Checks, Drug Screening, Licensing Fees
                </div>
            </div>
            <div class="component-block">
                <div class="title-line">4. Mandatory Insurance &amp; Statutory Coverage</div>
                <div class="body">
                    Workmans Compensation Insurance Paid<br>
                    sick leave<br>
                    General liability insurance
                </div>
            </div>
        </div>
        <div class="components-col">
            <div class="component-block">
                <div class="title-line">5. Supervision &amp; Management</div>
                <div class="body">
                    Field Supervisors or Account Managers<br>
                    Scheduling &amp; Admin Staff<br>
                    On-call Management Support
                </div>
            </div>
            <div class="component-block">
                <div class="title-line">6. Workforce Maintenance Cost</div>
                <div class="body">
                    Recruiting, Hiring, and Turnover Costs<br>
                    Training (initial + recurring)<br>
                    Vehicles (if patrol is required)<br>
                    Office Space, Software, Timekeeping Systems
                </div>
            </div>
            <div class="component-block">
                <div class="title-line">7. Risk &amp; Liability Exposure</div>
                <div class="body">
                    Legal &amp; HR Costs for employee discipline or claims<br>
                    Potential for coverage gaps or untrained guards on post
                </div>
            </div>
        </div>
    </div>

    <div class="components-footer">
        Created by GASQ | CFO Tested. CFO Approved.
    </div>
</div>

</body>
</html>
