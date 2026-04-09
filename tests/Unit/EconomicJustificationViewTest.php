<?php

namespace Tests\Unit;

use Tests\TestCase;

class EconomicJustificationViewTest extends TestCase
{
    public function test_economic_justification_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.economic-justification')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Economic Model Inputs', $html);
        $this->assertStringContainsString('Economic Justification Outputs', $html);
        $this->assertStringContainsString('Every section on the right stays synchronized with the shared input rail on the left.', $html);
        $this->assertStringContainsString('Customer Return on Investment Savings', $html);
        $this->assertStringContainsString('Projected Hours &amp; Cost Analysis', $html);
    }
}
