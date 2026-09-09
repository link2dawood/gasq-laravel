<?php

namespace Tests\Unit;

use Tests\TestCase;

class CostToProtectEstimateViewTest extends TestCase
{
    public function test_master_estimate_renders_all_four_pages_with_the_headline_figures(): void
    {
        $html = view('pdf.cost-to-protect-estimate', [
            'scenario' => ['meta' => [
                'annualBudget' => 538769,
                'baselineWage' => 29.38,
                'scope' => [
                    'hoursOfCoveragePerDay' => 24,
                    'daysOfCoveragePerWeek' => 4,
                    'weeksOfCoverage' => 52,
                    'staffPerShift' => 1,
                ],
                'contact' => ['contactName' => 'Test Buyer', 'contactEmail' => 'buyer@example.com'],
            ]],
            'result' => [],
            'user' => null,
            'vendorId' => 7,
            'reportNumber' => 'GASQ-20260811-003857-V7',
            'reportType' => 'budget-calculator',
        ])->render();

        // Page furniture: four numbered pages, buyer-facing labelling
        foreach (['Page 1 of 4', 'Page 2 of 4', 'Page 3 of 4', 'Page 4 of 4'] as $marker) {
            $this->assertStringContainsString($marker, $html);
        }
        $this->assertStringContainsString('GASQ COST TO PROTECT', $html);
        $this->assertStringContainsString('ESTIMATE DASHBOARD', $html);
        $this->assertStringContainsString('DETAILED COST APPRAISAL COMPARISON', $html);
        $this->assertStringContainsString('RECOVERY INSIGHTS', $html);
        $this->assertStringContainsString('PROPERTY &amp; DISCLAIMER', $html);
        $this->assertStringContainsString('GASQ-20260811-003857-V7', $html);
        $this->assertStringContainsString('Vendor — Full Report', $html);

        // Headline figures
        $this->assertStringContainsString('$538,769', $html);
        $this->assertStringContainsString('$377,138', $html);
        $this->assertStringContainsString('$161,631', $html);
        $this->assertStringContainsString('$107.93', $html);
        $this->assertStringContainsString('$75.55', $html);
        $this->assertStringContainsString('8.4 months', $html);
        $this->assertStringContainsString('4,992', $html);

        // Charts are inline SVG data URIs (dompdf renders no other image source here)
        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);
    }
}
