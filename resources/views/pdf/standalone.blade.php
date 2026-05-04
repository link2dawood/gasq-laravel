@php
    $type = $reportType ?? ($type ?? 'calculator');
    $scenario = $scenario ?? [];
    $result = $result ?? [];
    $kpis = $result['kpis'] ?? $result ?? [];

    $title = match ($type) {
        'bill-rate-analysis' => 'Bill Rate Analysis',
        'economic-justification' => 'Economic Justification',
        'budget-calculator' => 'Workforce Calculator',
        'government-contract-calculator' => 'Government Contract Calculator',
        'gasq-tco-calculator' => 'GASQ TCO Calculator',
        'mobile-patrol-hit-calculator' => 'Mobile Patrol Hit Calculator',
        'mobile-patrol-analysis' => 'Mobile Patrol Analysis',
        'workforce-appraisal-report' => 'Workforce Appraisal Report',
        'buyer-fit-index' => 'Buyer Fit Index',
        'gasq-direct-labor-build-up' => 'GASQ Direct Labor Build-Up',
        'gasq-additional-cost-stack' => 'GASQ Additional Cost Stack',
        default => 'GASQ Calculator Report',
    };

    $flatten = function (array $arr, string $prefix = '') use (&$flatten) {
        $out = [];
        foreach ($arr as $k => $v) {
            $key = $prefix === '' ? (string) $k : ($prefix . '.' . $k);
            if (is_array($v)) {
                $out += $flatten($v, $key);
            } else {
                $out[$key] = $v;
            }
        }
        return $out;
    };

    $displayKey = function (string $key): string {
        foreach (['meta.inputs.', 'inputs.', 'meta.'] as $prefix) {
            if (str_starts_with($key, $prefix)) {
                $key = substr($key, strlen($prefix));
                break;
            }
        }

        $segments = array_filter(explode('.', $key), fn ($segment) => $segment !== '');

        return implode(' / ', array_map(function (string $segment): string {
            if (ctype_digit($segment)) {
                return 'Item ' . ((int) $segment + 1);
            }

            $label = \Illuminate\Support\Str::headline($segment);
            $label = preg_replace('/\bPct\b/', 'Percent', $label);
            $label = preg_replace(
                [
                    '/\bFte\b/',
                    '/\bOt\b/',
                    '/\bHw\b/',
                    '/\bFica\b/',
                    '/\bFuta\b/',
                    '/\bSuta\b/',
                    '/\bQa\b/',
                    '/\bGa\b/',
                    '/\bTco\b/',
                    '/\bGov\b/',
                    '/\bVs\b/',
                ],
                ['FTE', 'OT', 'HW', 'FICA', 'FUTA', 'SUTA', 'QA', 'G&A', 'TCO', 'Government', 'vs'],
                $label,
            );

            return $label;
        }, $segments));
    };

    $formatValue = function (string $key, mixed $value): string {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null) {
            return 'N/A';
        }

        if (is_numeric($value)) {
            $number = (float) $value;

            if (preg_match('/(?:Pct|Percent)$/', $key) === 1) {
                $percent = number_format($number * 100, 2, '.', ',');

                return rtrim(rtrim($percent, '0'), '.') . '%';
            }

            if (abs($number - round($number)) < 0.00001) {
                return number_format($number, 0, '.', ',');
            }

            return rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
        }

        return is_scalar($value) ? (string) $value : json_encode($value);
    };

    $flatScenario = $flatten((array) $scenario);
    $flatKpis = $flatten((array) $kpis);
@endphp

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .muted { color: #6b7280; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        h2 { font-size: 13px; margin: 18px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        th { background: #f9fafb; text-align: left; }
        td { word-break: break-word; }
        .value { font-family: DejaVu Sans Mono, monospace; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="muted">Generated: {{ $generatedAt ?? '' }}</div>

    <h2>Key results</h2>
    <table>
        <thead><tr><th style="width: 55%">Metric</th><th>Value</th></tr></thead>
        <tbody>
        @forelse($flatKpis as $k => $v)
            <tr>
                <td>{{ $displayKey($k) }}</td>
                <td class="value">{{ $formatValue($k, $v) }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="muted">No KPIs were captured. Run the calculator again and retry.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Inputs (scenario snapshot)</h2>
    <table>
        <thead><tr><th style="width: 55%">Field</th><th>Value</th></tr></thead>
        <tbody>
        @forelse($flatScenario as $k => $v)
            <tr>
                <td>{{ $displayKey($k) }}</td>
                <td class="value">{{ $formatValue($k, $v) }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="muted">No inputs were captured.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
