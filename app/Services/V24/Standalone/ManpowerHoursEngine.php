<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class ManpowerHoursEngine
{
    private const SHIFT_MULTIPLIERS = [
        '8-hour' => 3.0,
        '10-hour' => 2.4,
        '12-hour' => 2.0,
        '16-hour' => 1.5,
        '24-hour' => 1.0,
    ];

    /**
     * Mirrors `resources/views/calculators/manpower-hours.blade.php` demo math.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);
        $coverage = (float) Arr::get($m, 'coverageHoursPerDay', 24);
        $shift = (string) Arr::get($m, 'shiftPattern', '8-hour');
        $factor = (float) Arr::get($m, 'schedulingFactor', 1.4);
        $maxHrs = (float) Arr::get($m, 'maxHoursPerGuardPerWeek', 40);

        $mult = self::SHIFT_MULTIPLIERS[$shift] ?? 3.0;
        $requiredDaily = $coverage * $mult * $factor;
        $weekly = $requiredDaily * 7;
        $monthly = $requiredDaily * 30;
        $annual = $requiredDaily * 365;
        $guards = $maxHrs > 0 ? (int) ceil($weekly / $maxHrs) : 0;

        $matrix = [];
        foreach (self::SHIFT_MULTIPLIERS as $k => $mVal) {
            $daily = $coverage * $mVal * $factor;
            $wk = $daily * 7;
            $matrix[] = [
                'shiftPattern' => $k,
                'multiplier' => round($mVal, 1),
                'weeklyHours' => round($wk, 1),
                'guardsNeeded' => $maxHrs > 0 ? (int) ceil($wk / $maxHrs) : 0,
            ];
        }

        return [
            'dailyHours' => round($requiredDaily, 1),
            'weeklyHours' => round($weekly, 1),
            'monthlyHours' => round($monthly, 1),
            'annualHours' => round($annual, 1),
            'guardsRequired' => $guards,
            'details' => [
                'coverageHoursPerDay' => $coverage,
                'shiftMultiplier' => round($mult, 1),
                'schedulingFactor' => round($factor, 2),
                'requiredDailyLaborHours' => round($requiredDaily, 1),
                'requiredWeeklyLaborHours' => round($weekly, 1),
                'maxHoursPerGuardPerWeek' => $maxHrs,
            ],
            'matrix' => $matrix,
        ];
    }
}

