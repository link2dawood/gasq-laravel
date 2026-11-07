<?php

namespace Tests\Feature;

use App\Services\WorkforceAllocationSummary;
use Tests\TestCase;

class WorkforceAllocationEmailTest extends TestCase
{
    public function test_allocation_totals_follow_explicit_percentages_and_benchmark_fallback(): void
    {
        $service = app(WorkforceAllocationSummary::class);
        $default = $service->build([]);
        $this->assertEqualsWithDelta($default['estimate']['totalAnnualVendor'], $default['total'], 0.01);
        $custom = $service->build(['meta' => ['allocations' => ['baseDirectLaborWage' => 50]]]);
        $this->assertEqualsWithDelta($custom['estimate']['totalAnnualVendor'] * 0.5, $custom['total'], 0.01);
        $this->assertEquals($custom['total'], $custom['amounts']['directLabor']);
        $this->assertEquals(0, $custom['amounts']['fringeBurden']);
    }

    public function test_workforce_cover_masks_budget_values_and_uses_document_wording(): void
    {
        $html = view('emails.workforce-allocation-protected', [
            'clientName' => 'Avery Buyer', 'budgetTotal' => 757174,
            'allocations' => ['directLabor' => 442957, 'fringeBurden' => 100150, 'operationsSupport' => 100497, 'overheadProfit' => 113570],
            'bookingUrl' => 'https://example.test/schedule',
        ])->render();
        foreach (['Dear Avery Buyer', '$XXX,174', '$XXX,957', '$XXX,150', '$XXX,497', '$XXX,570', 'href="https://example.test/schedule"', 'releasing the password during the appointment', 'Grayling D. Livingston'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('757,174', $html);
        $this->assertStringNotContainsString('[Customer Name]', $html);
    }
}
