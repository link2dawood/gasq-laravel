<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class CostAnalysisEngine
{
    /**
     * Mirrors `resources/views/calculators/cost-analysis.blade.php` demo math.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);
        $budget = (float) Arr::get($m, 'annualBudget', 180000);
        $guards = (float) Arr::get($m, 'guards', 3);
        $hours = (float) Arr::get($m, 'coverageHoursPerDay', 24);
        $theft = (float) Arr::get($m, 'annualTheftLosses', 50000);
        $insurance = (float) Arr::get($m, 'annualInsurancePremium', 25000);
        $liability = (float) Arr::get($m, 'annualLiabilityExposure', 30000);
        $reduction = (float) Arr::get($m, 'riskReductionPct', 70) / 100;

        $annualHours = $hours * 365;
        $perGuard = $guards > 0 ? $budget / $guards : 0;
        $perHour = $annualHours > 0 ? $budget / $annualHours : 0;
        $totalRisk = $theft + $insurance + $liability;
        $riskReduction = $totalRisk * $reduction;
        $netValue = $riskReduction - $budget;
        $rosi = $budget > 0 ? ($netValue / $budget) * 100 : 0;
        $monthly = $budget / 12;
        $weekly = $budget / 52;
        $payback = $riskReduction > 0 ? ($budget / $riskReduction) * 12 : 0;

        return [
            'costPerGuardPerYear' => round($perGuard, 0),
            'costPerHour' => round($perHour, 2),
            'monthlyCost' => round($monthly, 0),
            'weeklyCost' => round($weekly, 0),
            'totalAnnualRiskExposure' => round($totalRisk, 2),
            'riskReductionValue' => round($riskReduction, 2),
            'netValue' => round($netValue, 2),
            'rosiPct' => round($rosi, 1),
            'riskSavings' => round($riskReduction, 2),
            'paybackMonths' => round($payback, 1),
        ];
    }
}

