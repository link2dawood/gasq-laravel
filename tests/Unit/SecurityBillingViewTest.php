<?php

namespace Tests\Unit;

use Tests\TestCase;

class SecurityBillingViewTest extends TestCase
{
    public function test_security_billing_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.security-billing')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Billing Model Controls', $html);
        $this->assertStringContainsString('Live Security Billing Outputs', $html);
        $this->assertStringContainsString('Scenario A below always mirrors the current shared inputs.', $html);
        $this->assertStringContainsString('id="sb-summary"', $html);
        $this->assertStringContainsString('id="sb-comparison"', $html);
        $this->assertStringContainsString('id="sb-profile"', $html);
    }
}
