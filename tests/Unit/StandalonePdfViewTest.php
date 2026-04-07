<?php

namespace Tests\Unit;

use Tests\TestCase;

class StandalonePdfViewTest extends TestCase
{
    public function test_standalone_pdf_view_renders_human_readable_labels_and_values(): void
    {
        $html = view('pdf.standalone', [
            'reportType' => 'budget-calculator',
            'generatedAt' => 'Apr 7, 2026 12:00 PM',
            'scenario' => [
                'meta' => [
                    'annualBudget' => 243000,
                    'inputs' => [
                        'directLaborWage' => 27.48,
                        'annualPaidHoursPerFte' => 1456,
                        'otHolidayPremiumPct' => 0.08,
                        'ficaMedicarePct' => 0.0765,
                    ],
                ],
            ],
            'result' => [
                'kpis' => [
                    'annualBudget' => 243000,
                    'monthlyBudget' => 20250,
                    'weeklyBudget' => 4673.08,
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Annual Budget', $html);
        $this->assertStringContainsString('Monthly Budget', $html);
        $this->assertStringContainsString('Weekly Budget', $html);
        $this->assertStringContainsString('Direct Labor Wage', $html);
        $this->assertStringContainsString('Annual Paid Hours Per FTE', $html);
        $this->assertStringContainsString('OT Holiday Premium Percent', $html);
        $this->assertStringContainsString('FICA Medicare Percent', $html);
        $this->assertStringContainsString('243,000', $html);
        $this->assertStringContainsString('4,673.08', $html);
        $this->assertStringContainsString('8%', $html);
        $this->assertStringContainsString('7.65%', $html);

        $this->assertStringNotContainsString('meta.inputs.directLaborWage', $html);
        $this->assertStringNotContainsString('otHolidayPremiumPct', $html);
        $this->assertStringNotContainsString('ficaMedicarePct', $html);
    }
}
