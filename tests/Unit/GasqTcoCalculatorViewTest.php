<?php

namespace Tests\Unit;

use Tests\TestCase;

class GasqTcoCalculatorViewTest extends TestCase
{
    public function test_gasq_tco_calculator_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.gasq-tco-calculator')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('TCO Model Controls', $html);
        $this->assertStringContainsString('Live TCO Comparison Outputs', $html);
        $this->assertStringContainsString('The comparison summary below updates from the shared TCO inputs on the left.', $html);
        $this->assertStringContainsString('Decision Signal', $html);
        $this->assertStringContainsString('Comparison Breakdown', $html);
    }
}
