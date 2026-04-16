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
                    'governmentShouldCostHourly' => 86.75,
                    'annualBillableHours' => 8736,
                    'annualBudget' => 243000,
                    'monthlyBudget' => 20250,
                    'weeklyBudget' => 4673.08,
                    'dailyBudget' => 665.75,
                    'totalAllocatedPct' => 1.03,
                    'laborAllocationPct' => 0.62,
                    'laborAllocationAmount' => 150660,
                    'laborStatus' => 'Within benchmark',
                    'groupPercents' => [
                        'directLabor' => 0.54,
                        'fringeBurden' => 0.08,
                    ],
                    'groupAmounts' => [
                        'directLabor' => 131220,
                        'fringeBurden' => 19440,
                    ],
                    'allocationPercents' => [
                        'baseDirectLaborWage' => 0.4754,
                        'generalLiabilityInsurance' => 0.0545,
                    ],
                    'allocationAmounts' => [
                        'baseDirectLaborWage' => 115522.2,
                        'generalLiabilityInsurance' => 13243.5,
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Annual Budget', $html);
        $this->assertStringContainsString('Government Should Cost Hourly', $html);
        $this->assertStringContainsString('Annual Billable Hours', $html);
        $this->assertStringContainsString('Monthly Budget', $html);
        $this->assertStringContainsString('Weekly Budget', $html);
        $this->assertStringContainsString('Daily Budget', $html);
        $this->assertStringContainsString('Total Allocated Percent', $html);
        $this->assertStringContainsString('Labor Allocation Amount', $html);
        $this->assertStringContainsString('Labor Status', $html);
        $this->assertStringContainsString('Group Percents / Direct Labor', $html);
        $this->assertStringContainsString('Allocation Amounts / Base Direct Labor Wage', $html);
        $this->assertStringContainsString('Direct Labor Wage', $html);
        $this->assertStringContainsString('Annual Paid Hours Per FTE', $html);
        $this->assertStringContainsString('OT Holiday Premium Percent', $html);
        $this->assertStringContainsString('FICA Medicare Percent', $html);
        $this->assertStringContainsString('243,000', $html);
        $this->assertStringContainsString('86.75', $html);
        $this->assertStringContainsString('8,736', $html);
        $this->assertStringContainsString('4,673.08', $html);
        $this->assertStringContainsString('665.75', $html);
        $this->assertStringContainsString('103%', $html);
        $this->assertStringContainsString('150,660', $html);
        $this->assertStringContainsString('54%', $html);
        $this->assertStringContainsString('115,522.2', $html);
        $this->assertStringContainsString('Within benchmark', $html);
        $this->assertStringContainsString('8%', $html);
        $this->assertStringContainsString('7.65%', $html);

        $this->assertStringNotContainsString('meta.inputs.directLaborWage', $html);
        $this->assertStringNotContainsString('otHolidayPremiumPct', $html);
        $this->assertStringNotContainsString('ficaMedicarePct', $html);
    }
}
