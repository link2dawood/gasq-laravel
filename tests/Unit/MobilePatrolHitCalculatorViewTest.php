<?php

namespace Tests\Unit;

use Tests\TestCase;

class MobilePatrolHitCalculatorViewTest extends TestCase
{
    public function test_mobile_patrol_hit_calculator_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.mobile-patrol-hit-calculator')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Hit Model Controls', $html);
        $this->assertStringContainsString('Live Hit Calculator Outputs', $html);
        $this->assertStringContainsString('The full daily and annual hit breakdown below updates from the shared input rail on the left.', $html);
        $this->assertStringContainsString('Decision Snapshot', $html);
        $this->assertStringContainsString('Breakdown', $html);
    }
}
