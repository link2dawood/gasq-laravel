<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Bid Engagement Report's comparison.
 *
 * It used to print the vendor's real bid in the Pricing Summary and a modelled
 * 70%-of-internal figure in the side-by-side table just below it: two different
 * vendor numbers in one buyer-facing document, with the saving pinned at 30%
 * and payback at 12 months on every report regardless of the bid. The vendor
 * column is now the bid itself.
 */
class BidEngagementReportComparisonTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $questionnaire */
    private function render(float $hourlyRate, float $annualPrice, array $questionnaire): string
    {
        $buyer = User::factory()->create(['user_type' => 'buyer']);
        $vendor = User::factory()->create(['user_type' => 'vendor', 'company' => 'ABC Security']);

        $job = JobPosting::create([
            'user_id' => $buyer->id,
            'title' => 'Distribution Center Security',
            'location' => 'Dallas, TX',
            'status' => 'open',
            'questionnaire_data' => $questionnaire,
        ]);

        $bid = Bid::create([
            'job_posting_id' => $job->id,
            'user_id' => $vendor->id,
            'amount' => $annualPrice,
            'hourly_bill_rate' => $hourlyRate,
            'annual_price' => $annualPrice,
            'status' => 'pending',
        ]);

        return view('pdf.bid-engagement-report', [
            'bid' => $bid->fresh(),
            'job' => $job->fresh(),
            'vendor' => $vendor,
            'buyer' => $buyer,
            'opportunity' => null,
        ])->render();
    }

    public function test_the_comparison_uses_the_submitted_bid_not_a_modelled_rate(): void
    {
        // Baseline $25 → internal TCO $91.84/hr over 8,736 hours = $802,336.
        // The bid is $32.65/hr = $285,230.40.
        $html = $this->render(32.65, 285230.40, [
            'baseline_wage' => 25.00,
            'hours_per_day' => 24,
            'days_per_week' => 7,
            'weeks_per_year' => 52,
        ]);

        $this->assertStringContainsString("This vendor's bid", $html);
        // The bid rate appears in the comparison column.
        $this->assertStringContainsString('$32.65', $html);
        $this->assertStringContainsString('$285,230.40', $html);
        // The 70% figure survives only as a labelled benchmark in the method
        // block; it is no longer the vendor column, so its annual total
        // ($64.29 × 8,736 = $561,637) appears nowhere.
        $this->assertStringContainsString('GASQ Benchmark Outsourced Rate', $html);
        $this->assertStringNotContainsString('561,63', $html);
        // Recovery is therefore a real figure, not the constant 30%.
        $this->assertStringNotContainsString('30% vs in-house', $html);
        $this->assertStringContainsString('64% vs in-house', $html);
    }

    public function test_a_bid_above_the_internal_cost_is_reported_as_additional_cost(): void
    {
        // Baseline $12 → internal TCO $44.09/hr = $385,165 a year. Bid is more.
        $html = $this->render(55.00, 480480.00, [
            'baseline_wage' => 12.00,
            'hours_per_day' => 24,
            'days_per_week' => 7,
            'weeks_per_year' => 52,
        ]);

        $this->assertStringContainsString('Additional Cost vs In-House', $html);
        $this->assertStringContainsString('No recovery at this bid', $html);
        $this->assertStringContainsString('above in-house', $html);
    }

    public function test_without_a_buyer_baseline_no_comparison_is_invented(): void
    {
        // The baseline used to be back-derived from the vendor's own bid, which
        // made the in-house column a restatement of the bid.
        $html = $this->render(32.65, 285230.40, [
            'hours_per_day' => 24,
            'days_per_week' => 7,
            'weeks_per_year' => 52,
        ]);

        $this->assertStringContainsString('no baseline wage from the buyer', $html);
        $this->assertStringNotContainsString('Side-by-Side Annual Comparison', $html);
        $this->assertStringContainsString('no buyer baseline on file', $html);
        // The vendor's own pricing is still reported in full.
        $this->assertStringContainsString('$285,230.40', $html);
    }
}
