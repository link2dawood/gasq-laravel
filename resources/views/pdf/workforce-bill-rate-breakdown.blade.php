@php
    use App\Support\LeadFormatting;

    // ---------- Pull inputs from the calculator's session scenario ----------
    $meta = data_get($scenario ?? [], 'meta', []);
    $baselineWage = (float) (data_get($meta, 'baselineWage')
        ?? data_get($meta, 'governmentShouldCostHourly')
        ?? 25.00);

    // Scope inputs come from the 4 scope sliders on the calculator.
    $scope = (array) data_get($meta, 'scope', []);
    $hoursPerDay = max(1, min(24, (int) (data_get($scope, 'hoursOfCoveragePerDay') ?? data_get($meta, 'hoursPerDay') ?? 24)));
    $daysPerWeek = max(1, min(7, (int) (data_get($scope, 'daysOfCoveragePerWeek') ?? data_get($meta, 'daysPerWeek') ?? 7)));
    $weeksPerYear = max(1, min(52, (int) (data_get($scope, 'weeksOfCoverage') ?? data_get($meta, 'weeksPerYear') ?? 52)));
    $staffPerShift = max(1, min(100, (int) (data_get($scope, 'staffPerShift') ?? data_get($meta, 'staffPerShift') ?? 1)));

    // ---------- GASQ side-by-side TCO formula (mirrors budget.blade.php refreshAppraisal()) ----------
    $EMPLOYER_FRINGE_FACTOR = 0.70;
    $PAID_HOURS_PER_FTE = 3744;
    $BILLABLE_HOURS_PER_FTE = 1456;
    $VENDOR_DISCOUNT_FACTOR = 0.70;
    $OT_MULTIPLIER = 1.5;

    $loadedWage = $baselineWage / $EMPLOYER_FRINGE_FACTOR;
    $annualWorkforceCost = $loadedWage * $PAID_HOURS_PER_FTE;
    $internalTcoHourly = $annualWorkforceCost / $BILLABLE_HOURS_PER_FTE;
    $vendorTcoHourly = $internalTcoHourly * $VENDOR_DISCOUNT_FACTOR;
    $capitalRecoveryPerHour = $internalTcoHourly - $vendorTcoHourly;

    // Coverage hours
    $weeklyCoverageHours = $hoursPerDay * $daysPerWeek;
    $monthlyCoverageHours = (int) round(($weeklyCoverageHours * $weeksPerYear) / 12);
    $annualCoverageHours = $hoursPerDay * $daysPerWeek * $weeksPerYear * $staffPerShift;
    $ftesRequired = max(1, (int) ceil($annualCoverageHours / $BILLABLE_HOURS_PER_FTE));

    // Per-FTE figures
    $annualPerInt = $internalTcoHourly * $BILLABLE_HOURS_PER_FTE;
    $annualPerVend = $vendorTcoHourly * $BILLABLE_HOURS_PER_FTE;
    $internalOt = $internalTcoHourly * $OT_MULTIPLIER;
    $vendorOt = $vendorTcoHourly * $OT_MULTIPLIER;

    // Coverage-cost figures
    $totalAnnualInt = $internalTcoHourly * $annualCoverageHours;
    $totalAnnualVend = $vendorTcoHourly * $annualCoverageHours;
    $totalWeeklyInt = $totalAnnualInt / 52;
    $totalWeeklyVend = $totalAnnualVend / 52;
    $totalMonthlyInt = $totalAnnualInt / 12;
    $totalMonthlyVend = $totalAnnualVend / 12;

    // Recovery
    $annualCapitalRecovery = $totalAnnualInt - $totalAnnualVend;
    $recoveryPct = $totalAnnualInt > 0 ? round(100 * $annualCapitalRecovery / $totalAnnualInt) : 0;
    $paybackMonths = $totalMonthlyInt > 0.01 ? round($totalAnnualVend / $totalMonthlyInt, 1) : 0;

    // Branding
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
    $num = fn ($v) => number_format((float) $v);
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Workforce-to-Post Appraisal Comparison</title>
<style>
    @page { margin: 28px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color:#1f2937; font-size:11px; line-height:1.45; }
    .page { border: 1.5px solid #111827; padding: 24px 28px; box-sizing:border-box; }
    .brand-bar { text-align:center; margin-bottom:4px; }
    .brand-bar img { height:56px; }
    h1.title { text-align:center; font-size:20px; color:#8b0a0a; margin:14px 0 18px; text-transform:uppercase; letter-spacing:.5px; }

    .meta-bar { background:#f3f4f6; border:1px solid #d1d5db; padding:10px 14px; margin-bottom:14px; font-size:11px; }
    .meta-bar table { width:100%; border-collapse:collapse; }
    .meta-bar td { padding:2px 0; border:0; }

    .preparedby { width:100%; border-collapse:collapse; margin:6px 0 16px; border:1px solid #d1d5db; background:#fafbfd; }
    .preparedby td { border:0; padding:8px 12px; vertical-align:middle; font-size:10.5px; }

    .compare-table { width:100%; border-collapse:collapse; }
    .compare-table th {
        background:#1f2937; color:#fff; padding:10px 12px; text-align:left; font-size:10px;
        text-transform:uppercase; letter-spacing:.5px;
    }
    .compare-table th.right { text-align:right; font-family: "DejaVu Sans Mono", monospace; }
    .compare-table td { padding:9px 12px; border-bottom:1px solid #e5e7eb; font-size:11px; }
    .compare-table td.right { text-align:right; font-family: "DejaVu Sans Mono", monospace; }
    .compare-table tr.emphasis td { background:#fff4e6; font-weight:700; border-top:1.5px solid #8b0a0a; }

    .legal { font-size:9.5px; color:#4b5563; margin-top:14px; line-height:1.5; }
    .footer-line { text-align:center; font-size:10px; color:#374151; margin-top:14px; }
    .footer-line .approved { font-weight:700; }
</style>
</head>
<body>

<div class="page">
    <div class="brand-bar">
        @if($logoData)
            <img src="{{ $logoData }}" alt="GASQ">
        @else
            <div style="font-size:24px;font-weight:900;letter-spacing:2px;">GASQ</div>
        @endif
    </div>

    <h1 class="title">Workforce-to-Post™ Appraisal Comparison</h1>

    <div class="meta-bar">
        <table>
            <tr>
                <td><strong>Prepared for:</strong> {{ $preparedFor }}</td>
                <td style="text-align:center;"><strong>Date:</strong> {{ $reportDate }}</td>
                <td style="text-align:right;"><strong>Report #:</strong> {{ $reportNumber }}</td>
            </tr>
        </table>
    </div>

    {{-- Prepared-by calling card --}}
    @if($vendorName || $vendorCompany || $vendorEmail || $vendorPhone || $vendorLogoData)
    <table class="preparedby">
        <tr>
            <td style="width:130px;">
                @if($vendorLogoData)
                    <img src="{{ $vendorLogoData }}" alt="{{ $vendorCompany ?? 'Vendor' }}" style="max-height:50px;max-width:120px;">
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

    {{-- Appraisal Comparison Summary — mirrors the calculator's right-column table 1:1. --}}
    <table class="compare-table">
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

    <p class="legal">
        All price calculations include the full cost of workforce staffing and support services, including livable base wages,
        employer-paid payroll taxes (FICA, FUTA, SUTA), workers compensation, general liability insurance, unemployment insurance,
        paid time off, healthcare and fringe benefits, uniforms and equipment, onboarding and training, site supervision, quality
        assurance oversight, management and administrative support, 24/7 dispatch capability, compliance with local, state, and
        federal labor laws, and all service-level guarantees, including open post protection, vendor replacement, and price lock
        guarantees, unless otherwise specified.
    </p>

    <div class="footer-line">
        <div class="approved">"CFO Tested. CFO Approved"</div>
        <div>(470) 633-2816 | info@getasecurityquote.com | getasecurityquotenow.com</div>
    </div>
</div>

</body>
</html>
