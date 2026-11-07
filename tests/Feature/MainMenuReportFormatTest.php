<?php

namespace Tests\Feature;

use Tests\TestCase;

class MainMenuReportFormatTest extends TestCase
{
    public function test_nested_results_render_as_readable_rows_with_units_and_without_internal_fields(): void
    {
        $html = view('pdf.main-menu', [
            'user' => null,
            'scenario' => [],
            'result' => [
                'kpis' => ['annualCoverageHours' => null],
                'tabs' => [
                    'billRate' => ['basePayRate' => 0, 'markupPct' => 12.5],
                    'billRateComponents' => ['totalBillRate' => 25, 'components' => [
                        ['key' => 'wages', 'label' => 'Wages & Benefits', 'value' => 25, 'pct' => 100],
                    ]],
                    'contractSummary' => ['tableRows' => [
                        ['label' => 'Total Annual Costs', 'value' => 12345, 'highlight' => true],
                    ], 'contributoryProfit' => 0, 'profitPctOfRevenue' => 0, 'profitPerHour' => 0],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('12.50%', $html);
        $this->assertStringContainsString('100.00%', $html);
        $this->assertStringContainsString(\App\Support\Currency::format(0), $html);
        $this->assertMatchesRegularExpression('/<tr class="total"><td>Total Annual Costs<\/td><td class="v">[^<]*12,345\.00<\/td><\/tr>/', $html);
        foreach (['Tabs /', 'Table Rows', 'Highlight', 'Workforce Overview', 'Annual Coverage Hours'] as $label) {
            $this->assertStringNotContainsString($label, $html);
        }
    }
}
