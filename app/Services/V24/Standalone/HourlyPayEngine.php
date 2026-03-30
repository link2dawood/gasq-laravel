<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class HourlyPayEngine
{
    /**
     * Mirrors the demo math in `resources/views/calculators/hourly-pay.blade.php`.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);
        $rate = (float) Arr::get($m, 'hourlyRate', 18);
        $regHrs = (float) Arr::get($m, 'regularHours', 40);
        $otHrs = (float) Arr::get($m, 'otHours', 0);
        $dtHrs = (float) Arr::get($m, 'doubleTimeHours', 0);

        $fedTaxPct = (float) Arr::get($m, 'fedTaxPct', 12) / 100;
        $stateTaxPct = (float) Arr::get($m, 'stateTaxPct', 5) / 100;
        $ficaPct = (float) Arr::get($m, 'ficaPct', 6.2) / 100;
        $medicarePct = (float) Arr::get($m, 'medicarePct', 1.45) / 100;
        $healthWeekly = (float) Arr::get($m, 'healthWeekly', 0);
        $otherWeekly = (float) Arr::get($m, 'otherWeekly', 0);

        $regPay = $rate * $regHrs;
        $otPay = $rate * 1.5 * $otHrs;
        $dtPay = $rate * 2.0 * $dtHrs;
        $weeklyGross = $regPay + $otPay + $dtPay;
        $totalHrs = $regHrs + $otHrs + $dtHrs;

        $fedTax = $weeklyGross * $fedTaxPct;
        $stateTax = $weeklyGross * $stateTaxPct;
        $ficaAmt = $weeklyGross * $ficaPct;
        $medicareAmt = $weeklyGross * $medicarePct;
        $totalDed = $fedTax + $stateTax + $ficaAmt + $medicareAmt + $healthWeekly + $otherWeekly;
        $netPay = $weeklyGross - $totalDed;
        $effTaxRate = $weeklyGross > 0 ? ($totalDed / $weeklyGross) * 100 : 0.0;
        $effHourly = $totalHrs > 0 ? $netPay / $totalHrs : 0.0;

        return [
            'regPay' => round($regPay, 2),
            'otPay' => round($otPay, 2),
            'dtPay' => round($dtPay, 2),
            'weeklyGross' => round($weeklyGross, 2),
            'fedTax' => round($fedTax, 2),
            'stateTax' => round($stateTax, 2),
            'ficaAmt' => round($ficaAmt, 2),
            'medicareAmt' => round($medicareAmt, 2),
            'otherDeductions' => round($healthWeekly + $otherWeekly, 2),
            'totalDeductions' => round($totalDed, 2),
            'netPay' => round($netPay, 2),
            'biweeklyNetPay' => round($netPay * 2, 2),
            'monthlyNetPay' => round($netPay * 4.333, 2),
            'annualNetPay' => round($netPay * 52, 2),
            'effectiveTaxRatePct' => round($effTaxRate, 1),
            'effectiveNetHourly' => round($effHourly, 2),
        ];
    }
}

