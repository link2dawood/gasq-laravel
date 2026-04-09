<?php

namespace Tests\Unit;

use Tests\TestCase;

class GovernmentContractCalculatorViewTest extends TestCase
{
    public function test_government_contract_calculator_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.government-contract-calculator')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Contract Model Controls', $html);
        $this->assertStringContainsString('Live Government Contract Outputs', $html);
        $this->assertStringContainsString('The bill-rate summary and cost stack below update from the shared inputs on the left.', $html);
        $this->assertStringContainsString('Decision Snapshot', $html);
        $this->assertStringContainsString('Cost Stack Breakdown', $html);
    }
}
