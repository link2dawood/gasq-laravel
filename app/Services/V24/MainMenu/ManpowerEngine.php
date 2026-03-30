<?php

namespace App\Services\V24\MainMenu;

use Illuminate\Support\Arr;

class ManpowerEngine
{
    private const SHIFT_MUL = [
        '8-hour' => 3.0,
        '10-hour' => 2.4,
        '12-hour' => 2.0,
        '16-hour' => 1.5,
        '24-hour' => 1.0,
    ];

    /**
     * @param  array<string, mixed>  $scenario
     * @param  array<string, mixed>  $derivedCoverage
     * @return array<string, mixed>
     */
    public function compute(array $scenario, array $derivedCoverage): array
    {
        $meta = (array) ($scenario['meta'] ?? []);
        $coverage = (float) (Arr::get($meta, 'siteCoverageHoursPerDay') ?? Arr::get($meta, 'siteCoverage') ?? 24);
        $shift = (string) (Arr::get($meta, 'shiftPattern') ?? '8-hour');
        $factor = (float) (Arr::get($meta, 'schedulingFactor') ?? 1.4);

        $shiftMul = self::SHIFT_MUL[$shift] ?? 3.0;
        $requiredHoursPerDay = $coverage * $shiftMul * max(0.1, $factor);

        $weekly = $requiredHoursPerDay * 7;
        $monthly = $requiredHoursPerDay * 30;
        $annual = $requiredHoursPerDay * 365;
        $guards = (int) ceil($weekly / 28);

        return [
            'weeklyHours' => round($weekly, 1),
            'monthlyHours' => round($monthly, 1),
            'annualHours' => round($annual, 1),
            'estimatedGuardsPartTime28hr' => $guards,
            'shiftMultiplierUsed' => round($shiftMul, 1),
            'coverageDerived' => $derivedCoverage,
        ];
    }
}

