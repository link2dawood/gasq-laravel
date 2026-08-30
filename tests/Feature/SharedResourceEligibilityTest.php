<?php

namespace Tests\Feature;

use App\Models\BillRateBreakdown;
use App\Models\BillRateLineItem;
use App\Models\User;
use App\Models\VendorOperatingVolume;
use App\Services\SharedResourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Acceptance scenarios A–E from spec §87, plus the rules they depend on.
 *
 * The rule under test throughout: 1,000 verified weekly billable hours earns the right to
 * APPLY for Shared Resource pricing — it never approves the rate (RULE 7/8).
 */
class SharedResourceEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private SharedResourceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SharedResourceService::class);
    }

    private function vendor(): User
    {
        return User::create([
            'name' => 'Test Vendor',
            'email' => 'vendor' . uniqid() . '@example.test',
            'password' => Hash::make('password'),
            'user_type' => 'vendor',
        ]);
    }

    private function volume(User $vendor, float $hours, string $status = VendorOperatingVolume::STATUS_ELIGIBLE_FOR_REVIEW, ?string $expiresAt = null): VendorOperatingVolume
    {
        return VendorOperatingVolume::create([
            'vendor_id' => $vendor->id,
            'weekly_billable_hours' => $hours,
            'account_count' => 6,
            'status' => $status,
            'verified_at' => now(),
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Breakdown whose line items sum to $38.00 (the worked example in spec §38).
     */
    private function breakdown(User $vendor, float $proposedRate = 38.00, bool $certified = true, string $status = BillRateBreakdown::STATUS_APPROVED): BillRateBreakdown
    {
        $breakdown = BillRateBreakdown::create([
            'vendor_id' => $vendor->id,
            'pricing_model' => BillRateBreakdown::MODEL_SHARED_RESOURCE,
            'proposed_bill_rate' => $proposedRate,
            'status' => $status,
            'certified' => $certified,
            'certified_at' => $certified ? now() : null,
        ]);

        $lines = [
            ['direct_labor', 'Officer wage', 20.00, BillRateLineItem::CLASS_DIRECT_LABOR, null, null],
            ['employer_costs', 'Employer costs', 6.50, BillRateLineItem::CLASS_DEDICATED, null, null],
            ['workforce_maintenance', 'Workforce maintenance', 3.75, BillRateLineItem::CLASS_DEDICATED, null, null],
            ['supervision', 'Supervision', 0.58, BillRateLineItem::CLASS_SHARED, 'Dedicated $3.50/hr spread across 6 qualifying accounts', 6],
            ['administrative', 'Administration', 1.20, BillRateLineItem::CLASS_SHARED, 'Company admin allocated by account hours', 6],
            ['operating', 'Training / uniform / technology', 1.10, BillRateLineItem::CLASS_DEDICATED, null, null],
            ['insurance', 'Insurance', 1.25, BillRateLineItem::CLASS_DEDICATED, null, null],
            ['profit', 'Profit', 3.62, BillRateLineItem::CLASS_DEDICATED, null, null],
        ];

        foreach ($lines as $i => [$category, $label, $amount, $classification, $method, $accounts]) {
            BillRateLineItem::create([
                'bill_rate_breakdown_id' => $breakdown->id,
                'category' => $category,
                'label' => $label,
                'amount' => $amount,
                'resource_classification' => $classification,
                'allocation_method' => $method,
                'shared_across_accounts' => $accounts,
                'sort_order' => $i,
            ]);
        }

        return $breakdown->fresh('lineItems');
    }

    /** Scenario A — 800 hours: not eligible, cannot submit. */
    public function test_scenario_a_vendor_below_threshold_is_not_eligible(): void
    {
        $vendor = $this->vendor();
        $this->volume($vendor, 800, VendorOperatingVolume::STATUS_NOT_ELIGIBLE);

        $this->assertFalse($this->service->isEligibleForReview($vendor));
        $this->assertSame(SharedResourceService::STATUS_NOT_ELIGIBLE, $this->service->statusFor($vendor));
        $this->assertFalse($this->service->canSubmitSharedResourcePricing($vendor, null));
    }

    /** Scenario B — 1,200 hours but no breakdown: eligible for review, NOT approved. */
    public function test_scenario_b_hours_alone_do_not_approve_the_rate(): void
    {
        $vendor = $this->vendor();
        $this->volume($vendor, 1200);

        $this->assertTrue($this->service->isEligibleForReview($vendor));
        $this->assertSame(SharedResourceService::STATUS_ELIGIBLE_FOR_REVIEW, $this->service->statusFor($vendor));

        // The critical assertion: eligible, but submission is still blocked.
        $this->assertFalse($this->service->canSubmitSharedResourcePricing($vendor, null));
        $this->assertContains(
            'A complete line-item bill-rate breakdown is required for Shared Resource pricing.',
            $this->service->blockingReasons($vendor, null)
        );
    }

    /** Scenario C — 1,200 hours, breakdown does not reconcile: blocked. */
    public function test_scenario_c_non_reconciling_breakdown_is_blocked(): void
    {
        $vendor = $this->vendor();
        $this->volume($vendor, 1200);

        // Line items sum to $38.00 but the vendor proposes $36.00.
        $breakdown = $this->breakdown($vendor, proposedRate: 36.00);

        $this->assertFalse($breakdown->reconcilesWithProposedRate());
        $this->assertFalse($this->service->canSubmitSharedResourcePricing($vendor, $breakdown));

        $reasons = implode(' ', $this->service->blockingReasons($vendor, $breakdown));
        $this->assertStringContainsString('does not reconcile', $reasons);
    }

    /** Scenario D — 1,200 hours, complete reconciling approved breakdown: allowed. */
    public function test_scenario_d_fully_validated_vendor_may_submit(): void
    {
        $vendor = $this->vendor();
        $this->volume($vendor, 1200);
        $breakdown = $this->breakdown($vendor);

        $this->assertTrue($breakdown->reconcilesWithProposedRate());
        $this->assertSame([], $this->service->blockingReasons($vendor, $breakdown));
        $this->assertTrue($this->service->canSubmitSharedResourcePricing($vendor, $breakdown));
        $this->assertSame(SharedResourceService::STATUS_APPROVED, $this->service->statusFor($vendor, $breakdown));
    }

    /** Scenario E — verification expired: submission disabled until reverified. */
    public function test_scenario_e_expired_verification_blocks_submission(): void
    {
        $vendor = $this->vendor();
        $this->volume($vendor, 1200, expiresAt: now()->subDay()->toDateTimeString());
        $breakdown = $this->breakdown($vendor);

        $this->assertSame(SharedResourceService::STATUS_EXPIRED, $this->service->statusFor($vendor, $breakdown));
        $this->assertFalse($this->service->canSubmitSharedResourcePricing($vendor, $breakdown));
        $this->assertContains(
            'Operating-volume verification has expired and must be renewed.',
            $this->service->blockingReasons($vendor, $breakdown)
        );
    }

    /** RULE 11 — a shared line with no allocation method cannot be validated. */
    public function test_shared_line_without_allocation_method_is_blocked(): void
    {
        $vendor = $this->vendor();
        $this->volume($vendor, 1200);
        $breakdown = $this->breakdown($vendor);

        $breakdown->lineItems()->where('category', 'supervision')->update([
            'allocation_method' => null,
            'shared_across_accounts' => null,
        ]);
        $breakdown = $breakdown->fresh('lineItems');

        $reasons = implode(' ', $this->service->blockingReasons($vendor, $breakdown));
        $this->assertStringContainsString('allocation method', $reasons);
        $this->assertFalse($this->service->canSubmitSharedResourcePricing($vendor, $breakdown));
    }

    /** RULE 14 — missing certification blocks submission. */
    public function test_uncertified_breakdown_is_blocked(): void
    {
        $vendor = $this->vendor();
        $this->volume($vendor, 1200);
        $breakdown = $this->breakdown($vendor, certified: false);

        $this->assertContains(
            'The vendor bill-rate certification has not been completed.',
            $this->service->blockingReasons($vendor, $breakdown)
        );
    }

    /** RULE 15 — GASQ approval is required even when everything else is in order. */
    public function test_unapproved_breakdown_is_blocked(): void
    {
        $vendor = $this->vendor();
        $this->volume($vendor, 1200);
        $breakdown = $this->breakdown($vendor, status: BillRateBreakdown::STATUS_SUBMITTED);

        $this->assertContains(
            'GASQ has not approved this Shared Resource bill-rate structure.',
            $this->service->blockingReasons($vendor, $breakdown)
        );
        $this->assertSame(
            SharedResourceService::STATUS_FINANCIAL_REVIEW_PENDING,
            $this->service->statusFor($vendor, $breakdown)
        );
    }

    /** A vendor who has never submitted anything is simply "not verified". */
    public function test_vendor_with_no_submission_is_not_verified(): void
    {
        $vendor = $this->vendor();

        $this->assertSame(SharedResourceService::STATUS_NOT_VERIFIED, $this->service->statusFor($vendor));
        $this->assertFalse($this->service->canSubmitSharedResourcePricing($vendor, null));
    }

    /** refreshReconciliation() recomputes from stored line items, not client input. */
    public function test_refresh_reconciliation_recomputes_from_stored_lines(): void
    {
        $vendor = $this->vendor();
        $this->volume($vendor, 1200);
        $breakdown = $this->breakdown($vendor);

        // Claim a false total; the service must overwrite it from the real lines.
        $breakdown->forceFill(['line_items_total' => 999.99, 'reconciles' => false])->save();

        $refreshed = $this->service->refreshReconciliation($breakdown);

        $this->assertSame('38.00', (string) $refreshed->line_items_total);
        $this->assertTrue($refreshed->reconciles);
    }
}
