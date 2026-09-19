<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillRateViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_bill_rate_view_lays_out_the_seven_layers(): void
    {
        $html = view('calculators.bill-rate')->render();

        $this->assertStringContainsString('Bill Rate Breakdown', $html);
        $this->assertStringContainsString('Approved Final Rate / Allocation', $html);
        $this->assertStringContainsString('Full Cost Build-Up', $html);
        foreach (['Direct Labor', 'Employer Labor Burden', 'Workforce Maintenance &amp; Final Bill Rate', 'Other Direct Costs', 'G&amp;A / Operating Contingency', 'Profit'] as $n => $title) {
            $this->assertStringContainsString('Layer ' . ($n + 1) . ' — ' . $title, $html);
        }
        $this->assertStringContainsString('Layer 7 — Five-Year Pricing Plan', $html);

        // Layers expand/collapse; tabs split the current year from the forecast.
        $this->assertStringContainsString('id="brb_layers"', $html);
        $this->assertStringContainsString('id="brb-current"', $html);
        $this->assertStringContainsString('id="brb-forecast"', $html);
        $this->assertStringContainsString('Buyer Cost to Protect', $html);
        $this->assertStringContainsString('Rate Reconciliation', $html);

        // The approved baseline ships to the page as the default scenario.
        $this->assertStringContainsString('Primary Post Officers', $html);
    }
}
