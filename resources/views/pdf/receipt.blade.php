<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; padding: 24px; }
        h1 { font-size: 18px; margin-bottom: 8px; }
        .meta { color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { font-weight: bold; }
        .footer { margin-top: 32px; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    <h1>GASQ – Credit Purchase Receipt</h1>
    <p class="meta">Generated {{ isset($generatedAt) ? $generatedAt : now()->format('M j, Y g:i A') }}</p>

    <p><strong>Customer:</strong> {{ $user->name }}<br>
    <strong>Email:</strong> {{ $user->email }}</p>

    <table>
        <tr><th>Date</th><td>{{ $transaction->created_at->format('M j, Y g:i A') }}</td></tr>
        <tr><th>Description</th><td>{{ $transaction->description ?: 'Credit purchase' }}</td></tr>
        <tr><th>Credits added</th><td>+{{ number_format($transaction->tokens_change) }}</td></tr>
        <tr><th>Balance after</th><td>{{ number_format($transaction->balance_after ?? 0) }} credits</td></tr>
    </table>

    <p class="footer">Thank you for using GASQ. This is your receipt for records.</p>
</body>
</html>
