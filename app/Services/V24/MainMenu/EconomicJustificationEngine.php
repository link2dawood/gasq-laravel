<?php

namespace App\Services\V24\MainMenu;

use App\Services\BudgetSummaryService;
use Illuminate\Support\Arr;

class EconomicJustificationEngine
{
    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);

        if (($meta['roiCostBasis'] ?? null) === 'budget') {
            return app(BudgetSummaryService::class)->fromScenario((array) ($meta['budgetScenario'] ?? []));
        }

        $employeeCost = (float) (Arr::get($meta, 'employeeTrueHourlyCost') ?? 133.0);
        $weeklyHours = (float) (Arr::get($meta, 'weeklyHoursPerformed') ?? 168.0);
        $weeksInYear = (float) (Arr::get($meta, 'weeksInYear') ?? 52.0);
        $monthsInYear = (float) (Arr::get($meta, 'monthsInYear') ?? 12.0);

        // Mirrors current demo: vendor hourly cost assumed 70% of in-house.
        $vendorHourly = (float) (Arr::get($meta, 'vendorTrueHourlyCost') ?? ($employeeCost * 0.70));
        $weeksPerMonth = $monthsInYear > 0 ? $weeksInYear / $monthsInYear : 4.333;

        $ihWeekly = $employeeCost * $weeklyHours;
        $vWeekly = $vendorHourly * $weeklyHours;
        $ihMonthly = $ihWeekly * $weeksPerMonth;
        $vMonthly = $vWeekly * $weeksPerMonth;
        $ihAnnual = $ihWeekly * $weeksInYear;
        $vAnnual = $vWeekly * $weeksInYear;
        $savings = $ihAnnual - $vAnnual;
        $roiPct = $ihAnnual > 0 ? ($savings / $ihAnnual) * 100 : 0;
        $payback = $savings > 0 ? $vAnnual / ($ihMonthly > 0 ? $ihMonthly : 1) : 0;
        $dollar = $vAnnual > 0 ? $savings / $vAnnual : 0;

        return [
            'totalWeeklyHours' => round($weeklyHours, 2),
            'totalMonthlyHours' => round($weeklyHours * $weeksPerMonth, 2),
            'totalAnnualHours' => round($weeklyHours * $weeksInYear, 2),
            'totalStaffRequired' => (int) ceil($weeklyHours / 28),
            // Retain monetary precision until presentation in the selected currency.
            'inHouseHourly' => $employeeCost,
            'vendorHourly' => $vendorHourly,
            'inHouseWeekly' => $ihWeekly,
            'vendorWeekly' => $vWeekly,
            'inHouseMonthly' => $ihMonthly,
            'vendorMonthly' => $vMonthly,
            'inHouseAnnual' => $ihAnnual,
            'vendorAnnual' => $vAnnual,
            'costSavings' => $savings,
            'roiPct' => round($roiPct, 1),
            'paybackMonths' => round($payback, 1),
            'dollarForDollarReturn' => round($dollar, 2),
        ];
    }
}
