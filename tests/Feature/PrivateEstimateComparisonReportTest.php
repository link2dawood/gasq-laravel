<?php

namespace Tests\Feature;

use App\Models\EstimatePricing;
use App\Models\EstimateScope;
use App\Models\PrivateEstimate;
use App\Models\User;
use App\Services\PrivateEstimate\EstimateComparisonReport;
use App\Services\PrivateEstimate\EstimateInvitationService;
use App\Services\PrivateEstimate\PrivateEstimateService;
use App\Support\PrivateEstimate as Flow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Session\Middleware\StartSession;
use RuntimeException;
use Tests\TestCase;

/**
 * The side-by-side comparison report: the buyer-facing deliverable of the
 * private estimate flow.
 *
 * What these tests pin down, because the generic calculators get it wrong:
 *   - the vendor column is the platform-calculated rate, never internal cost
 *     times a discount factor, so capital recovery is a real figure per deal
 *     rather than a constant 30% and a constant payback period;
 *   - the internal column says whether it is the buyer's stated cost or the
 *     GASQ model;
 *   - the totals reconcile against the budget amount the buyer had approved.
 */
class PrivateEstimateComparisonReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    /** nda_accepted_at and phone_verified keep the global onboarding middleware out of the way. */
    private function vendor(): User
    {
        return User::factory()->create([
            'user_type' => 'vendor',
            'company' => 'ABC Security',
            'nda_accepted_at' => now(),
            'phone_verified' => true,
        ]);
    }

    /**
     * A priced estimate: 24/7 single post, 8,736 annual hours, scope approved by
     * both sides, and a platform rate of $30/hr unless overridden.
     *
     * @param  array<string, mixed>  $estimateAttributes
     * @param  array<string, mixed>  $pricingAttributes
     * @param  array<string, mixed>  $scopeAttributes
     */
    private function pricedEstimate(
        array $estimateAttributes = [],
        array $pricingAttributes = [],
        array $scopeAttributes = [],
        ?User $vendor = null,
    ): PrivateEstimate {
        $estimate = app(PrivateEstimateService::class)->create($vendor ?: $this->vendor(), array_merge([
            'company_name' => 'ABC Properties',
            'buyer_name' => 'Jane Smith',
            'buyer_email' => 'jane@abcproperties.test',
            'site_name' => 'Main Entrance',
        ], $estimateAttributes));

        EstimateScope::create(array_merge([
            'private_estimate_id' => $estimate->id,
            'version' => 1,
            'service_type' => 'unarmed',
            'posts' => 1,
            'locations' => 1,
            'hours_per_day' => 24,
            'days_per_week' => 7,
            'weeks_per_year' => 52,
            'baseline_wage' => 25.00,
            'buyer_approved_at' => now(),
            'vendor_approved_at' => now(),
        ], $scopeAttributes));

        EstimatePricing::create(array_merge([
            'private_estimate_id' => $estimate->id,
            'scope_version' => 1,
            'base_wage' => 21.00,
            'employer_burden' => 6.30,
            'cost_to_deliver' => 27.30,
            'profit_margin' => 0.10,
            'final_bill_rate' => 30.00,
            'calculation_version' => 'v24',
        ], $pricingAttributes));

        $estimate->forceFill(['current_scope_version' => 1, 'priced_at' => now()])->save();

        return $estimate->fresh();
    }

    private function report(PrivateEstimate $estimate): array
    {
        return app(EstimateComparisonReport::class)->build($estimate);
    }

    public function test_the_vendor_column_is_the_platform_calculated_rate(): void
    {
        $r = $this->report($this->pricedEstimate());

        $this->assertSame(30.00, round($r['vendor']['hourly'], 2));
        $this->assertSame('platform', $r['vendor']['source']);
        // 8,736 annual hours × $30.
        $this->assertSame(262080.0, round($r['vendor']['annual'], 2));
        $this->assertSame(8736.0, round((float) $r['coverage']['annualHours'], 2));

        // Not the internal column times the 0.70 discount factor the generic
        // calculators apply.
        $modelled = $r['internal']['hourly'] * (float) config('budget_calculator.vendor_discount_factor');
        $this->assertNotEqualsWithDelta($modelled, $r['vendor']['hourly'], 0.01);
    }

    public function test_a_stored_total_cannot_move_the_quote_away_from_the_formula(): void
    {
        // A total written straight into the pricing row would be a way around
        // the calculated rate. The report prices the agreed hours at the
        // calculated rate regardless, and flags the disagreement.
        $r = $this->report($this->pricedEstimate([], [
            'final_bill_rate' => 30.00,
            'annual_cost' => 180000.00,
        ]));

        $this->assertSame(262080.0, round($r['vendor']['annual'], 2));
        $this->assertSame(180000.0, round((float) $r['vendor']['storedAnnual'], 2));
        $this->assertTrue($r['vendor']['storedAnnualDiffers']);
        // The period figures follow the formula too.
        $this->assertSame(21840.0, round($r['vendor']['monthly'], 2));
        $this->assertSame(5040.0, round($r['vendor']['weekly'], 2));
    }

    public function test_a_stored_total_that_matches_the_formula_is_not_flagged(): void
    {
        $r = $this->report($this->pricedEstimate([], [
            'final_bill_rate' => 30.00,
            'annual_cost' => 262080.00,
        ]));

        $this->assertFalse($r['vendor']['storedAnnualDiffers']);
    }

    public function test_capital_recovery_moves_with_the_agreed_rate(): void
    {
        $cheap = $this->report($this->pricedEstimate([], ['final_bill_rate' => 28.00]));
        $dear = $this->report($this->pricedEstimate([], ['final_bill_rate' => 38.00]));

        $this->assertGreaterThan($dear['recovery']['pct'], $cheap['recovery']['pct']);
        $this->assertNotEqualsWithDelta(30.0, $cheap['recovery']['pct'], 0.5);
        $this->assertNotEqualsWithDelta(30.0, $dear['recovery']['pct'], 0.5);
        // Nor is payback the constant the master estimate prints.
        $this->assertNotEqualsWithDelta($cheap['recovery']['paybackMonths'], $dear['recovery']['paybackMonths'], 0.1);
    }

    public function test_a_rate_above_the_in_house_cost_is_reported_as_additional_cost(): void
    {
        $r = $this->report($this->pricedEstimate(
            ['current_bill_rate' => 30.00],
            ['final_bill_rate' => 42.00],
        ));

        $this->assertFalse($r['recovery']['positive']);
        $this->assertLessThan(0, $r['recovery']['annual']);
        // 12 hours × ... the figure is not floored at zero, so the buyer sees it.
        $this->assertSame(-104832.0, round($r['recovery']['annual'], 2));
    }

    public function test_the_internal_column_uses_the_buyers_stated_cost_when_given(): void
    {
        $r = $this->report($this->pricedEstimate(['current_bill_rate' => 41.50]));

        $this->assertSame('stated', $r['internal']['source']);
        $this->assertSame(41.50, round($r['internal']['hourly'], 2));
        $this->assertSame(362544.0, round($r['internal']['annual'], 2));
        $this->assertStringContainsString('current cost', $r['internal']['label']);
    }

    public function test_a_stated_annual_cost_alone_is_reduced_to_an_hourly_rate(): void
    {
        $r = $this->report($this->pricedEstimate(['current_annual_cost' => 350000.00]));

        $this->assertSame('stated', $r['internal']['source']);
        $this->assertSame(350000.0, round($r['internal']['annual'], 2));
        $this->assertSame(40.06, round($r['internal']['hourly'], 2));
    }

    public function test_the_internal_column_falls_back_to_the_modelled_should_cost(): void
    {
        $r = $this->report($this->pricedEstimate());

        $this->assertSame('modelled', $r['internal']['source']);
        // $25 ÷ 0.70 × 3,744 ÷ 1,456 = $91.84/hr.
        $this->assertSame(91.84, round($r['internal']['hourly'], 2));
        $this->assertStringContainsString('did not state a current cost', $r['internal']['basis']);
    }

    public function test_the_budget_is_converted_from_its_basis_and_reconciled(): void
    {
        $r = $this->report($this->pricedEstimate([
            'budget_status' => 'approved',
            'budget_amount' => 25000.00,
            'budget_basis' => 'monthly',
            'budget_approved_at' => now(),
        ]));

        $this->assertTrue($r['budget']['known']);
        $this->assertTrue($r['budget']['approved']);
        $this->assertSame(300000.0, round($r['budget']['annual'], 2));
        // $300,000 approved against $262,080 agreed.
        $this->assertSame(37920.0, round((float) $r['budget']['variance'], 2));
        $this->assertTrue($r['budget']['withinBudget']);
        $this->assertTrue($r['budget']['reconciles']);
        $this->assertStringContainsString('within the approved budget', $r['budget']['statement']);
    }

    public function test_an_estimate_over_the_approved_budget_says_so_on_its_face(): void
    {
        $r = $this->report($this->pricedEstimate([
            'budget_status' => 'approved',
            'budget_amount' => 200000.00,
            'budget_basis' => 'annual',
        ]));

        $this->assertFalse($r['budget']['withinBudget']);
        $this->assertFalse($r['budget']['reconciles']);
        $this->assertSame(-62080.0, round((float) $r['budget']['variance'], 2));
        $this->assertStringContainsString('exceeds the approved budget', $r['budget']['statement']);
    }

    public function test_an_hourly_budget_is_scaled_by_the_annual_hours(): void
    {
        $r = $this->report($this->pricedEstimate([
            'budget_status' => 'approved',
            'budget_amount' => 32.00,
            'budget_basis' => 'hourly',
        ]));

        $this->assertSame(279552.0, round($r['budget']['annual'], 2));
        $this->assertTrue($r['budget']['withinBudget']);
    }

    public function test_no_recorded_budget_is_stated_rather_than_assumed(): void
    {
        $r = $this->report($this->pricedEstimate());

        $this->assertFalse($r['budget']['known']);
        $this->assertNull($r['budget']['variance']);
        $this->assertFalse($r['budget']['reconciles']);
        $this->assertStringContainsString('No approved budget amount', $r['budget']['statement']);
    }

    public function test_the_rate_is_agreed_only_when_both_parties_approved_the_scope(): void
    {
        $agreed = $this->report($this->pricedEstimate());
        $this->assertTrue($agreed['agreement']['agreed']);
        $this->assertStringContainsString('approved by both', $agreed['agreement']['statement']);

        $provisional = $this->report($this->pricedEstimate([], [], ['vendor_approved_at' => null]));
        $this->assertFalse($provisional['agreement']['agreed']);
        $this->assertStringContainsString('provisional', $provisional['agreement']['statement']);
    }

    public function test_an_unpriced_estimate_has_no_report(): void
    {
        $estimate = $this->pricedEstimate([], ['final_bill_rate' => 0]);
        $service = app(EstimateComparisonReport::class);

        $this->assertFalse($service->ready($estimate));

        $this->expectException(RuntimeException::class);
        $service->build($estimate);
    }

    public function test_the_workforce_requirement_covers_the_whole_week(): void
    {
        // 24 × 7 = 168 weekly hours ÷ 28 billable = 6 FTEs.
        $r = $this->report($this->pricedEstimate());
        $this->assertSame(6, $r['coverage']['ftesRequired']);

        // Two posts double the hours and the workforce.
        $two = $this->report($this->pricedEstimate([], [], ['posts' => 2]));
        $this->assertSame(17472.0, round((float) $two['coverage']['annualHours'], 2));
        $this->assertSame(12, $two['coverage']['ftesRequired']);
    }

    public function test_the_owning_vendor_can_preview_and_download_the_report(): void
    {
        $vendor = $this->vendor();
        $estimate = $this->pricedEstimate([], [], [], $vendor);

        $this->actingAs($vendor)
            ->get(route('private-estimates.report.show', $estimate))
            ->assertOk()
            ->assertSee('Agreed vendor annual cost', false)
            ->assertSee($estimate->public_id);

        $response = $this->actingAs($vendor)
            ->post(route('private-estimates.report.download', $estimate), ['pdf_password' => 'Opens-2026']);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        // Password protected: the file is encrypted, so the page text is not readable in the bytes.
        $this->assertStringNotContainsString('Agreed vendor cost to protect', $response->getContent());

        $this->assertDatabaseHas('estimate_activity', [
            'private_estimate_id' => $estimate->id,
            'event_type' => Flow::EVENT_PDF_GENERATED,
        ]);
    }

    public function test_another_vendor_cannot_reach_the_report(): void
    {
        $estimate = $this->pricedEstimate();
        $other = $this->vendor();

        $this->actingAs($other)
            ->get(route('private-estimates.report.show', $estimate))
            ->assertNotFound();
    }

    public function test_the_buyer_sees_the_comparison_once_the_estimate_is_released(): void
    {
        $estimate = $this->pricedEstimate();
        ['token' => $token] = app(EstimateInvitationService::class)->issue($estimate, 7);

        // Priced but not yet released: the portal shows status only.
        $this->withSession(['private_estimate_verified.'.$estimate->id => now()->addHour()->timestamp])
            ->get(route('private-estimates.buyer.show', $token))
            ->assertOk()
            ->assertSee("You're verified", false)
            ->assertDontSee('Agreed vendor annual cost', false);

        $estimate->forceFill(['status' => Flow::ESTIMATE_READY])->save();

        $this->withSession(['private_estimate_verified.'.$estimate->id => now()->addHour()->timestamp])
            ->get(route('private-estimates.buyer.show', $token))
            ->assertOk()
            ->assertSee('Agreed vendor annual cost', false)
            ->assertSee('Operational capital recovered', false);

        // Seeing it marks the estimate viewed for the vendor's timeline.
        $this->assertSame(Flow::ESTIMATE_VIEWED, $estimate->fresh()->status);
        $this->assertNotNull($estimate->fresh()->first_viewed_at);
    }

    public function test_an_expired_estimate_shows_the_buyer_no_figures(): void
    {
        $estimate = $this->pricedEstimate();
        $estimate->forceFill(['status' => Flow::ESTIMATE_READY, 'valid_until' => now()->subDay()])->save();
        ['token' => $token] = app(EstimateInvitationService::class)->issue($estimate, 7);

        $this->withSession(['private_estimate_verified.'.$estimate->id => now()->addHour()->timestamp])
            ->get(route('private-estimates.buyer.show', $token))
            ->assertOk()
            ->assertDontSee('Agreed vendor annual cost', false);
    }
}
