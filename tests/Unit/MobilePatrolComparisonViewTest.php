<?php

namespace Tests\Unit;

use Tests\TestCase;

class MobilePatrolComparisonViewTest extends TestCase
{
    public function test_mobile_patrol_comparison_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.mobile-patrol-comparison')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Comparison Model Controls', $html);
        $this->assertStringContainsString('Live Patrol Comparison Outputs', $html);
        $this->assertStringContainsString('Both scenario result panels below update from the shared input rail on the left.', $html);
        $this->assertStringContainsString('Scenario A Inputs', $html);
        $this->assertStringContainsString('Scenario B Inputs', $html);
    }
}
