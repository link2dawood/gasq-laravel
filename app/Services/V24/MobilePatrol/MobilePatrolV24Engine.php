<?php

namespace App\Services\V24\MobilePatrol;

use Illuminate\Support\Arr;

class MobilePatrolV24Engine
{
    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        // NOTE: Mirrors current UI math. Replace with V24 workbook-derived formulas once mapped.
        $m = (array) ($scenario['meta'] ?? []);

        $hoursPerDay = (float) Arr::get($m, 'hoursPerDay', 24);
        $daysPerYear = (float) Arr::get($m, 'daysPerYear', 365);
        $wage = (float) Arr::get($m, 'patrolmanHourlyWage', 30.00);
        $burden = (float) Arr::get($m, 'payrollBurdenPercent', 24) / 100;
        $vehFin = (float) Arr::get($m, 'vehicleAnnualFinanceCost', 7980.00);
        $miles = (float) Arr::get($m, 'milesDrivenPerDay', 360);
        $mpg = (float) Arr::get($m, 'milesPerGallon', 20);
        $fuelPrice = (float) Arr::get($m, 'fuelPricePerGallon', 2.57);
        $repairs = (float) Arr::get($m, 'annualRepairs', 4000.00);
        $tires = (float) Arr::get($m, 'tiresAnnualCost', 1200.00);
        $oilCost = (float) Arr::get($m, 'oilChangeCostPerService', 32);
        $oilMiles = (float) Arr::get($m, 'milesBetweenOilChanges', 6000);
        $insurance = (float) Arr::get($m, 'autoInsuranceAnnualCost', 1500.00);
        $markup = (float) Arr::get($m, 'markupPercent', 27) / 100;

        $hoursPerYear = $hoursPerDay * $daysPerYear;
        $annualWageCost = $hoursPerYear * $wage * (1 + $burden);
        $milesDrivenPerYear = $miles * $daysPerYear;
        $fuelGallonsPerYear = $mpg > 0 ? $milesDrivenPerYear / $mpg : 0;
        $annualFuelCost = $fuelGallonsPerYear * $fuelPrice;
        $oilChangesPerYear = $oilMiles > 0 ? $milesDrivenPerYear / $oilMiles : 0;
        $annualOilCost = $oilChangesPerYear * $oilCost;
        $totalPreMarkup = $annualWageCost + $vehFin + $annualFuelCost + $repairs + $tires + $annualOilCost + $insurance;
        $annualCostWithMarkup = $markup < 1 ? $totalPreMarkup / (1 - $markup) : $totalPreMarkup;
        $hourlyBillableRate = $hoursPerYear > 0 ? $annualCostWithMarkup / $hoursPerYear : 0;

        return [
            'hoursPerYear' => round($hoursPerYear, 0),
            'annualWageCost' => round($annualWageCost, 2),
            'milesDrivenPerYear' => round($milesDrivenPerYear, 0),
            'fuelGallonsPerYear' => round($fuelGallonsPerYear, 0),
            'annualFuelCost' => round($annualFuelCost, 2),
            'oilChangesPerYear' => round($oilChangesPerYear, 1),
            'annualOilChangeCost' => round($annualOilCost, 2),
            'preMarkupCost' => round($totalPreMarkup, 2),
            'dailyCost' => round($annualCostWithMarkup / 365, 2),
            'weeklyCost' => round($annualCostWithMarkup / 52, 2),
            'monthlyCost' => round($annualCostWithMarkup / 12, 2),
            'annualCost' => round($annualCostWithMarkup, 2),
            'hourlyBillableRate' => round($hourlyBillableRate, 2),
        ];
    }
}

