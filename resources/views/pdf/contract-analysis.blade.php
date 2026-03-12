<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Contract Analysis Report</title>
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
    <h1>GASQ Contract Analysis</h1>
    <p class="meta">Report generated {{ $generatedAt ?? now()->format('M j, Y g:i A') }}</p>

    <h2 style="font-size: 14px; margin-top: 16px;">Summary</h2>
    @php $r = $result ?? []; @endphp
    <table>
        <tr><th>Total annual hours</th><td>{{ number_format($r['total_annual_hours'] ?? 0, 0) }}</td></tr>
        <tr><th>Total annual pay cost</th><td>${{ number_format($r['total_annual_pay_cost'] ?? 0, 2) }}</td></tr>
        <tr><th>Total annual bill revenue</th><td>${{ number_format($r['total_annual_bill_revenue'] ?? 0, 2) }}</td></tr>
        <tr><th>Gross margin</th><td>${{ number_format($r['gross_margin'] ?? 0, 2) }}</td></tr>
        <tr><th>Margin %</th><td>{{ number_format($r['margin_percent'] ?? 0, 1) }}%</td></tr>
    </table>

    <p class="footer">GASQ Security Calculator – Contract Analysis Report</p>
</body>
</html>
