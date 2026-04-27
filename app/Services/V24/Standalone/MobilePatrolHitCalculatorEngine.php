<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class MobilePatrolHitCalculatorEngine
{
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);

        $weeklyChecks          = max(0, (int)   Arr::get($m, 'weeklyChecks', 84));
        $weeksPerYear          = max(1, (float)  Arr::get($m, 'weeksPerYear', 52));
        $minutesOnSite         = max(0, (float)  Arr::get($m, 'minutesOnSite', 15));
        $minutesTravel         = max(0, (float)  Arr::get($m, 'minutesTravel', 10));
        $officerPayRate        = max(0, (float)  Arr::get($m, 'officerPayRate', 25));
        $payrollBurdenPct      = max(0, (float)  Arr::get($m, 'payrollBurdenPct', 30)) / 100;
        $vehicleCostPerHour    = max(0, (float)  Arr::get($m, 'vehicleCostPerHour', 6.50));
        $fuelCostPerHour       = max(0, (float)  Arr::get($m, 'fuelCostPerHour', 2.25));
        $equipmentCostPerHour  = max(0, (float)  Arr::get($m, 'equipmentCostPerHour', 1.75));
        $supervisionCostPerHour= max(0, (float)  Arr::get($m, 'supervisionCostPerHour', 4.00));
        $overheadPct           = max(0, (float)  Arr::get($m, 'overheadPct', 10)) / 100;
        $gaPct                 = max(0, (float)  Arr::get($m, 'gaPct', 10)) / 100;
        $profitPct             = max(0, (float)  Arr::get($m, 'profitPct', 10)) / 100;
        $minimumCharge         = max(0, (float)  Arr::get($m, 'minimumCharge', 23));
        $addOnCost             = max(0, (float)  Arr::get($m, 'addOnCost', 0));

        $checksPerDay       = $weeklyChecks / 7;
        $totalMinutes       = $minutesOnSite + $minutesTravel;
        $hoursPerCheck      = $totalMinutes / 60;

        $burdenedRate          = $officerPayRate * (1 + $payrollBurdenPct);
        $totalOpCostPerHour    = $burdenedRate + $vehicleCostPerHour + $fuelCostPerHour + $equipmentCostPerHour + $supervisionCostPerHour;

        $baseCostPerCheck      = $totalOpCostPerHour * $hoursPerCheck;
        $overheadPerCheck      = $baseCostPerCheck * $overheadPct;
        $gaPerCheck            = $baseCostPerCheck * $gaPct;
        $subtotalCostPerCheck  = $baseCostPerCheck + $overheadPerCheck + $gaPerCheck;
        $preMkupCostPerCheck   = $subtotalCostPerCheck + $addOnCost;
        $profitAmountPerCheck  = $preMkupCostPerCheck * $profitPct;
        $calculatedPrice       = $preMkupCostPerCheck + $profitAmountPerCheck;
        $finalPricePerCheck    = max($calculatedPrice, $minimumCharge);

        $monthlyChecks  = $weeklyChecks * ($weeksPerYear / 12);
        $annualChecks   = $weeklyChecks * $weeksPerYear;
        $weeklyRevenue  = $finalPricePerCheck * $weeklyChecks;
        $monthlyRevenue = $finalPricePerCheck * $monthlyChecks;
        $annualRevenue  = $finalPricePerCheck * $annualChecks;

        $grossProfitPerCheck = $finalPricePerCheck - $preMkupCostPerCheck;
        $profitMarginPct     = $finalPricePerCheck > 0 ? $grossProfitPerCheck / $finalPricePerCheck : 0;

        return [
            'checksPerDay'         => round($checksPerDay, 2),
            'totalMinutes'         => round($totalMinutes, 2),
            'hoursPerCheck'        => round($hoursPerCheck, 4),
            'burdenedRate'         => round($burdenedRate, 2),
            'totalOpCostPerHour'   => round($totalOpCostPerHour, 2),
            'baseCostPerCheck'     => round($baseCostPerCheck, 4),
            'overheadPerCheck'     => round($overheadPerCheck, 4),
            'gaPerCheck'           => round($gaPerCheck, 4),
            'subtotalCostPerCheck' => round($subtotalCostPerCheck, 4),
            'preMkupCostPerCheck'  => round($preMkupCostPerCheck, 4),
            'profitAmountPerCheck' => round($profitAmountPerCheck, 4),
            'finalPricePerCheck'   => round($finalPricePerCheck, 2),
            'weeklyChecks'         => $weeklyChecks,
            'monthlyChecks'        => round($monthlyChecks, 2),
            'annualChecks'         => round($annualChecks, 2),
            'weeklyRevenue'        => round($weeklyRevenue, 2),
            'monthlyRevenue'       => round($monthlyRevenue, 2),
            'annualRevenue'        => round($annualRevenue, 2),
            'grossProfitPerCheck'  => round($grossProfitPerCheck, 4),
            'profitMarginPct'      => round($profitMarginPct, 6),
            'reference'            => 'standalone:mobile-patrol-hit-calculator',
        ];
    }
}
