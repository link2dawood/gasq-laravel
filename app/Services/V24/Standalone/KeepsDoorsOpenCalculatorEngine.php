<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class KeepsDoorsOpenCalculatorEngine
{
    /**
     * Access-control staffing model (simplified):
     * weeklyHours = hoursPerDay * daysPerWeek * guards
     * annualHours = weeklyHours * weeksPerYear
     * annualCost = annualHours * billRateHourly
     * costPerDay = annualCost / (daysPerWeek * weeksPerYear)
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);

        $guards = max(0, (int) Arr::get($m, 'guards', 2));
        $hoursPerDay = max(0.0, (float) Arr::get($m, 'hoursPerDay', 8));
        $daysPerWeek = max(0.0, (float) Arr::get($m, 'daysPerWeek', 5));
        $weeksPerYear = max(1.0, (float) Arr::get($m, 'weeksPerYear', 52));
        $billRateHourly = max(0.0, (float) Arr::get($m, 'billRateHourly', 30.90));

        $weeklyHours = $hoursPerDay * $daysPerWeek * $guards;
        $annualHours = $weeklyHours * $weeksPerYear;
        $annualCost = $annualHours * $billRateHourly;
        $daysPerYear = max(1.0, $daysPerWeek * $weeksPerYear);
        $costPerDay = $annualCost / $daysPerYear;

        return [
            'inputs' => [
                'guards' => $guards,
                'hoursPerDay' => round($hoursPerDay, 2),
                'daysPerWeek' => round($daysPerWeek, 2),
                'weeksPerYear' => round($weeksPerYear, 2),
                'billRateHourly' => round($billRateHourly, 2),
            ],
            'kpis' => [
                'weeklyHours' => round($weeklyHours, 2),
                'annualHours' => round($annualHours, 2),
                'annualCost' => round($annualCost, 2),
                'costPerDay' => round($costPerDay, 2),
            ],
            'reference' => 'standalone:keeps-doors-open-calculator',
        ];
    }
}

