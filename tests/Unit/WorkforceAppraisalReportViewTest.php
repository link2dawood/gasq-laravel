<?php

namespace Tests\Unit;

use Tests\TestCase;

class WorkforceAppraisalReportViewTest extends TestCase
{
    public function test_workforce_appraisal_view_includes_shared_direct_labor_build_up_section(): void
    {
        $html = view('calculators.workforce-appraisal-report', [
            'initialTab' => 'cfo',
        ])->render();

        $this->assertStringContainsString('Direct Labor Build-Up', $html);
        $this->assertStringContainsString('Shared across all Workforce Appraisal tabs', $html);
        $this->assertStringContainsString('id="wa_dlb_root"', $html);
    }
}
