<?php

namespace App\Services;

class MobilePatrolService
{
    /**
     * Single scenario: cost per visit × visits per month × 12 + base.
     */
    public function scenarioCost(float $costPerVisit, float $visitsPerMonth, float $monthlyBase = 0): array
    {
        $monthly = $costPerVisit * $visitsPerMonth + $monthlyBase;
        $annual = $monthly * 12;
        return [
            'cost_per_visit' => $costPerVisit,
            'visits_per_month' => $visitsPerMonth,
            'monthly_cost' => round($monthly, 2),
            'annual_cost' => round($annual, 2),
        ];
    }

    /**
     * Compare two scenarios.
     */
    public function compare(array $scenarioA, array $scenarioB): array
    {
        $annualA = $scenarioA['annual_cost'] ?? 0;
        $annualB = $scenarioB['annual_cost'] ?? 0;
        $savings = $annualA - $annualB;
        $savingsPercent = $annualA > 0 ? ($savings / $annualA) * 100 : 0;

        return [
            'scenario_a_annual' => $annualA,
            'scenario_b_annual' => $annualB,
            'savings' => round($savings, 2),
            'savings_percent' => round($savingsPercent, 1),
        ];
    }
}
