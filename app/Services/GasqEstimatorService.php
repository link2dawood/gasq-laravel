<?php

namespace App\Services;

class GasqEstimatorService
{
    protected static array $livingWageByLocation = [
        'california' => 19.41,
        'new-york' => 17.87,
        'texas' => 14.53,
        'florida' => 15.45,
        'illinois' => 16.20,
    ];

    public function estimate(string $location, float $hoursPerWeek, float $numberOfGuards): array
    {
        $livingWage = self::$livingWageByLocation[strtolower($location)] ?? 15.00;
        $hours = max(0, $hoursPerWeek);
        $guards = max(1, $numberOfGuards);
        $hourlyRate = $livingWage * 1.3;
        $weeklyTotal = $hours * $guards * $hourlyRate;
        $monthlyTotal = $weeklyTotal * 4.33;
        $annualTotal = $weeklyTotal * 52;

        return [
            'hourly_rate' => round($hourlyRate, 2),
            'weekly_total' => round($weeklyTotal, 2),
            'monthly_total' => round($monthlyTotal, 2),
            'annual_total' => round($annualTotal, 2),
            'living_wage' => $livingWage,
        ];
    }

    public function getLocations(): array
    {
        return array_keys(self::$livingWageByLocation);
    }
}
