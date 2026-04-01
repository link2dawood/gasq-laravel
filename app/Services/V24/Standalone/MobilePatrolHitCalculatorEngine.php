<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class MobilePatrolHitCalculatorEngine
{
    /**
     * A lightweight single-scenario “per hit/stop” calculator.
     *
     * Inputs live under scenario.meta.* and default to a reasonable patrol demo.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);

        $daysPerYear = max(1.0, (float) Arr::get($m, 'daysPerYear', 365));
        $hoursPerDay = max(0.0, (float) Arr::get($m, 'hoursPerDay', 24));
        $hitsPerDay = max(0.0, (float) Arr::get($m, 'hitsPerDay', 180));

        $milesPerDay = max(0.0, (float) Arr::get($m, 'milesPerDay', 360));
        $costPerMile = max(0.0, (float) Arr::get($m, 'costPerMile', 0.67));
        $equipmentPerDay = max(0.0, (float) Arr::get($m, 'equipmentPerDay', 0));

        $regularHoursPerDay = max(0.0, (float) Arr::get($m, 'regularHoursPerDay', $hoursPerDay));
        $overtimeHoursPerDay = max(0.0, (float) Arr::get($m, 'overtimeHoursPerDay', 0));

        $regularHourlyUsd = max(0.0, (float) Arr::get($m, 'regularHourlyUsd', 30.00));
        $overtimeHourlyUsd = max(0.0, (float) Arr::get($m, 'overtimeHourlyUsd', 45.00));

        $markupPct = max(0.0, (float) Arr::get($m, 'markupPct', 27.0)) / 100.0;

        $mileageCostPerDay = $milesPerDay * $costPerMile;
        $operatingCostPerDay = $mileageCostPerDay + $equipmentPerDay;
        $laborCostPerDay = $regularHoursPerDay * $regularHourlyUsd + $overtimeHoursPerDay * $overtimeHourlyUsd;
        $totalCostPerDay = $operatingCostPerDay + $laborCostPerDay;

        $costPerHit = $hitsPerDay > 0 ? $totalCostPerDay / $hitsPerDay : 0.0;
        $billRatePerHour = $hoursPerDay > 0 ? ($totalCostPerDay / $hoursPerDay) * (1 + $markupPct) : 0.0;
        $billPerDay = $totalCostPerDay * (1 + $markupPct);
        $billPerHit = $hitsPerDay > 0 ? $billPerDay / $hitsPerDay : 0.0;

        $annual = fn (float $perDay) => $perDay * $daysPerYear;

        return [
            'inputs' => [
                'daysPerYear' => round($daysPerYear, 0),
                'hoursPerDay' => round($hoursPerDay, 2),
                'hitsPerDay' => round($hitsPerDay, 2),
                'milesPerDay' => round($milesPerDay, 2),
                'costPerMile' => round($costPerMile, 4),
                'equipmentPerDay' => round($equipmentPerDay, 2),
                'regularHoursPerDay' => round($regularHoursPerDay, 2),
                'overtimeHoursPerDay' => round($overtimeHoursPerDay, 2),
                'regularHourlyUsd' => round($regularHourlyUsd, 2),
                'overtimeHourlyUsd' => round($overtimeHourlyUsd, 2),
                'markupPct' => round($markupPct * 100.0, 2),
            ],
            'daily' => [
                'mileageCost' => round($mileageCostPerDay, 2),
                'operatingCost' => round($operatingCostPerDay, 2),
                'laborCost' => round($laborCostPerDay, 2),
                'totalCost' => round($totalCostPerDay, 2),
                'costPerHit' => round($costPerHit, 4),
                'billPerDay' => round($billPerDay, 2),
                'billPerHit' => round($billPerHit, 4),
                'billRatePerHour' => round($billRatePerHour, 2),
            ],
            'annual' => [
                'operatingCost' => round($annual($operatingCostPerDay), 2),
                'laborCost' => round($annual($laborCostPerDay), 2),
                'totalCost' => round($annual($totalCostPerDay), 2),
                'billTotal' => round($annual($billPerDay), 2),
                'totalHits' => (int) round($hitsPerDay * $daysPerYear),
            ],
            'reference' => 'standalone:mobile-patrol-hit-calculator',
        ];
    }
}

