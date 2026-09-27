<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\User;
use App\Models\VendorEngagement;
use App\Models\VendorEngagementStage;
use App\Models\VendorOpportunity;
use App\Models\VendorOpportunityInvitation;
use App\Models\VendorProfile;
use App\Services\VendorAccess\VendorEngagementService;
use App\Support\VendorEngagement as Flow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * GASQ Vendor Access, phase one: who may open an invitation, and the rule that
 * keeps the sequence honest — qualifications first, price last.
 */
class VendorAccessTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(bool $verified = true): User
    {
        // nda_accepted_at and phone_verified keep the global onboarding
        // middleware out of the way; this suite is about access, not onboarding.
        $user = User::factory()->create([
            'user_type' => 'vendor',
            'company' => 'ABC Security',
            'nda_accepted_at' => now(),
            'phone_verified' => true,
        ]);
        VendorProfile::create([
            'user_id' => $user->id,
            'company_name' => 'ABC Security',
            'is_verified' => $verified,
        ]);

        return $user->fresh();
    }

    private function invitation(?User $vendor = null, array $overrides = []): VendorOpportunityInvitation
    {
        $vendor ??= $this->vendor();
        $buyer = User::factory()->create(['user_type' => 'buyer', 'nda_accepted_at' => now()]);
        $job = JobPosting::create([
            'user_id' => $buyer->id,
            'title' => 'Overnight Site Coverage',
            'description' => 'Single post, overnight coverage.',
            'location' => 'Atlanta, GA',
            'status' => 'open',
        ]);
        $opportunity = VendorOpportunity::create([
            'job_posting_id' => $job->id,
            'lead_tier' => 'a',
            'status' => 'sent',
            'decision_maker_verified' => true,
        ]);

        return VendorOpportunityInvitation::create(array_merge([
            'vendor_opportunity_id' => $opportunity->id,
            'vendor_id' => $vendor->id,
            'invite_key' => (string) Str::uuid(),
            'status' => VendorOpportunityInvitation::STATUS_NEW,
            'expires_at' => now()->addDays(7),
        ], $overrides));
    }

    public function test_an_invited_vendor_reaches_the_first_stage(): void
    {
        $vendor = $this->vendor();
        $invitation = $this->invitation($vendor);

        $this->actingAs($vendor)
            ->get(route('vendor-access.enter', $invitation))
            ->assertRedirect(route('vendor-access.stage', ['invitation' => $invitation, 'stage' => Flow::OPPORTUNITY]));

        $engagement = VendorEngagement::first();
        $this->assertNotNull($engagement);
        $this->assertSame(Flow::OPPORTUNITY, $engagement->stage);
        $this->assertCount(10, $engagement->stages);
        $this->assertSame(VendorEngagementStage::AVAILABLE, $engagement->stageRecord(Flow::OPPORTUNITY)->status);
        $this->assertSame(VendorEngagementStage::LOCKED, $engagement->stageRecord(Flow::PRICE)->status);
        $this->assertDatabaseHas('vendor_engagement_activity', ['event_type' => Flow::EVENT_ENGAGEMENT_STARTED]);
    }

    public function test_another_vendor_cannot_open_the_invitation(): void
    {
        $invitation = $this->invitation();
        $stranger = $this->vendor();

        $this->actingAs($stranger)
            ->get(route('vendor-access.enter', $invitation))
            ->assertOk()
            ->assertSee('This opportunity is not available to you');

        $this->assertSame(0, VendorEngagement::count());
    }

    public function test_an_unverified_vendor_is_turned_away(): void
    {
        $vendor = $this->vendor(verified: false);
        $invitation = $this->invitation($vendor);

        $this->actingAs($vendor)
            ->get(route('vendor-access.enter', $invitation))
            ->assertOk()
            ->assertSee('Verification required');
    }

    public function test_expired_revoked_and_exhausted_invitations_are_refused(): void
    {
        $vendor = $this->vendor();

        $expired = $this->invitation($vendor, ['expires_at' => now()->subDay()]);
        $this->actingAs($vendor)->get(route('vendor-access.enter', $expired))
            ->assertOk()->assertSee('This invitation has expired');

        $revoked = $this->invitation($this->vendor());
        $revoked->forceFill(['revoked_at' => now(), 'revoked_reason' => 'Withdrawn'])->save();
        $this->actingAs($revoked->vendor)->get(route('vendor-access.enter', $revoked))
            ->assertOk()->assertSee('This invitation was withdrawn');

        $used = $this->invitation($this->vendor(), ['max_uses' => 1, 'use_count' => 1]);
        $this->actingAs($used->vendor)->get(route('vendor-access.enter', $used))
            ->assertOk()->assertSee('This invitation has been used');
    }

    public function test_opening_the_link_counts_against_the_use_limit(): void
    {
        $vendor = $this->vendor();
        $invitation = $this->invitation($vendor, ['max_uses' => 2]);

        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation));
        $this->assertSame(1, (int) $invitation->fresh()->use_count);
        $this->assertNotNull($invitation->fresh()->last_used_at);

        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation));
        $this->assertSame(2, (int) $invitation->fresh()->use_count);

        // The third visit is refused, and no further use is recorded.
        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation))
            ->assertSee('This invitation has been used');
        $this->assertSame(2, (int) $invitation->fresh()->use_count);
    }

    public function test_price_cannot_be_reached_before_the_stages_before_it(): void
    {
        $vendor = $this->vendor();
        $invitation = $this->invitation($vendor);
        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation));

        // Typing the price URL sends the vendor back to where they actually are.
        $this->actingAs($vendor)
            ->get(route('vendor-access.stage', ['invitation' => $invitation, 'stage' => Flow::PRICE]))
            ->assertRedirect(route('vendor-access.stage', ['invitation' => $invitation, 'stage' => Flow::OPPORTUNITY]))
            ->assertSessionHas('info');

        $engagement = VendorEngagement::first();
        $service = app(VendorEngagementService::class);
        $this->assertFalse($service->canEnter($engagement, Flow::PRICE));
        $this->assertFalse($service->canEnter($engagement, Flow::SOLUTION));
        $this->assertTrue($service->canEnter($engagement, Flow::OPPORTUNITY));
    }

    public function test_the_sequence_opens_one_stage_at_a_time(): void
    {
        $vendor = $this->vendor();
        $invitation = $this->invitation($vendor);
        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation));

        $this->actingAs($vendor)->post(route('vendor-access.reviewed', $invitation))
            ->assertRedirect(route('vendor-access.stage', ['invitation' => $invitation, 'stage' => Flow::RESPONSE]));

        $this->actingAs($vendor)->post(route('vendor-access.accept', $invitation))
            ->assertRedirect(route('vendor-access.stage', ['invitation' => $invitation, 'stage' => Flow::QUALIFY]));

        $engagement = VendorEngagement::first();
        $this->assertNotNull($engagement->accepted_at);
        $this->assertSame(VendorEngagementStage::COMPLETE, $engagement->stageRecord(Flow::RESPONSE)->status);
        $this->assertSame(VendorEngagementStage::AVAILABLE, $engagement->stageRecord(Flow::QUALIFY)->status);
        // Meet stays shut until qualification is confirmed.
        $this->assertSame(VendorEngagementStage::LOCKED, $engagement->stageRecord(Flow::MEET)->status);

        $this->actingAs($vendor)->post(route('vendor-access.qualify', $invitation), ['confirm' => 1])
            ->assertRedirect(route('vendor-access.stage', ['invitation' => $invitation, 'stage' => Flow::MEET]));

        $this->assertSame(VendorEngagementStage::COMPLETE, $engagement->fresh()->stageRecord(Flow::QUALIFY)->status);
        $this->assertDatabaseHas('vendor_engagement_activity', ['event_type' => Flow::EVENT_STAGE_COMPLETED]);
    }

    public function test_qualification_requires_the_confirmation(): void
    {
        $vendor = $this->vendor();
        $invitation = $this->invitation($vendor);
        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation));
        $this->actingAs($vendor)->post(route('vendor-access.reviewed', $invitation));
        $this->actingAs($vendor)->post(route('vendor-access.accept', $invitation));

        $this->actingAs($vendor)->post(route('vendor-access.qualify', $invitation), [])
            ->assertSessionHasErrors('confirm');

        $this->assertSame(
            VendorEngagementStage::AVAILABLE,
            VendorEngagement::first()->stageRecord(Flow::QUALIFY)->status,
        );
    }

    public function test_declining_records_a_reason_and_stops_the_engagement(): void
    {
        $vendor = $this->vendor();
        $invitation = $this->invitation($vendor);
        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation));
        $this->actingAs($vendor)->post(route('vendor-access.reviewed', $invitation));

        $this->actingAs($vendor)->post(route('vendor-access.decline', $invitation), [
            'reason' => 'wage',
            'note' => 'Baseline wage will not staff this post overnight.',
        ])->assertRedirect();

        $engagement = VendorEngagement::first();
        $this->assertSame(Flow::STATUS_DECLINED, $engagement->status);
        $this->assertSame('wage', $engagement->decline_reason);
        $this->assertNotNull($engagement->declined_at);
        $this->assertDatabaseHas('vendor_engagement_activity', ['event_type' => Flow::EVENT_OPPORTUNITY_DECLINED]);

        $this->actingAs($vendor)->post(route('vendor-access.decline', $invitation), ['reason' => 'nonsense'])
            ->assertSessionHasErrors('reason');
    }

    public function test_a_scope_adjustment_pauses_the_engagement_and_keeps_the_reason(): void
    {
        $vendor = $this->vendor();
        $invitation = $this->invitation($vendor);
        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation));
        $this->actingAs($vendor)->post(route('vendor-access.reviewed', $invitation));

        $this->actingAs($vendor)->post(route('vendor-access.request-adjustment', $invitation), [
            'note' => 'Two posts are needed overnight, not one.',
        ])->assertRedirect();

        $engagement = VendorEngagement::first();
        $this->assertSame(Flow::STATUS_ADJUSTMENT_REQUESTED, $engagement->status);
        $this->assertNotNull($engagement->adjustment_requested_at);
        $this->assertStringContainsString('Two posts', $engagement->adjustment_note);
        // The response stage is not complete, so qualification has not opened.
        $this->assertSame(VendorEngagementStage::LOCKED, $engagement->stageRecord(Flow::QUALIFY)->status);
    }

    public function test_one_engagement_per_vendor_per_opportunity(): void
    {
        $vendor = $this->vendor();
        $invitation = $this->invitation($vendor);

        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation));
        $this->actingAs($vendor)->get(route('vendor-access.enter', $invitation));

        $this->assertSame(1, VendorEngagement::count());
        $this->assertSame(10, VendorEngagementStage::count());
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $invitation = $this->invitation();

        $this->get(route('vendor-access.enter', $invitation))->assertRedirect(route('login'));
    }
}
