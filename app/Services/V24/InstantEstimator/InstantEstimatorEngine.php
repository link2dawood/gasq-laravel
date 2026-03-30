<?php

namespace App\Services\V24\InstantEstimator;

use Illuminate\Support\Arr;

class InstantEstimatorEngine
{
    private const LIVING_WAGE = [
        'california' => 19.41,
        'new-york' => 17.87,
        'texas' => 14.53,
        'florida' => 15.45,
        'illinois' => 16.20,
        'georgia' => 13.98,
        'north-carolina' => 13.50,
        'arizona' => 14.25,
        'colorado' => 16.80,
        'washington' => 18.90,
        'pennsylvania' => 14.40,
        'new-jersey' => 17.10,
        'ohio' => 13.25,
        'michigan' => 14.10,
    ];

    private const SERVICE_MULT = [
        'unarmed' => 1.0,
        'armed' => 1.25,
        'patrol' => 1.15,
    ];

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        // NOTE: Mirrors current Instant Estimator UI behavior. Replace with real V24 formulas once mapped.
        $meta = (array) ($scenario['meta'] ?? []);
        $posts = (array) ($scenario['posts'] ?? []);

        $location = strtolower((string) (Arr::get($meta, 'locationState') ?? 'california'));
        $serviceType = (string) (Arr::get($meta, 'serviceType') ?? 'unarmed');
        $mult = self::SERVICE_MULT[$serviceType] ?? 1.0;

        $livingWage = self::LIVING_WAGE[$location] ?? 15.00;
        $withOverhead = $livingWage * 1.3;

        $hours = 0.0;
        $guards = 0.0;
        foreach ($posts as $p) {
            $hours += (float) (Arr::get($p, 'weeklyHours') ?? 0);
            $guards += (float) (Arr::get($p, 'qtyRequired') ?? 1);
            break; // estimator models a single row
        }
        $hours = max(0.0, $hours);
        $guards = max(1.0, $guards);

        $hourlyRate = $withOverhead * $mult;
        $weekly = $hourlyRate * $hours * $guards;
        $monthly = $weekly * 4.333;
        $annual = $weekly * 52;

        return [
            'livingWageBase' => round($livingWage, 2),
            'withOverheadHourly' => round($withOverhead, 2),
            'serviceMultiplier' => round($mult, 2),
            'estimatedHourlyRate' => round($hourlyRate, 2),
            'estimatedWeeklyTotal' => round($weekly, 2),
            'estimatedMonthlyTotal' => round($monthly, 2),
            'estimatedAnnualTotal' => round($annual, 2),
        ];
    }
}

