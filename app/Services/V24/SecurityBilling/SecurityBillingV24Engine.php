<?php

namespace App\Services\V24\SecurityBilling;

use Illuminate\Support\Arr;

class SecurityBillingV24Engine
{
    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        // NOTE: Mirrors current UI math. Replace with V24 workbook-derived formulas once mapped.
        $meta = (array) ($scenario['meta'] ?? []);

        $basePay = (float) (Arr::get($meta, 'basePayRate') ?? 18.0);
        $hours = (float) (Arr::get($meta, 'hoursPerWeek') ?? 40.0);
        $weeks = (float) (Arr::get($meta, 'weeksPerYear') ?? 52.0);

        $fica = (float) (Arr::get($meta, 'ficaPct') ?? 7.65) / 100;
        $futa = (float) (Arr::get($meta, 'futaPct') ?? 0.8) / 100;
        $suta = (float) (Arr::get($meta, 'sutaPct') ?? 5.76) / 100;
        $overhead = (float) (Arr::get($meta, 'overheadPct') ?? 35.0) / 100;
        $profit = (float) (Arr::get($meta, 'profitPct') ?? 15.0) / 100;

        $uCost = (float) (Arr::get($meta, 'uniformCostPerUniform') ?? 75.0);
        $uQty = (float) (Arr::get($meta, 'uniformsPerEmployee') ?? 2.0);
        $trainingCost = (float) (Arr::get($meta, 'trainingCostPerHire') ?? 500.0);

        $taxRate = $fica + $futa + $suta;
        $withTaxes = $basePay * (1 + $taxRate);
        $withOverhead = $withTaxes * (1 + $overhead);
        $billRate = $profit < 1 ? ($withOverhead / (1 - $profit)) : 0.0;

        $otRate = $billRate * 1.5;
        $weekly = $billRate * $hours;
        $monthly = $weekly * ($weeks / 12);
        $annual = $weekly * $weeks;

        $uniformTotal = $uCost * $uQty;
        $trainingHr = ($hours > 0 && $weeks > 0) ? ($trainingCost / ($hours * $weeks)) : 0.0;

        return [
            'basePayRate' => round($basePay, 2),
            'costWithPayrollTaxes' => round($withTaxes, 2),
            'costWithOverhead' => round($withOverhead, 2),
            'billRate' => round($billRate, 2),
            'otBillRate' => round($otRate, 2),
            'holidayBillRate' => round($otRate, 2),
            'weeklyTotal' => round($weekly, 2),
            'monthlyTotal' => round($monthly, 2),
            'annualTotal' => round($annual, 2),
            'uniformTotal' => round($uniformTotal, 2),
            'trainingCostPerHour' => round($trainingHr, 4),
            'totalBillRate' => round($billRate, 2),
        ];
    }
}

