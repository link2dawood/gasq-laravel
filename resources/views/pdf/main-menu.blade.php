<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Main Menu Calculator Report</title>
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
    <h1>GASQ Main Menu Calculator</h1>
    <p class="meta">Report generated {{ isset($generatedAt) ? $generatedAt : now()->format('M j, Y g:i A') }}</p>

    <h2 style="font-size: 14px; margin-top: 16px;">Result</h2>
    <table>
        @php $res = $result ?? []; @endphp
        @foreach($res as $key => $val)
        <tr>
            <th>{{ str_replace('_', ' ', ucfirst($key)) }}</th>
            <td>@if(is_numeric($val) && str_contains((string)$val, '.')){{ number_format((float)$val, 2) }}@else{{ $val }}@endif</td>
        </tr>
        @endforeach
    </table>

    <p class="footer">GASQ Security Calculator – Main Menu Report</p>
</body>
</html>
