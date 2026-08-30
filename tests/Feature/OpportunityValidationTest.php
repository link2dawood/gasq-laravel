<?php

namespace Tests\Feature;

use App\Services\OpportunityValidator;
use App\Support\PricingComparison;
use Tests\TestCase;

/**
 * The review page as a procurement control point (review spec §2, §15, §16, P0-10/11/14).
 */
class OpportunityValidationTest extends TestCase
{
    private OpportunityValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new OpportunityValidator();
    }

    /** A questionnaire with everything present and internally consistent. */
    private function completeQuestionnaire(array $overrides = []): array
    {
        return array_merge([
            'service_types' => ['unarmed'],
            'duties_required' => ['access_control'],
            'hours_per_day' => 8,
            'days_per_week' => 5,
            'weeks_per_year' => 52,
            'staff_per_shift' => 1,
            'baseline_wage' => 20.00,
            'budget_approved_status' => 'approved',
            'approved_budget_amount' => 250000,
            'final_decision_maker' => 'yes',
            'approval_authority' => 'yes',
            'service_start_date' => '2026-10-01',
            'desired_contract_term' => '12 months',
            'selection_method' => 'sealed_price',
            'insurance_minimums_required' => 'yes',
        ], $overrides);
    }

    public function test_complete_opportunity_is_ready_for_release(): void
    {
        $result = $this->validator->validate($this->completeQuestionnaire());

        $this->assertSame(OpportunityValidator::STATUS_READY, $result['status']);
        $this->assertSame(0, $result['blocking_count']);
        $this->assertTrue($this->validator->isReadyForRelease($this->completeQuestionnaire()));
    }

    /** P0-3 — no baseline wage means no financial basis, so release is blocked. */
    public function test_missing_baseline_wage_blocks_release(): void
    {
        $q = $this->completeQuestionnaire(['baseline_wage' => null]);

        $this->assertFalse($this->validator->isReadyForRelease($q));
        $this->assertContains('baseline_wage', $this->blockingKeys($q));
    }

    /** P0-4 — speculative opportunities must not reach the vendor network (§17). */
    public function test_unconfirmed_budget_blocks_release(): void
    {
        $this->assertFalse($this->validator->isReadyForRelease(
            $this->completeQuestionnaire(['budget_approved_status' => 'unknown'])
        ));

        $this->assertFalse($this->validator->isReadyForRelease(
            $this->completeQuestionnaire(['approved_budget_amount' => 0])
        ));
    }

    /** P0-5 — purchasing authority must be confirmed (§18). */
    public function test_missing_purchasing_authority_blocks_release(): void
    {
        $q = $this->completeQuestionnaire(['approval_authority' => null]);

        $this->assertFalse($this->validator->isReadyForRelease($q));
        $this->assertContains('decision_maker', $this->blockingKeys($q));
    }

    /** P0-6 — coverage must be present and numeric. */
    public function test_incomplete_coverage_blocks_release(): void
    {
        $q = $this->completeQuestionnaire(['hours_per_day' => 0]);

        $this->assertFalse($this->validator->isReadyForRelease($q));
        $this->assertContains('coverage', $this->blockingKeys($q));
    }

    /**
     * §7 — post hours are not the same as workforce capacity. 24/7 single-officer
     * coverage warns, but does not block: it may still be a legitimate opportunity.
     */
    public function test_unsustainable_coverage_warns_without_blocking(): void
    {
        $q = $this->completeQuestionnaire(['hours_per_day' => 24, 'days_per_week' => 7]);
        $result = $this->validator->validate($q);

        $this->assertSame(OpportunityValidator::STATUS_READY, $result['status']);
        $this->assertSame(1, $result['warning_count']);

        $mathCheck = collect($result['checks'])->firstWhere('key', 'coverage_math');
        $this->assertFalse($mathCheck['passed']);
        $this->assertFalse($mathCheck['blocking']);
    }

    // ---- Price Variance vs Capital Recovery (§15, §16, RULE 3/4/5) ----

    /** RULE 4 — a vendor-to-vendor delta is variance, never savings. */
    public function test_price_variance_is_never_labelled_savings(): void
    {
        $variance = PricingComparison::priceVariance(40.00, 35.00);

        $this->assertSame('Price Variance', $variance['label']);
        $this->assertSame(5.00, $variance['amount']);
        $this->assertFalse($variance['is_savings']);
        $this->assertStringNotContainsStringIgnoringCase('savings', $variance['label']);
    }

    /** RULE 5 — capital recovery needs the buyer's own cost as the benchmark. */
    public function test_capital_recovery_requires_buyer_side_comparison(): void
    {
        $recovery = PricingComparison::capitalRecovery(
            trueCostToProtect: 58.40,
            qualifiedOutsourcedCost: 43.00,
            annualHours: 8736,
        );

        $this->assertNotNull($recovery);
        $this->assertSame('Capital Recovery Opportunity', $recovery['label']);
        $this->assertSame(15.40, $recovery['hourly']);
        $this->assertSame(134534.40, $recovery['annual']);
    }

    /** Without a buyer benchmark there is no claim to make — null, not a plausible number. */
    public function test_capital_recovery_refuses_without_true_cost(): void
    {
        $this->assertNull(PricingComparison::capitalRecovery(0.0, 43.00, 8736));
    }

    /** Outsourcing costing more is not negative recovery — it is no recovery. */
    public function test_capital_recovery_null_when_outsourcing_costs_more(): void
    {
        $this->assertNull(PricingComparison::capitalRecovery(40.00, 46.00, 8736));
    }

    /** @return list<string> */
    private function blockingKeys(array $q): array
    {
        return collect($this->validator->validate($q)['checks'])
            ->filter(fn ($c) => $c['blocking'] && ! $c['passed'])
            ->pluck('key')
            ->all();
    }
}
