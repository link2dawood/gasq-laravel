@php
    use App\Support\LeadFormatting;

    // Pull everything from session payload (scenario.meta) and result computed by backend.
    $meta = data_get($scenario ?? [], 'meta', []);
    $alloc = (array) data_get($meta, 'allocations', []);
    $totalBudget = (float) data_get($meta, 'annualBudget', 0);
    $annualHours = (float) data_get($meta, 'annualBillableHours', 0);
    $hourly = (float) data_get($meta, 'governmentShouldCostHourly', 0);

    // Sum of allocation percentages and per-group totals.
    // Group keys we know about from the budget_calculator config:
    //   Direct Labor: baseDirectLaborWage, localityPay, laborMarketAdjustment, hwCash, shiftDifferential
    //   Fringe & Employer Burden: hwBenefits, ficaMedicare, futa, suta, workersComp, vacation,
    //                              paidHolidays, sickLeave
    //   Operations & Contract Support: operations + contract support items
    //   Overhead, G&A, Profit: corporateOverhead, ga, profitFee, accountingLegal
    $directLaborKeys = ['baseDirectLaborWage','localityPay','laborMarketAdjustment','hwCash','shiftDifferential'];
    $fringeKeys = ['hwBenefits','ficaMedicare','futa','suta','workersComp','vacation','paidHolidays','sickLeave'];
    $opsKeys = ['operations','dispatch','siteSupervision','qaOversight','vehicles','equipment','uniforms','onboardingTraining','compliance','technology','insuranceCorporate','riskMgmt'];
    $ohKeys = ['corporateOverhead','generalAdmin','ga','accountingLegal','profitFee','profit'];

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

    // Fallback: if config keys don't all match, distribute remaining percentage to OH.
    $totalKnownPct = $directLaborPct + $fringePct + $opsPct + $ohPct;
    $unaccountedPct = max(0, 100 - $totalKnownPct);
    if ($unaccountedPct > 0 && $totalKnownPct > 0) {
        // Distribute proportionally to existing groups.
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

    // Coverage breakdown
    $weeklyHours = $annualHours > 0 ? round($annualHours / 52) : 0;
    $monthlyHours = $annualHours > 0 ? round($annualHours / 12) : 0;
    $weeksOfCoverage = 52;
    // Total workforce required: assume 1,456 annual paid hours per FTE (industry standard).
    $hoursPerFte = 1456;
    $ftesRequired = $annualHours > 0 ? max(1, (int) ceil($annualHours / $hoursPerFte)) : 0;

    // Report header values
    $preparedFor = $preparedFor ?? trim(($user?->name ?? '') . ($user?->company ? ' - ' . strtoupper($user->company) : ''));
    if ($preparedFor === '') {
        $preparedFor = 'GASQ Network Vendor';
    }
    $reportDate = $generatedAt ?? now()->format('m/d/Y');
    $reportNumber = $reportNumber ?? ('GASQ-' . now()->format('Ymd-His') . '-V' . ((int) ($vendorId ?? 0)));

    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $pct = fn ($v) => number_format((float) $v, 2) . '%';
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Workforce-to-Post Bill Rate Breakdown Report</title>
<style>
    @page { margin: 36px 28px; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        color: #1f2937;
        font-size: 11px;
        line-height: 1.45;
    }
    .page { page-break-after: always; }
    .page:last-child { page-break-after: auto; }

    .brand-bar {
        text-align: center;
        margin-bottom: 16px;
    }
    .brand-bar .gasq-mark {
        font-size: 30px;
        font-weight: 900;
        letter-spacing: 2px;
        color: #1f2937;
    }
    .brand-bar .gasq-domain {
        font-size: 9px;
        color: #6b7280;
        letter-spacing: 2px;
        margin-top: -2px;
    }

    h1.title {
        text-align: center;
        font-size: 22px;
        color: #8b0a0a;
        margin: 14px 0 18px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .meta-bar {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        padding: 12px 16px;
        margin-bottom: 18px;
        font-size: 12px;
    }
    .meta-bar table { width: 100%; border-collapse: collapse; }
    .meta-bar td { padding: 2px 0; border: 0; }

    .stat-row { display: table; width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 10px; }
    .stat-cell {
        display: table-cell;
        background: #fdf2f2;
        border: 2px solid #b91c1c;
        padding: 14px;
        text-align: center;
        width: 25%;
    }
    .stat-cell .label {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #1f2937;
        font-weight: 700;
        margin-bottom: 6px;
    }
    .stat-cell .value {
        font-size: 17px;
        font-weight: 800;
        color: #8b0a0a;
    }

    .triple-row { display: table; width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .triple-cell {
        display: table-cell;
        border: 1px solid #d1d5db;
        padding: 14px;
        text-align: center;
        width: 33.3%;
    }
    .triple-cell.head {
        background: #f3f4f6;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 8px 14px;
    }
    .triple-cell.value { font-size: 22px; font-weight: 800; color: #8b0a0a; padding: 18px 14px; }

    .blurb-box {
        background: #fdf2f2;
        border: 2px solid #b91c1c;
        padding: 14px 18px;
        margin: 18px 0;
        color: #8b0a0a;
        font-size: 11.5px;
    }
    .blurb-box strong { color: #8b0a0a; }

    .footer-info {
        text-align: center;
        font-size: 11px;
        margin-top: 22px;
    }
    .footer-info .approved { font-weight: 700; }

    /* Page 2 table */
    .breakdown-table { width: 100%; border-collapse: collapse; }
    .breakdown-table th {
        background: #1f2937;
        color: #fff;
        padding: 12px;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .breakdown-table th.right { text-align: right; }
    .breakdown-table td {
        padding: 11px 12px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 11.5px;
    }
    .breakdown-table td.right { text-align: right; }
    .breakdown-table tr.total td { border-top: 2px solid #8b0a0a; border-bottom: 2px solid #8b0a0a; font-weight: 700; }

    .legal-fineprint {
        font-size: 10px;
        color: #4b5563;
        margin-top: 18px;
        line-height: 1.55;
    }

    /* Page 3 components */
    .components-grid { display: table; width: 100%; border-spacing: 18px 0; }
    .components-col { display: table-cell; width: 50%; vertical-align: top; }
    .component-block { margin-bottom: 14px; }
    .component-block .num {
        display: inline-block;
        font-weight: 800;
        font-size: 14px;
        margin-bottom: 6px;
    }
    .component-block .title-line {
        font-weight: 800;
        font-size: 13px;
        color: #1f2937;
        margin-bottom: 6px;
    }
    .component-block .body { font-size: 11px; color: #374151; }
    .component-block .body strong { color: #1f2937; }
    .components-footer {
        margin-top: 28px;
        text-align: center;
        font-weight: 700;
        font-size: 12px;
    }
</style>
</head>
<body>

{{-- ============ PAGE 1: COVER / SUMMARY ============ --}}
<div class="page">
    <div class="brand-bar">
        <div class="gasq-mark">GASQ</div>
        <div class="gasq-domain">GETASECURITYQUOTE.COM</div>
    </div>

    <h1 class="title">Workforce-to-Post™ Bill Rate Breakdown Report</h1>

    <div class="meta-bar">
        <table>
            <tr>
                <td><strong>Prepared for:</strong> {{ $preparedFor }}</td>
                <td style="text-align:center;"><strong>Date:</strong> {{ $reportDate }}</td>
                <td style="text-align:right;"><strong>Report #:</strong> {{ $reportNumber }}</td>
            </tr>
        </table>
    </div>

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
        <div class="triple-cell value">{{ number_format($annualHours) }}</div>
        <div class="triple-cell value">{{ $ftesRequired }}</div>
    </div>

    <div class="blurb-box">
        <strong>Our outsourced cost calculations are equal to or less than what it would cost to perform the security function in-house.</strong><br>
        This security capital recovery report is independent and designed to help you budget with confidence – no obligation, no commitment required.
    </div>

    <div class="footer-info">
        <div class="approved">"CFO Tested. CFO Approved"</div>
        <div>(470) 633-2816 | info@getasecurityquote.com | getasecurityquotenow.com</div>
    </div>
</div>

{{-- ============ PAGE 2: BREAKDOWN TABLE ============ --}}
<div class="page">
    <div class="brand-bar">
        <div class="gasq-mark" style="font-size:22px;">GASQ</div>
        <div class="gasq-domain">GETASECURITYQUOTE.COM</div>
    </div>

    <h1 class="title" style="font-size:18px;">Workforce-to-Post™ Bill Rate Breakdown Report</h1>
    <div style="text-align:center; color:#8b0a0a; font-weight:700; margin-bottom:14px;">
        Report #: {{ $reportNumber }}
    </div>

    <table class="breakdown-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Total Cost to Outsource</th>
                <th class="right">Total Cost % to Outsource</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Workforce Direct Labor Rate</strong></td>
                <td class="right">{{ $money($directLaborAmt) }}</td>
                <td class="right">{{ $pct($directLaborPct) }}</td>
            </tr>
            <tr>
                <td><strong>Fringe &amp; Employer Burden</strong></td>
                <td class="right">{{ $money($fringeAmt) }}</td>
                <td class="right">{{ $pct($fringePct) }}</td>
            </tr>
            <tr>
                <td><strong>Operations &amp; Contract Support</strong></td>
                <td class="right">{{ $money($opsAmt) }}</td>
                <td class="right">{{ $pct($opsPct) }}</td>
            </tr>
            <tr>
                <td><strong>Overhead, G&amp;A, &amp; Profit</strong></td>
                <td class="right">{{ $money($ohAmt) }}</td>
                <td class="right">{{ $pct($ohPct) }}</td>
            </tr>
            <tr class="total">
                <td>Total Contract / Budget Required Value</td>
                <td class="right">{{ $money($totalBudget) }}</td>
                <td class="right">100%</td>
            </tr>
            <tr><td><strong>Total Weekly Hours of Coverage</strong></td><td class="right">{{ number_format($weeklyHours) }}</td><td class="right"></td></tr>
            <tr><td><strong>Total Monthly Hours of Coverage</strong></td><td class="right">{{ number_format($monthlyHours) }}</td><td class="right"></td></tr>
            <tr><td><strong>Total Annual Hours of Coverage</strong></td><td class="right">{{ number_format($annualHours) }}</td><td class="right"></td></tr>
            <tr><td><strong>Total Weeks of Coverage</strong></td><td class="right">{{ $weeksOfCoverage }}</td><td class="right"></td></tr>
            <tr><td><strong>Total Workforce Required for Coverage</strong></td><td class="right">{{ $ftesRequired }}</td><td class="right"></td></tr>
        </tbody>
    </table>

    <p class="legal-fineprint">
        All price calculations include the full cost of workforce staffing and support services, including livable base wages, employer-paid payroll taxes (FICA, FUTA, SUTA), workers compensation, general liability insurance, unemployment insurance, paid time off, healthcare and fringe benefits, uniforms and equipment, onboarding and training, site supervision, quality assurance oversight, management and administrative support, 24/7 dispatch capability, compliance with local, state, and federal labor laws, and all service-level guarantees, including open post protection, vendor replacement, and price lock guarantees, unless otherwise specified.
    </p>
</div>

{{-- ============ PAGE 3: KEY COMPONENTS EXPLAINER ============ --}}
<div class="page">
    <div class="brand-bar">
        <div class="gasq-mark" style="font-size:22px;">GASQ</div>
        <div class="gasq-domain">GETASECURITYQUOTE.COM</div>
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
