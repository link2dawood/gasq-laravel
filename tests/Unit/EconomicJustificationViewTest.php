<?php

namespace Tests\Unit;

use App\Services\BudgetSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EconomicJustificationViewTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_both_roi_views_show_the_requested_employer_label_and_hours_and_staff_totals(): void
    {
        foreach (['calculators.main-menu', 'calculators.economic-justification'] as $view) {
            $html = view($view, ['budgetSummary' => app(BudgetSummaryService::class)->fromScenario(['meta' => ['baselineWage' => 25]])])->render();

            $this->assertStringContainsString($view === 'calculators.main-menu'
                ? 'Workforce Baseline Assumption Labor Rate ($):'
                : 'Employer Hourly Cost to Protect ($):', $html);
            foreach (['Total Weekly Hours', 'Total Monthly Hours', 'Total Annual Hours', 'Total Staff Required'] as $label) {
                $this->assertStringContainsString($label, $html);
            }
        }
    }
}
