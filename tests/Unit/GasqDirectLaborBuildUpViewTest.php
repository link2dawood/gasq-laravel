<?php

namespace Tests\Unit;

use Tests\TestCase;

class GasqDirectLaborBuildUpViewTest extends TestCase
{
    public function test_direct_labor_build_up_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.gasq-direct-labor-build-up')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Direct Labor Controls', $html);
        $this->assertStringContainsString('Live Direct Labor Build-Up', $html);
        $this->assertStringContainsString('The table below updates from the shared input rail on the left.', $html);
        $this->assertStringContainsString('id="dlb_controls"', $html);
        $this->assertStringContainsString('id="dlb_stat_hours"', $html);
        $this->assertStringContainsString('id="dlb_stat_direct"', $html);
        $this->assertStringContainsString('id="dlb_stat_burdened"', $html);
        $this->assertStringContainsString('id="dlb_stat_total"', $html);
        $this->assertStringContainsString('id="dlb_body"', $html);
    }
}
