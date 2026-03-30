<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class EconomicJustificationEngine
{
    /**
     * Mirrors the demo math in `resources/views/calculators/economic-justification.blade.php`.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);
        $employeeCost = (float) Arr::get($m, 'employeeTrueHourlyCost', 133.0);
        $weeklyHours = (float) Arr::get($m, 'weeklyHours', 168.0);
        $weeksInYear = (float) Arr::get($m, 'weeksInYear', 52.0);
        $monthsInYear = (float) Arr::get($m, 'monthsInYear', 12.0);

        $vendorHourly = (float) Arr::get($m, 'vendorHourlyCost', $employeeCost * 0.70);
        $weeksPerMonth = $monthsInYear > 0 ? $weeksInYear / $monthsInYear : 4.333;

        $ihWeekly = $employeeCost * $weeklyHours;
        $vWeekly = $vendorHourly * $weeklyHours;
        $ihMonthly = $ihWeekly * $weeksPerMonth;
        $vMonthly = $vWeekly * $weeksPerMonth;
        $ihAnnual = $ihWeekly * $weeksInYear;
        $vAnnual = $vWeekly * $weeksInYear;
        $savings = $ihAnnual - $vAnnual;
        $roiPct = $ihAnnual > 0 ? ($savings / $ihAnnual) * 100 : 0;
        $payback = $savings > 0 ? $vAnnual / ($ihMonthly > 0 ? $ihMonthly : 1) : 0;
        $dollar = $vAnnual > 0 ? $savings / $vAnnual : 0;

        return [
            'inHouseWeekly' => round($ihWeekly, 2),
            'inHouseMonthly' => round($ihMonthly, 2),
            'inHouseAnnual' => round($ihAnnual, 2),
            'vendorHourly' => round($vendorHourly, 2),
            'vendorWeekly' => round($vWeekly, 2),
            'vendorMonthly' => round($vMonthly, 2),
            'vendorAnnual' => round($vAnnual, 2),
            'savings' => round($savings, 2),
            'roiPct' => round($roiPct, 1),
            'paybackMonths' => round($payback, 1),
            'dollarForDollar' => round($dollar, 2),
        ];
    }
}

