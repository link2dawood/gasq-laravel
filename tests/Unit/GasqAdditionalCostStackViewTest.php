<?php

namespace Tests\Unit;

use Tests\TestCase;

class GasqAdditionalCostStackViewTest extends TestCase
{
    public function test_additional_cost_stack_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.gasq-additional-cost-stack')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Additional Cost Controls', $html);
        $this->assertStringContainsString('Live Additional Cost Stack', $html);
        $this->assertStringContainsString('The cost stack below updates from the shared input rail on the left.', $html);
        $this->assertStringContainsString('id="acs_controls"', $html);
        $this->assertStringContainsString('id="acs_stat_hours"', $html);
        $this->assertStringContainsString('id="acs_stat_modules"', $html);
        $this->assertStringContainsString('id="acs_stat_total_h"', $html);
        $this->assertStringContainsString('id="acs_stat_total_a"', $html);
        $this->assertStringContainsString('id="acs_body"', $html);
    }
}
