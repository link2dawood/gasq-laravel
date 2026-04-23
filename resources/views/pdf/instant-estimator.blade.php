<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Instant Estimator Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #24324a; padding: 28px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin: 20px 0 10px; }
        .meta { color: #667085; margin-bottom: 18px; font-size: 10px; }
        .hero {
            border: 1px solid #d7deec;
            border-radius: 10px;
            padding: 16px;
            background: #f8fbff;
        }
        .hero-label { color: #667085; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; }
        .hero-value { font-size: 24px; font-weight: bold; margin: 4px 0; color: #0f2f69; }
        .grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .grid td { vertical-align: top; width: 50%; padding: 0 6px 0 0; }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            background: #ffffff;
            min-height: 180px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 7px 0; border-bottom: 1px solid #eef2f7; }
        th { color: #667085; font-weight: 600; width: 58%; }
        tr:last-child th, tr:last-child td { border-bottom: 0; }
        .chips { margin-top: 12px; }
        .chip {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: #edf4ff;
            color: #0f2f69;
            font-size: 10px;
            margin-right: 6px;
            margin-bottom: 6px;
        }
        .footer { margin-top: 26px; font-size: 10px; color: #98a2b3; }
    </style>
</head>
<body>
@php
    $kpis = $result['kpis'] ?? [];
    $legacy = $result;
    $requestData = $result['request'] ?? [];
    $serviceLabel = $kpis['serviceLabel'] ?? 'Instant estimate';
    $outsourcedTerm = $kpis['outsourcedTerm'] ?? ($legacy['annual_total'] ?? 0);
    $outsourcedHourly = $kpis['outsourcedHourly'] ?? ($legacy['hourly_rate'] ?? 0);
    $internalTerm = $kpis['internalTerm'] ?? 0;
    $internalHourly = $kpis['internalTrueHourly'] ?? 0;
    $coverageText = ($kpis['weeksCoveredRounded'] ?? null) && ($kpis['monthsOfCoverageRounded'] ?? null)
        ? ($kpis['weeksCoveredRounded'].' weeks / '.$kpis['monthsOfCoverageRounded'].' months')
        : 'Directional estimate';
@endphp

    <h1>GASQ Instant Estimator</h1>
    <p class="meta">Report generated {{ $generatedAt ?? now()->format('M j, Y g:i A') }}</p>

    <div class="hero">
        <div class="hero-label">Outsourced Term Cost</div>
        <div class="hero-value">${{ number_format((float) $outsourcedTerm, 2) }}</div>
        <div>{{ $serviceLabel }} · {{ $coverageText }}</div>
        <div class="chips">
            <span class="chip">Outsourced hourly: ${{ number_format((float) $outsourcedHourly, 2) }}</span>
            @if($internalHourly > 0)
                <span class="chip">Internal true hourly: ${{ number_format((float) $internalHourly, 2) }}</span>
            @endif
            @if(($kpis['totalWorkforceRequired'] ?? 0) > 0)
                <span class="chip">Annualized workforce: {{ number_format((float) ($kpis['totalWorkforceRequired'] ?? 0), 0) }}</span>
            @endif
        </div>
    </div>

    <h2>Cost Comparison</h2>
    <table class="grid" role="presentation">
        <tr>
            <td>
                <div class="card">
                    <strong>Outsourced</strong>
                    <table>
                        <tr><th>Hourly</th><td>${{ number_format((float) ($kpis['outsourcedHourly'] ?? ($legacy['hourly_rate'] ?? 0)), 2) }}</td></tr>
                        <tr><th>Weekly</th><td>${{ number_format((float) ($kpis['outsourcedWeekly'] ?? ($legacy['weekly_total'] ?? 0)), 2) }}</td></tr>
                        <tr><th>Monthly</th><td>${{ number_format((float) ($kpis['outsourcedMonthly'] ?? ($legacy['monthly_total'] ?? 0)), 2) }}</td></tr>
                        <tr><th>Annual</th><td>${{ number_format((float) ($kpis['outsourcedAnnual'] ?? ($legacy['annual_total'] ?? 0)), 2) }}</td></tr>
                        <tr><th>Term</th><td>${{ number_format((float) $outsourcedTerm, 2) }}</td></tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="card">
                    <strong>Internal TCO</strong>
                    <table>
                        <tr><th>Hourly</th><td>${{ number_format((float) ($kpis['internalTrueHourly'] ?? 0), 2) }}</td></tr>
                        <tr><th>Weekly</th><td>${{ number_format((float) ($kpis['internalWeekly'] ?? 0), 2) }}</td></tr>
                        <tr><th>Monthly</th><td>${{ number_format((float) ($kpis['internalMonthly'] ?? 0), 2) }}</td></tr>
                        <tr><th>Annual</th><td>${{ number_format((float) ($kpis['internalAnnual'] ?? 0), 2) }}</td></tr>
                        <tr><th>Term</th><td>${{ number_format((float) $internalTerm, 2) }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <h2>Request Context</h2>
    <table>
        <tr><th>Requester</th><td>{{ $requestData['name'] ?? '—' }}</td></tr>
        <tr><th>Company</th><td>{{ $requestData['company'] ?? '—' }}</td></tr>
        <tr><th>Email</th><td>{{ $requestData['email'] ?? '—' }}</td></tr>
        <tr><th>Phone</th><td>{{ $requestData['phone'] ?? '—' }}</td></tr>
        <tr><th>Location</th><td>{{ $requestData['location'] ?? '—' }}</td></tr>
        <tr><th>Website</th><td>{{ $requestData['website'] ?? '—' }}</td></tr>
        <tr><th>Budget</th><td>{{ $requestData['budgetAmount'] ?? '—' }}</td></tr>
        <tr><th>Decision maker</th><td>{{ $requestData['decisionMaker'] ?? '—' }}</td></tr>
        <tr><th>Budget approved</th><td>{{ $requestData['approvedBudget'] ?? '—' }}</td></tr>
        <tr><th>Attachments</th><td>{{ !empty($requestData['attachments']) ? implode(', ', (array) $requestData['attachments']) : '—' }}</td></tr>
        <tr><th>Notes</th><td>{{ $requestData['notes'] ?? '—' }}</td></tr>
    </table>

    <h2>Recovery Metrics</h2>
    <table>
        <tr><th>Recovered capital</th><td>${{ number_format((float) ($kpis['recoveredCapitalTerm'] ?? 0), 2) }}</td></tr>
        <tr><th>Appraisal fee</th><td>${{ number_format((float) ($kpis['appraisalFee'] ?? 0), 2) }}</td></tr>
        <tr><th>Efficiency gain</th><td>{{ number_format((float) ($kpis['efficiencyGain'] ?? 0), 0) }} : 1</td></tr>
        <tr><th>Payback period</th><td>{{ number_format((float) ($kpis['breakevenMonths'] ?? 0), 2) }} months</td></tr>
        <tr><th>Weekly coverage hours</th><td>{{ number_format((float) ($kpis['weeklyCoverageHours'] ?? 0), 0) }}</td></tr>
        <tr><th>Term coverage hours</th><td>{{ number_format((float) ($kpis['termCoverageHours'] ?? 0), 0) }}</td></tr>
    </table>

    <p class="footer">GASQ Instant Estimator report. Directional pricing only; final proposal values depend on scope, site conditions, and contract structure.</p>
</body>
</html>
