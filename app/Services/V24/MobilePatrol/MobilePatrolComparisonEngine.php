<?php

namespace App\Services\V24\MobilePatrol;

use Illuminate\Support\Arr;

/**
 * Per-scenario cost/markup formula for the Mobile Patrol Comparison page.
 *
 * Keeps the wage-burden + vehicle-cost + markup math server-side so it is not
 * exposed in the browser. The controller calls this once per scenario (A/B).
 */
class MobilePatrolComparisonEngine
{
    /**
     * @param  array<string, mixed>  $p  One scenario's raw inputs.
     * @return array<string, float>
     */
    public function computeScenario(array $p): array
    {
        $hoursPerDay  = (float) (Arr::get($p, 'hoursPerDay') ?? 0);
        $daysPerYear  = (float) (Arr::get($p, 'daysPerYear') ?? 0);
        $wage         = (float) (Arr::get($p, 'wage') ?? 0);
        $burden       = (float) (Arr::get($p, 'burden') ?? 0);
        $vehFin       = (float) (Arr::get($p, 'vehFin') ?? 0);
        $miles        = (float) (Arr::get($p, 'miles') ?? 0);
        $mpg          = (float) (Arr::get($p, 'mpg') ?? 0);
        $fuel         = (float) (Arr::get($p, 'fuel') ?? 0);
        $repairs      = (float) (Arr::get($p, 'repairs') ?? 0);
        $tires        = (float) (Arr::get($p, 'tires') ?? 0);
        $oilCost      = (float) (Arr::get($p, 'oilCost') ?? 0);
        $oilMiles     = (float) (Arr::get($p, 'oilMiles') ?? 0);
        $insurance    = (float) (Arr::get($p, 'insurance') ?? 0);
        $markup       = (float) (Arr::get($p, 'markup') ?? 0);

        $hoursPerYear        = $hoursPerDay * $daysPerYear;
        $annualWageCost      = $hoursPerYear * $wage * (1 + $burden / 100);
        $milesDrivenPerYear  = $miles * $daysPerYear;
        $fuelGallonsPerYear  = $mpg > 0 ? $milesDrivenPerYear / $mpg : 0;
        $annualFuelCost      = $fuelGallonsPerYear * $fuel;
        $oilChangesPerYear   = $oilMiles > 0 ? $milesDrivenPerYear / $oilMiles : 0;
        $annualOilCost       = $oilChangesPerYear * $oilCost;
        $totalPreMarkup      = $annualWageCost + $vehFin + $annualFuelCost + $repairs + $tires + $annualOilCost + $insurance;
        $markupFrac          = $markup / 100;
        $annualCostWithMarkup = $markupFrac < 1 ? $totalPreMarkup / (1 - $markupFrac) : $totalPreMarkup;
        $monthlyCostWithMarkup = $annualCostWithMarkup / 12;
        $hourlyRate          = $hoursPerYear > 0 ? $annualCostWithMarkup / $hoursPerYear : 0;

        return [
            'hourlyRate' => round($hourlyRate, 2),
            'annualCostWithMarkup' => round($annualCostWithMarkup, 2),
            'monthlyCostWithMarkup' => round($monthlyCostWithMarkup, 2),
            'hoursPerYear' => round($hoursPerYear, 2),
            'totalPreMarkup' => round($totalPreMarkup, 2),
        ];
    }
}
