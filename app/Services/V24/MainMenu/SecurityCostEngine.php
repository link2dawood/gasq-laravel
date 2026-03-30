<?php

namespace App\Services\V24\MainMenu;

use Illuminate\Support\Arr;

class SecurityCostEngine
{
    private const LIVING_WAGE = [
        'california' => 19.41,
        'new-york' => 17.87,
        'texas' => 14.53,
        'florida' => 15.45,
        'illinois' => 16.20,
    ];

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        // NOTE: This mirrors the current Main Menu demo behavior.
        // As V24 parity mapping is finalized, replace this with Inputs/Post_Positions driven logic.
        $meta = (array) ($scenario['meta'] ?? []);
        $location = (string) (Arr::get($meta, 'locationState') ?? Arr::get($meta, 'location') ?? 'california');

        $livingWage = self::LIVING_WAGE[strtolower($location)] ?? 15.00;
        $hourlyRate = $livingWage * 1.3;

        $hours = (float) (Arr::get($meta, 'hoursPerWeek') ?? 40);
        $guards = (float) (Arr::get($meta, 'guards') ?? 1);
        $weekly = $hours * max(1, $guards) * $hourlyRate;

        return [
            'hourlyRate' => round($hourlyRate, 2),
            'weeklyTotal' => round($weekly, 2),
            'monthlyTotal' => round($weekly * 4.333, 2),
            'annualTotal' => round($weekly * 52, 2),
            'livingWageBase' => round($livingWage, 2),
            'withOverheadHourly' => round($hourlyRate, 2),
        ];
    }
}

