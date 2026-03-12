<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Mobile Patrol Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; padding: 24px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 20px; font-size: 10px; }
        table { width: 100%; max-width: 320px; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { font-weight: bold; }
        .footer { margin-top: 32px; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    <h1>GASQ Mobile Patrol Calculator</h1>
    <p class="meta">Report generated {{ $generatedAt ?? now()->format('M j, Y g:i A') }}</p>

    <h2 style="font-size: 14px; margin-top: 16px;">Result</h2>
    @php $r = $result ?? []; @endphp
    <table>
        <tr><th>Monthly cost</th><td>${{ number_format($r['monthly_cost'] ?? 0, 2) }}</td></tr>
        <tr><th>Annual cost</th><td>${{ number_format($r['annual_cost'] ?? 0, 2) }}</td></tr>
    </table>

    <p class="footer">GASQ Security Calculator – Mobile Patrol Report</p>
</body>
</html>
