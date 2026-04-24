<?php

namespace App\Services\V24\MobilePatrol;

use Illuminate\Support\Arr;

class MobilePatrolV24Engine
{
    protected function normalizeReturnOnSalesRate(float $input): float
    {
        if ($input <= 0) {
            return 0.0;
        }

        return $input > 1 ? ($input / 100) : $input;
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);

        $baselinePayRate = (float) Arr::get($m, 'baselinePayRate', 25.0);
        $divisor = (float) Arr::get($m, 'divisor', 0.70);
        $annualHours = (float) Arr::get($m, 'annualHours', 8736.0);
        $mph = (float) Arr::get($m, 'mph', 25.0);
        $hoursPerDay = (float) Arr::get($m, 'hoursPerDay', 24.0);
        $mpg = (float) Arr::get($m, 'mpg', 25.0);
        $fuelCostPerGallon = (float) Arr::get($m, 'fuelCostPerGallon', 4.11);
        $annualMaintenance = (float) Arr::get($m, 'annualMaintenance', 0.0);
        $tireSetsPerYear = (float) Arr::get($m, 'tireSetsPerYear', 4.0);
        $tireCostPerSet = (float) Arr::get($m, 'tireCostPerSet', 0.0);
        $autoInsurance = (float) Arr::get($m, 'autoInsurance', 0.0);
        $oilChangeIntervalMiles = (float) Arr::get($m, 'oilChangeIntervalMiles', 7500.0);
        $oilChangeCost = (float) Arr::get($m, 'oilChangeCost', 100.0);
        $returnOnSalesPct = (float) Arr::get($m, 'returnOnSalesPct', 0.25);
        $returnOnSalesRate = $this->normalizeReturnOnSalesRate($returnOnSalesPct);

        $employerCostHourly = $divisor > 0 ? $baselinePayRate / $divisor : 0.0;
        $annualLaborCost = $employerCostHourly * $annualHours;
        $milesPerDay = $mph * $hoursPerDay;
        $milesPerYear = $milesPerDay * 365;
        $gallonsPerYear = $mpg > 0 ? $milesPerYear / $mpg : 0.0;
        $annualFuelCost = $gallonsPerYear * $fuelCostPerGallon;
        $oilChangesPerYear = $oilChangeIntervalMiles > 0 ? ceil($milesPerYear / $oilChangeIntervalMiles) : 0.0;
        $annualOilCost = $oilChangesPerYear * $oilChangeCost;
        $annualTireCost = $tireSetsPerYear * $tireCostPerSet;
        $totalAnnualCost = $annualLaborCost + $annualFuelCost + $annualMaintenance + $annualTireCost + $autoInsurance + $annualOilCost;
        $returnOnSalesAmount = $totalAnnualCost * $returnOnSalesRate;
        $totalAnnualCostWithReturnOnSales = $totalAnnualCost + $returnOnSalesAmount;
        $costPerHour = $annualHours > 0 ? $totalAnnualCostWithReturnOnSales / $annualHours : 0.0;
        $hourlyBillableRate = $costPerHour;

        return [
            'baselinePayRate' => round($baselinePayRate, 2),
            'divisor' => round($divisor, 4),
            'annualHours' => round($annualHours, 0),
            'mph' => round($mph, 2),
            'hoursPerDay' => round($hoursPerDay, 2),
            'mpg' => round($mpg, 2),
            'fuelCostPerGallon' => round($fuelCostPerGallon, 2),
            'annualMaintenance' => round($annualMaintenance, 2),
            'tireSetsPerYear' => round($tireSetsPerYear, 2),
            'tireCostPerSet' => round($tireCostPerSet, 2),
            'autoInsurance' => round($autoInsurance, 2),
            'oilChangeIntervalMiles' => round($oilChangeIntervalMiles, 0),
            'oilChangeCost' => round($oilChangeCost, 2),
            'returnOnSalesPct' => round($returnOnSalesPct, 4),
            'returnOnSalesRate' => round($returnOnSalesRate, 4),
            'returnOnSalesPercentDisplay' => round($returnOnSalesRate * 100, 2),
            'employerCostHourly' => round($employerCostHourly, 2),
            'annualLaborCost' => round($annualLaborCost, 2),
            'milesPerDay' => round($milesPerDay, 0),
            'milesPerYear' => round($milesPerYear, 0),
            'gallonsPerYear' => round($gallonsPerYear, 0),
            'annualFuelCost' => round($annualFuelCost, 2),
            'oilChangesPerYear' => round($oilChangesPerYear, 1),
            'annualOilCost' => round($annualOilCost, 2),
            'annualTireCost' => round($annualTireCost, 2),
            'totalAnnualCost' => round($totalAnnualCost, 2),
            'returnOnSalesAmount' => round($returnOnSalesAmount, 2),
            'totalAnnualCostWithReturnOnSales' => round($totalAnnualCostWithReturnOnSales, 2),
            'costPerHour' => round($costPerHour, 2),
            'hourlyBillableRate' => round($hourlyBillableRate, 2),
        ];
    }
}
