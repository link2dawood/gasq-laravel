<?php

namespace App\Services;

class ContractAnalysisService
{
    /**
     * Simple contract analysis: total hours × rates.
     *
     * @param array<int, array{weekly_hours: float, pay_rate: float, bill_rate: float}> $categories
     */
    public function analyze(array $categories): array
    {
        $totalWeeklyHours = 0;
        $totalAnnualHours = 0;
        $totalPayCost = 0;
        $totalBillRevenue = 0;

        foreach ($categories as $cat) {
            $weekly = (float) ($cat['weekly_hours'] ?? 0);
            $payRate = (float) ($cat['pay_rate'] ?? 0);
            $billRate = (float) ($cat['bill_rate'] ?? 0);
            $annualHours = $weekly * 52;
            $totalWeeklyHours += $weekly;
            $totalAnnualHours += $annualHours;
            $totalPayCost += $weekly * $payRate * 52;
            $totalBillRevenue += $weekly * $billRate * 52;
        }

        $grossMargin = $totalBillRevenue - $totalPayCost;
        $marginPercent = $totalBillRevenue > 0 ? ($grossMargin / $totalBillRevenue) * 100 : 0;

        return [
            'total_weekly_hours' => round($totalWeeklyHours, 2),
            'total_annual_hours' => round($totalAnnualHours, 2),
            'total_annual_pay_cost' => round($totalPayCost, 2),
            'total_annual_bill_revenue' => round($totalBillRevenue, 2),
            'gross_margin' => round($grossMargin, 2),
            'margin_percent' => round($marginPercent, 1),
        ];
    }
}
