<?php

namespace Tests\Unit;

use Tests\TestCase;

class BillRateViewTest extends TestCase
{
    public function test_bill_rate_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.bill-rate')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Bill Rate Controls', $html);
        $this->assertStringContainsString('Bill Rate Outputs', $html);
        $this->assertStringContainsString('Both tabs below update from the shared input rail on the left.', $html);
        $this->assertStringContainsString('id="br-basic"', $html);
        $this->assertStringContainsString('id="br-components"', $html);
    }
}
