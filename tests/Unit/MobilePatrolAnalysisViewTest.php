<?php

namespace Tests\Unit;

use Tests\TestCase;

class MobilePatrolAnalysisViewTest extends TestCase
{
    public function test_mobile_patrol_analysis_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.mobile-patrol-analysis')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Fleet Analysis Controls', $html);
        $this->assertStringContainsString('Live Fleet Analysis Outputs', $html);
        $this->assertStringContainsString('Dashboard tables, report tables, and export tools below all use the shared fleet inputs on the left.', $html);
        $this->assertStringContainsString('type="number" class="form-control form-control-sm" id="mpa_fiscalYear"', $html);
        $this->assertStringContainsString('id="mpa-dashboard"', $html);
        $this->assertStringContainsString('id="mpa-reports"', $html);
        $this->assertStringContainsString('id="mpa-tools"', $html);
    }
}
