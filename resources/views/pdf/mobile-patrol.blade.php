<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GASQ Mobile Patrol Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; padding: 24px; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 20px 0 8px; }
        .meta { color: #666; margin-bottom: 16px; font-size: 10px; }
        .intro { margin-bottom: 16px; color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f7f7f8; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .section { margin-top: 18px; }
        .summary-row td { font-weight: bold; }
        .footer { margin-top: 28px; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    @php
        $scenarioMeta = (array) (($scenario['meta'] ?? []));
        $kpis = (array) (($result['kpis'] ?? $result ?? []));

        $money = static fn ($value) => '$' . number_format((float) ($value ?? 0), 2);
        $number = static fn ($value, $digits = 2) => number_format((float) ($value ?? 0), $digits);
    @endphp

    <h1>GASQ Mobile Patrol Calculator</h1>
    <p class="meta">Report generated {{ $generatedAt ?? now()->format('M j, Y g:i A') }}</p>
    <p class="intro">
        Built from the mobile patrol formula using pay rate, divisor, annual hours, mileage assumptions,
        fuel costs, maintenance inputs, and return on sales.
    </p>

    <div class="section">
        <h2>Input Summary</h2>
        <table>
            <tr><th>Field</th><th>Value</th></tr>
            <tr><td>Baseline hourly pay rate</td><td>{{ $money($scenarioMeta['baselinePayRate'] ?? 0) }}</td></tr>
            <tr><td>Divisor</td><td>{{ $number($scenarioMeta['divisor'] ?? 0, 2) }}</td></tr>
            <tr><td>Annual hours</td><td>{{ $number($scenarioMeta['annualHours'] ?? 0, 0) }}</td></tr>
            <tr><td>Driving speed (MPH)</td><td>{{ $number($scenarioMeta['mph'] ?? 0, 0) }}</td></tr>
            <tr><td>Hours per day</td><td>{{ $number($scenarioMeta['hoursPerDay'] ?? 0, 0) }}</td></tr>
            <tr><td>Miles per gallon</td><td>{{ $number($scenarioMeta['mpg'] ?? 0, 0) }}</td></tr>
            <tr><td>Fuel cost per gallon</td><td>{{ $money($scenarioMeta['fuelCostPerGallon'] ?? 0) }}</td></tr>
            <tr><td>Annual maintenance / repair</td><td>{{ $money($scenarioMeta['annualMaintenance'] ?? 0) }}</td></tr>
            <tr><td>Tire sets per year</td><td>{{ $number($scenarioMeta['tireSetsPerYear'] ?? 0, 0) }}</td></tr>
            <tr><td>Tire cost per set</td><td>{{ $money($scenarioMeta['tireCostPerSet'] ?? 0) }}</td></tr>
            <tr><td>Auto insurance</td><td>{{ $money($scenarioMeta['autoInsurance'] ?? 0) }}</td></tr>
            <tr><td>Oil change interval (miles)</td><td>{{ $number($scenarioMeta['oilChangeIntervalMiles'] ?? 0, 0) }}</td></tr>
            <tr><td>Oil change cost</td><td>{{ $money($scenarioMeta['oilChangeCost'] ?? 0) }}</td></tr>
            <tr><td>Return on sales %</td><td>{{ $number($scenarioMeta['returnOnSalesPct'] ?? 0, 2) }}%</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Calculated Results</h2>
        <table>
            <tr><th>Metric</th><th>Value</th></tr>
            <tr><td>Employer cost per hour</td><td>{{ $money($kpis['employerCostHourly'] ?? 0) }}</td></tr>
            <tr><td>Annual labor cost</td><td>{{ $money($kpis['annualLaborCost'] ?? 0) }}</td></tr>
            <tr><td>Miles per day</td><td>{{ $number($kpis['milesPerDay'] ?? 0, 0) }}</td></tr>
            <tr><td>Miles per year</td><td>{{ $number($kpis['milesPerYear'] ?? 0, 0) }}</td></tr>
            <tr><td>Gallons per year</td><td>{{ $number($kpis['gallonsPerYear'] ?? 0, 0) }}</td></tr>
            <tr><td>Annual fuel cost</td><td>{{ $money($kpis['annualFuelCost'] ?? 0) }}</td></tr>
            <tr><td>Annual maintenance / repair</td><td>{{ $money($scenarioMeta['annualMaintenance'] ?? 0) }}</td></tr>
            <tr><td>Annual tire cost</td><td>{{ $money($kpis['annualTireCost'] ?? 0) }}</td></tr>
            <tr><td>Auto insurance</td><td>{{ $money($scenarioMeta['autoInsurance'] ?? 0) }}</td></tr>
            <tr><td>Oil changes per year</td><td>{{ $number($kpis['oilChangesPerYear'] ?? 0, 0) }} ({{ $money($kpis['annualOilCost'] ?? 0) }})</td></tr>
            <tr class="summary-row"><td>Total annual cost</td><td>{{ $money($kpis['totalAnnualCost'] ?? 0) }}</td></tr>
            <tr><td>Cost per hour</td><td>{{ $money($kpis['costPerHour'] ?? 0) }}</td></tr>
            <tr class="summary-row"><td>Hourly bill rate</td><td>{{ $money($kpis['hourlyBillableRate'] ?? 0) }}</td></tr>
        </table>
    </div>

    <p class="footer">GASQ Security Calculator – Mobile Patrol Formula Report</p>
</body>
</html>
