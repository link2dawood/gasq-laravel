<?php

namespace App\Services;

class SecurityBillingService
{
    public function calculate(float $hourlyRate, float $hoursPerWeek, int $weeks = 52): array
    {
        $weeklyTotal = $hourlyRate * $hoursPerWeek;
        $monthlyTotal = $weeklyTotal * 4.33;
        $annualTotal = $weeklyTotal * $weeks;

        return [
            'hourly_rate' => $hourlyRate,
            'weekly_hours' => $hoursPerWeek,
            'weekly_total' => round($weeklyTotal, 2),
            'monthly_total' => round($monthlyTotal, 2),
            'annual_total' => round($annualTotal, 2),
        ];
    }
}
