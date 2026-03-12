<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Instant Estimator Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; padding: 24px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 20px; font-size: 10px; }
        table { width: 100%; max-width: 320px; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { font-weight: bold; width: 50%; }
        .footer { margin-top: 32px; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    <h1>GASQ Instant Estimator</h1>
    <p class="meta">Report generated {{ $generatedAt ?? now()->format('M j, Y g:i A') }}</p>

    <h2 style="font-size: 14px; margin-top: 16px;">Estimate</h2>
    <table>
        <tr><th>Hourly rate (est.)</th><td>${{ number_format($result['hourly_rate'] ?? 0, 2) }}</td></tr>
        <tr><th>Weekly total</th><td>${{ number_format($result['weekly_total'] ?? 0, 2) }}</td></tr>
        <tr><th>Monthly total</th><td>${{ number_format($result['monthly_total'] ?? 0, 2) }}</td></tr>
        <tr><th>Annual total</th><td>${{ number_format($result['annual_total'] ?? 0, 2) }}</td></tr>
    </table>

    <p class="footer">GASQ Security Calculator – Instant Estimator Report</p>
</body>
</html>
