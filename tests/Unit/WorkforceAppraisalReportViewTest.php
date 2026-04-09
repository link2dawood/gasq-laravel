<?php

namespace Tests\Unit;

use Tests\TestCase;

class WorkforceAppraisalReportViewTest extends TestCase
{
    public function test_workforce_appraisal_view_uses_shared_input_and_results_workspace_layout_for_all_tabs(): void
    {
        $html = view('calculators.workforce-appraisal-report', [
            'initialTab' => 'cfo',
        ])->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Workforce Appraisal Controls', $html);
        $this->assertStringContainsString('Live Workforce Appraisal Outputs', $html);
        $this->assertStringContainsString('All tabs below stay connected to the shared input rail on the left.', $html);
        $this->assertStringContainsString('Direct Labor Build-Up', $html);
        $this->assertStringContainsString('Shared across all Workforce Appraisal tabs', $html);
        $this->assertStringContainsString('id="wa_dlb_root"', $html);
        $this->assertStringContainsString('id="wa-pane-cfo"', $html);
        $this->assertStringContainsString('id="wa-pane-posts"', $html);
        $this->assertStringContainsString('id="wa-pane-appraisal"', $html);
        $this->assertStringContainsString('id="wa-pane-price"', $html);
    }

    public function test_post_position_summary_entry_point_uses_posts_tab_with_shared_workspace_layout(): void
    {
        $html = view('calculators.workforce-appraisal-report', [
            'initialTab' => 'posts',
        ])->render();

        $this->assertStringContainsString('Workforce Appraisal Controls', $html);
        $this->assertStringContainsString('Live Workforce Appraisal Outputs', $html);
        $this->assertStringContainsString('data-bs-target="#wa-pane-posts"', $html);
        $this->assertStringContainsString('id="wa-pane-posts"', $html);
        $this->assertStringContainsString('tab-pane fade show active', $html);
        $this->assertStringContainsString('Scope of Work', $html);
    }

    public function test_appraisal_comparison_summary_entry_point_uses_appraisal_tab_with_shared_workspace_layout(): void
    {
        $html = view('calculators.workforce-appraisal-report', [
            'initialTab' => 'appraisal',
        ])->render();

        $this->assertStringContainsString('Workforce Appraisal Controls', $html);
        $this->assertStringContainsString('Live Workforce Appraisal Outputs', $html);
        $this->assertStringContainsString('data-bs-target="#wa-pane-appraisal"', $html);
        $this->assertStringContainsString('id="wa-pane-appraisal"', $html);
        $this->assertStringContainsString('Appraisal Comparison Summary', $html);
        $this->assertStringContainsString('Coverage Statement', $html);
        $this->assertStringContainsString('tab-pane fade show active', $html);
    }
}
