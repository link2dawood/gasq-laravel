<?php

namespace Tests\Feature;

use Tests\TestCase;

class WeightedRateEmailFormatTest extends TestCase
{
    public function test_weighted_cover_uses_masked_converted_totals_and_review_link(): void
    {
        $html = view('emails.vendor-weighted-rate-report', [
            'recipientName' => 'Avery', 'reportCurrency' => ['rate' => 2, 'symbol' => '$'],
            'totals' => ['weightedPayWage' => 12.19, 'weightedBillRate' => 31.34,
                'monthlyBillCost' => 500228.5, 'annualBillCost' => 6002742.5, 'overallWageShare' => .3889],
            'bookingUrl' => 'https://example.test/review',
        ])->render();
        foreach (['Dear Avery', '$XX.38', '$XX.68', '$X,XXX,457', '$XX,XXX,485', 'XX.89%', 'href="https://example.test/review"', 'provide the PDF password'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        foreach (['$24.38', '$62.68', '38.89%', 'Reply “ACCESS”', '[Customer Name]'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html);
        }
    }
}
