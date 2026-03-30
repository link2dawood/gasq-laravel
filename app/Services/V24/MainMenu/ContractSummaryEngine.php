<?php

namespace App\Services\V24\MainMenu;

use Illuminate\Support\Arr;

class ContractSummaryEngine
{
    /**
     * @param  array<string, mixed>  $scenario
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    public function compute(array $scenario, array $ctx): array
    {
        $meta = (array) ($scenario['meta'] ?? []);

        $coverage = (array) ($ctx['coverage'] ?? []);
        $sec = (array) ($ctx['securityCost'] ?? []);
        $br = (array) ($ctx['billRate'] ?? []);

        // Use derived annual labor hours when present; fall back to demo manpower annual if provided.
        $annualLaborHours = (float) (Arr::get($coverage, 'annualLaborHours') ?? 0);
        $weeklyHours = (float) (Arr::get($coverage, 'totalWeeklyPostHours') ?? 0);

        $billRate = (float) (Arr::get($br, 'finalBillRate') ?? 0);
        $hourlyPay = (float) (Arr::get($sec, 'hourlyRate') ?? 0);

        $weeklyBillings = $weeklyHours * $billRate;
        $annualRevenue = $weeklyBillings * 52;

        $vehPass = (float) (Arr::get($meta, 'vehiclePassthroughBillingsAnnual') ?? Arr::get($meta, 'cs_vehPassthrough') ?? 12000);
        $vehCosts = (float) (Arr::get($meta, 'vehiclePassthroughCostsAnnual') ?? Arr::get($meta, 'cs_vehCosts') ?? 163286);
        $workingCap = (float) (Arr::get($meta, 'workingCapitalRequirement') ?? Arr::get($meta, 'cs_workingCapital') ?? 0);

        $totalRevenue = $annualRevenue + $vehPass;

        $directExpense = $annualLaborHours * $hourlyPay;
        $totalCosts = $directExpense + $vehCosts;

        $profit = $totalRevenue - $totalCosts;
        $profitPct = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0.0;
        $profitPerHr = $annualLaborHours > 0 ? $profit / $annualLaborHours : 0.0;

        $rows = [
            ['label' => 'Weekly Hours', 'value' => round($weeklyHours, 1)],
            ['label' => 'Weekly Billings', 'value' => round($weeklyBillings, 2)],
            ['label' => 'Blended Straight Time Pay Rate', 'value' => round($hourlyPay, 2)],
            ['label' => 'Blended Straight Time Bill Rate', 'value' => round($billRate, 2)],
            ['label' => 'Overtime Bill Rate', 'value' => round($billRate * 1.5, 2)],
            ['label' => 'Holiday Bill Rate', 'value' => round($billRate * 1.5, 2)],
            ['label' => 'Total Annual Revenue (hourly billings)', 'value' => round($annualRevenue, 2)],
            ['label' => 'Vehicle & Other Pass-Through Billings', 'value' => round($vehPass, 2)],
            ['label' => 'Total Annual Contract Revenue', 'value' => round($totalRevenue, 2), 'highlight' => true],
            ['label' => 'Direct Expense', 'value' => round($directExpense, 2)],
            ['label' => 'Vehicle & Other Pass-Through Costs', 'value' => round($vehCosts, 2)],
            ['label' => 'Total Annual Costs', 'value' => round($totalCosts, 2), 'highlight' => true],
            ['label' => 'Working Capital Requirement', 'value' => round($workingCap, 2)],
        ];

        return [
            'tableRows' => $rows,
            'contributoryProfit' => round($profit, 2),
            'profitPctOfRevenue' => round($profitPct, 1),
            'profitPerHour' => round($profitPerHr, 2),
        ];
    }
}

