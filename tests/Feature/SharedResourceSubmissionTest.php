<?php

namespace Tests\Feature;

use App\Models\BillRateBreakdown;
use App\Models\User;
use App\Models\VendorOperatingVolume;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The vendor-facing Shared Resource flow (review spec §10-13).
 *
 * Covers the gate that matters: a vendor cannot self-select Shared Resource pricing, and
 * a breakdown that does not reconcile is blocked rather than filed.
 */
class SharedResourceSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ValidateCsrfToken::class]);
    }

    private function vendor(bool $verifiedPhone = true): User
    {
        $vendor = User::create([
            'name' => 'Vendor',
            'email' => 'v' . uniqid() . '@example.test',
            'password' => Hash::make('password'),
            'user_type' => 'vendor',
            'nda_accepted_at' => now(),
        ]);

        if ($verifiedPhone) {
            $vendor->forceFill(['phone_verified' => true])->save();
        }

        return $vendor;
    }

    private function verifiedVolume(User $vendor, float $hours = 1200): VendorOperatingVolume
    {
        return VendorOperatingVolume::create([
            'vendor_id' => $vendor->id,
            'weekly_billable_hours' => $hours,
            'account_count' => 6,
            'status' => VendorOperatingVolume::STATUS_ELIGIBLE_FOR_REVIEW,
            'verified_at' => now(),
        ]);
    }

    /** Line items summing to exactly $38.00 (the worked example in the spec). */
    private function balancedLines(): array
    {
        return [
            ['category' => 'direct_labor', 'label' => 'Officer wage', 'amount' => 20.00, 'resource_classification' => 'direct_labor'],
            ['category' => 'employer_costs', 'label' => 'Employer costs', 'amount' => 6.50, 'resource_classification' => 'dedicated'],
            ['category' => 'workforce_maintenance', 'label' => 'Workforce maintenance', 'amount' => 3.75, 'resource_classification' => 'dedicated'],
            ['category' => 'supervision', 'label' => 'Supervision', 'amount' => 0.58, 'resource_classification' => 'shared', 'allocation_method' => '$3.50/hr across 6 accounts', 'shared_across_accounts' => 6],
            ['category' => 'administrative', 'label' => 'Administration', 'amount' => 1.20, 'resource_classification' => 'shared', 'allocation_method' => 'Allocated by account hours', 'shared_across_accounts' => 6],
            ['category' => 'operating', 'label' => 'Training / uniform / tech', 'amount' => 1.10, 'resource_classification' => 'dedicated'],
            ['category' => 'insurance', 'label' => 'Insurance', 'amount' => 1.25, 'resource_classification' => 'dedicated'],
            ['category' => 'profit', 'label' => 'Profit', 'amount' => 3.62, 'resource_classification' => 'dedicated'],
        ];
    }

    public function test_vendor_can_view_shared_resource_status(): void
    {
        $vendor = $this->vendor();

        $this->actingAs($vendor)
            ->get(route('shared-resource.index'))
            ->assertOk()
            ->assertSee('Verified Shared Resource Rate', false);
    }

    /** §11 — a vendor cannot verify its own scale; submissions land as pending. */
    public function test_submitted_volume_is_pending_not_verified(): void
    {
        $vendor = $this->vendor();

        $this->actingAs($vendor)->post(route('shared-resource.volume.store'), [
            'weekly_billable_hours' => 1500,
            'account_count' => 8,
        ])->assertRedirect(route('shared-resource.index'));

        $volume = VendorOperatingVolume::where('vendor_id', $vendor->id)->first();

        $this->assertNotNull($volume);
        $this->assertSame(VendorOperatingVolume::STATUS_PENDING, $volume->status);
        $this->assertNull($volume->verified_at);
        $this->assertFalse($volume->isEligibleForReview());
    }

    /** Below the threshold, the breakdown builder is not reachable at all. */
    public function test_ineligible_vendor_cannot_reach_breakdown_builder(): void
    {
        $vendor = $this->vendor();
        $this->verifiedVolume($vendor, 800);

        $this->actingAs($vendor)
            ->get(route('shared-resource.breakdown.edit'))
            ->assertRedirect(route('shared-resource.index'));
    }

    public function test_eligible_vendor_can_reach_breakdown_builder(): void
    {
        $vendor = $this->vendor();
        $this->verifiedVolume($vendor);

        $this->actingAs($vendor)
            ->get(route('shared-resource.breakdown.edit'))
            ->assertOk()
            ->assertSee('Line-Item Bill-Rate Breakdown', false);
    }

    /** §39 — a breakdown that does not reconcile is blocked, not accepted. */
    public function test_non_reconciling_breakdown_is_blocked(): void
    {
        $vendor = $this->vendor();
        $this->verifiedVolume($vendor);

        $response = $this->actingAs($vendor)->post(route('shared-resource.breakdown.store'), [
            'proposed_bill_rate' => 36.00, // lines total 38.00
            'certified' => 1,
            'lines' => $this->balancedLines(),
        ]);

        $response->assertRedirect(route('shared-resource.breakdown.edit'));
        $response->assertSessionHas('error');

        $breakdown = BillRateBreakdown::where('vendor_id', $vendor->id)->first();
        $this->assertFalse($breakdown->reconciles);
        $this->assertSame(BillRateBreakdown::STATUS_DRAFT, $breakdown->status);
    }

    public function test_balanced_breakdown_is_accepted_and_awaits_gasq_review(): void
    {
        $vendor = $this->vendor();
        $this->verifiedVolume($vendor);

        $this->actingAs($vendor)->post(route('shared-resource.breakdown.store'), [
            'proposed_bill_rate' => 38.00,
            'certified' => 1,
            'lines' => $this->balancedLines(),
        ])->assertRedirect(route('shared-resource.index'));

        $breakdown = BillRateBreakdown::where('vendor_id', $vendor->id)->first();

        $this->assertTrue($breakdown->reconciles);
        $this->assertTrue($breakdown->certified);
        $this->assertSame('38.00', (string) $breakdown->line_items_total);
        $this->assertCount(8, $breakdown->lineItems);

        // Submitted is not approved — GASQ still has to review it (RULE 15).
        $this->assertSame(BillRateBreakdown::STATUS_SUBMITTED, $breakdown->status);
    }

    /** RULE 14 — certification is mandatory. */
    public function test_uncertified_submission_is_rejected(): void
    {
        $vendor = $this->vendor();
        $this->verifiedVolume($vendor);

        $this->actingAs($vendor)->post(route('shared-resource.breakdown.store'), [
            'proposed_bill_rate' => 38.00,
            'lines' => $this->balancedLines(),
        ])->assertSessionHasErrors('certified');

        $this->assertSame(0, BillRateBreakdown::where('vendor_id', $vendor->id)->count());
    }

    /** An ineligible vendor cannot bypass the builder by posting directly. */
    public function test_ineligible_vendor_cannot_post_breakdown_directly(): void
    {
        $vendor = $this->vendor();
        $this->verifiedVolume($vendor, 800);

        $this->actingAs($vendor)->post(route('shared-resource.breakdown.store'), [
            'proposed_bill_rate' => 38.00,
            'certified' => 1,
            'lines' => $this->balancedLines(),
        ])->assertRedirect(route('shared-resource.index'));

        $this->assertSame(0, BillRateBreakdown::where('vendor_id', $vendor->id)->count());
    }

    /** Buyers have no business in the vendor pricing flow. */
    public function test_buyer_cannot_access_shared_resource_pages(): void
    {
        $buyer = User::create([
            'name' => 'Buyer',
            'email' => 'b' . uniqid() . '@example.test',
            'password' => Hash::make('password'),
            'user_type' => 'buyer',
            'nda_accepted_at' => now(),
        ]);
        $buyer->forceFill(['phone_verified' => true])->save();

        $this->actingAs($buyer)
            ->get(route('shared-resource.index'))
            ->assertRedirect();
    }
}
