<?php

namespace App\Services;

class MainMenuCalculatorService
{
    protected static array $livingWageByLocation = [
        'california' => 19.41,
        'new-york' => 17.87,
        'texas' => 14.53,
        'florida' => 15.45,
        'illinois' => 16.20,
    ];

    public function securityCost(string $location, float $hoursPerWeek, float $guards): array
    {
        $livingWage = self::$livingWageByLocation[strtolower($location)] ?? 15.00;
        $hourlyRate = $livingWage * 1.3;
        $weekly = $hoursPerWeek * max(1, $guards) * $hourlyRate;
        return [
            'hourly_rate' => round($hourlyRate, 2),
            'weekly_total' => round($weekly, 2),
            'monthly_total' => round($weekly * 4.33, 2),
            'annual_total' => round($weekly * 52, 2),
        ];
    }

    public function manpowerHours(float $siteCoverage, string $shiftPattern, float $schedulingFactor): array
    {
        $multiplier = 1;
        if ($shiftPattern === '8-hour') $multiplier = 3;
        elseif ($shiftPattern === '10-hour') $multiplier = 2.4;
        elseif ($shiftPattern === '12-hour') $multiplier = 2;
        elseif ($shiftPattern === '16-hour') $multiplier = 1.5;
        elseif ($shiftPattern === '24-hour') $multiplier = 1;
        $requiredHours = $siteCoverage * $multiplier * max(0.1, $schedulingFactor);
        return [
            'weekly_hours' => round($requiredHours * 7, 2),
            'monthly_hours' => round($requiredHours * 30, 2),
            'annual_hours' => round($requiredHours * 365, 2),
        ];
    }

    public function economicJustification(float $employeeHourlyCost, float $vendorHourlyCost, float $weeklyHours, int $weeksInYear = 52): array
    {
        $inhouseWeekly = $employeeHourlyCost * $weeklyHours;
        $vendorWeekly = $vendorHourlyCost * $weeklyHours;
        $inhouseAnnual = $inhouseWeekly * $weeksInYear;
        $vendorAnnual = $vendorWeekly * $weeksInYear;
        $savings = $inhouseAnnual - $vendorAnnual;
        $roiPercent = $inhouseAnnual > 0 ? ($savings / $inhouseAnnual) * 100 : 0;
        $paybackMonths = $savings > 0 && $inhouseWeekly > 0 ? ($vendorAnnual / $inhouseWeekly) * (52 / 12) : 0;

        return [
            'inhouse_weekly_cost' => round($inhouseWeekly, 2),
            'vendor_weekly_cost' => round($vendorWeekly, 2),
            'inhouse_annual_cost' => round($inhouseAnnual, 2),
            'vendor_annual_cost' => round($vendorAnnual, 2),
            'cost_savings' => round($savings, 2),
            'roi_percentage' => round($roiPercent, 1),
            'payback_months' => round($paybackMonths, 1),
        ];
    }

    public function billRate(float $basePay, float $overheadPercent, float $profitPercent): array
    {
        $costWithBenefits = $basePay > 0 ? $basePay / 0.70 : 0;
        $finalBillRate = $costWithBenefits * (1 + $profitPercent / 100);
        $markup = $basePay > 0 ? (($finalBillRate - $basePay) / $basePay) * 100 : 0;
        return [
            'cost_with_benefits' => round($costWithBenefits, 2),
            'final_bill_rate' => round($finalBillRate, 2),
            'markup_percent' => round($markup, 1),
        ];
    }
}
