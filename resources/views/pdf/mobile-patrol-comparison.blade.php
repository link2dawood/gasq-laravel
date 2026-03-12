<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Mobile Patrol Comparison Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; padding: 24px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 20px; font-size: 10px; }
        table { width: 100%; max-width: 360px; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { font-weight: bold; }
        .footer { margin-top: 32px; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    <h1>GASQ Mobile Patrol Comparison</h1>
    <p class="meta">Report generated {{ $generatedAt ?? now()->format('M j, Y g:i A') }}</p>

    <h2 style="font-size: 14px; margin-top: 16px;">Comparison result</h2>
    @php $r = $result ?? []; @endphp
    <table>
        <tr><th>Scenario A annual</th><td>${{ number_format($r['scenario_a_annual'] ?? 0, 2) }}</td></tr>
        <tr><th>Scenario B annual</th><td>${{ number_format($r['scenario_b_annual'] ?? 0, 2) }}</td></tr>
        <tr><th>Savings (B vs A)</th><td>${{ number_format($r['savings'] ?? 0, 2) }}</td></tr>
        <tr><th>Savings %</th><td>{{ number_format($r['savings_percent'] ?? 0, 1) }}%</td></tr>
    </table>

    <p class="footer">GASQ Security Calculator – Mobile Patrol Comparison Report</p>
</body>
</html>
