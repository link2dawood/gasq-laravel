<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class UnarmedSecurityGuardServicesEngine
{
    /**
     * Package model for unarmed guard services.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);

        $guards = max(0, (int) Arr::get($m, 'guards', 1));
        $hoursPerWeekPerGuard = max(0.0, (float) Arr::get($m, 'hoursPerWeekPerGuard', 40));
        $billRateHourly = max(0.0, (float) Arr::get($m, 'billRateHourly', 30.63));
        $weeksPerMonth = max(0.0, (float) Arr::get($m, 'weeksPerMonth', 4.3333));

        $weeklyHours = $guards * $hoursPerWeekPerGuard;
        $weeklyTotal = $weeklyHours * $billRateHourly;
        $monthlyTotal = $weeklyTotal * $weeksPerMonth;
        $annualTotal = $weeklyTotal * 52.0;

        return [
            'inputs' => [
                'guards' => $guards,
                'hoursPerWeekPerGuard' => round($hoursPerWeekPerGuard, 2),
                'billRateHourly' => round($billRateHourly, 2),
                'weeksPerMonth' => round($weeksPerMonth, 4),
            ],
            'kpis' => [
                'weeklyHours' => round($weeklyHours, 2),
                'weeklyTotal' => round($weeklyTotal, 2),
                'monthlyTotal' => round($monthlyTotal, 2),
                'annualTotal' => round($annualTotal, 2),
            ],
            'reference' => 'standalone:unarmed-security-guard-services',
        ];
    }
}

