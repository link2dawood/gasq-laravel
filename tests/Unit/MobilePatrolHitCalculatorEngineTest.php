<?php

namespace Tests\Unit;

use App\Services\V24\Standalone\MobilePatrolHitCalculatorEngine;
use Tests\TestCase;

class MobilePatrolHitCalculatorEngineTest extends TestCase
{
    public function test_defaults_compute_expected_shapes_and_reasonable_values(): void
    {
        $eng = new MobilePatrolHitCalculatorEngine;
        $out = $eng->compute([
            'meta' => [
                'daysPerYear' => 365,
                'hoursPerDay' => 24,
                'hitsPerDay' => 180,
                'milesPerDay' => 360,
                'costPerMile' => 0.67,
                'equipmentPerDay' => 0,
                'regularHoursPerDay' => 24,
                'overtimeHoursPerDay' => 0,
                'regularHourlyUsd' => 30.0,
                'overtimeHourlyUsd' => 45.0,
                'markupPct' => 27,
            ],
        ]);

        $this->assertSame('standalone:mobile-patrol-hit-calculator', $out['reference']);
        $this->assertSame(21322, $out['annual']['totalHits']);
        $this->assertGreaterThan(0, $out['daily']['totalCost']);
        $this->assertGreaterThan($out['daily']['totalCost'], $out['daily']['billPerDay']);
        $this->assertGreaterThan(0, $out['daily']['costPerHit']);
        $this->assertGreaterThan($out['daily']['costPerHit'], $out['daily']['billPerHit']);
    }
}

