<?php

namespace Tests\Unit;

use Tests\TestCase;

class WorkforceToPostViewTest extends TestCase
{
    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'scenario' => ['meta' => [
                'annualBudget' => 538769,
                'baselineWage' => 29.38,
                'scope' => [
                    'hoursOfCoveragePerDay' => 24,
                    'daysOfCoveragePerWeek' => 7,
                    'weeksOfCoverage' => 52,
                    'staffPerShift' => 1,
                ],
                'contact' => [
                    'contactName' => 'Test Vendor',
                    'contactCompany' => 'Acme Protective',
                    'contactEmail' => 'vendor@example.com',
                ],
            ]],
            'result' => [],
            'user' => null,
            'vendorId' => 3,
            'reportNumber' => 'GASQ-20260911-152044-V3',
            'reportType' => 'budget-calculator-allocation',
            'reportScope' => 'allocation',
        ];
    }

    public function test_allocation_report_renders_the_three_redesigned_pages(): void
    {
        $html = view('pdf.workforce-bill-rate-breakdown', $this->payload())->render();

        // Page furniture: three numbered pages under the redesigned headings
        foreach (['Page 1 of 3', 'Page 2 of 3', 'Page 3 of 3'] as $marker) {
            $this->assertStringContainsString($marker, $html);
        }
        $this->assertStringContainsString('GASQ Workforce-to-Post Report', $html);
        $this->assertStringContainsString('Executive Cost Dashboard', $html);
        $this->assertStringContainsString('Line-Item Cost Composition', $html);
        $this->assertStringContainsString('GASQ Certified Statement', $html);
        $this->assertStringContainsString('GASQ-20260911-152044-V3', $html);
        $this->assertStringContainsString('Vendor — Full Report', $html);

        // Dashboard blocks
        $this->assertStringContainsString('Allocation Mix', $html);
        $this->assertStringContainsString('Dashboard Indicators', $html);
        $this->assertStringContainsString('Executive Readout', $html);
        $this->assertStringContainsString('Cost Elements Stated as Included', $html);
        $this->assertStringContainsString('Pricing Scope', $html);

        // Allocation totals: the vendor contract value and its four groups
        $this->assertStringContainsString('$659,992.32', $html);
        foreach (['Direct Labor', 'Fringe &amp; Employer Burden', 'Operations &amp; Contract Support', 'Overhead, G&amp;A &amp; Profit'] as $group) {
            $this->assertStringContainsString($group, $html);
        }

        // $0.00 line items stay hidden; real ones are listed
        $this->assertStringContainsString('Baseline Wage', $html);
        $this->assertStringNotContainsString('Locality Pay', $html);
    }

    public function test_group_amounts_reconcile_against_the_stated_contract_value(): void
    {
        $html = view('pdf.workforce-bill-rate-breakdown', $this->payload())->render();

        // Benchmark percentages total 100%, so the report reports a clean
        // reconciliation rather than flagging a variance.
        $this->assertStringContainsString('Reconciliation Check', $html);
        $this->assertStringContainsString('matching the stated contract / budget value', $html);
        $this->assertStringContainsString('Displayed group percentages total 100.00%', $html);
        $this->assertStringNotContainsString('Reconciliation Flag', $html);
    }
}
