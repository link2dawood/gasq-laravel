<?php

namespace Tests\Unit;

use App\Services\V24\MobilePatrol\MobilePatrolV24Engine;
use Tests\TestCase;

class MobilePatrolV24EngineTest extends TestCase
{
    public function test_it_matches_the_mobile_patrol_formula_inputs(): void
    {
        $engine = new MobilePatrolV24Engine;

        $out = $engine->compute([
            'meta' => [
                'baselinePayRate' => 25,
                'divisor' => 0.70,
                'annualHours' => 8736,
                'mph' => 25,
                'hoursPerDay' => 24,
                'mpg' => 25,
                'fuelCostPerGallon' => 4.11,
                'annualMaintenance' => 0,
                'tireSetsPerYear' => 4,
                'tireCostPerSet' => 0,
                'autoInsurance' => 0,
                'oilChangeIntervalMiles' => 7500,
                'oilChangeCost' => 100,
                'returnOnSalesPct' => 0,
            ],
        ]);

        $this->assertSame(35.71, $out['employerCostHourly']);
        $this->assertSame(312000.00, $out['annualLaborCost']);
        $this->assertSame(600.0, $out['milesPerDay']);
        $this->assertSame(219000.0, $out['milesPerYear']);
        $this->assertSame(8760.0, $out['gallonsPerYear']);
        $this->assertSame(36003.60, $out['annualFuelCost']);
        $this->assertSame(30.0, $out['oilChangesPerYear']);
        $this->assertSame(3000.00, $out['annualOilCost']);
        $this->assertSame(351003.60, $out['totalAnnualCost']);
        $this->assertSame(40.18, $out['costPerHour']);
        $this->assertSame(40.18, $out['hourlyBillableRate']);
    }
}
