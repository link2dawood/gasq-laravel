<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VendorCapability;
use App\Models\VendorOpportunity;
use App\Models\VendorOpportunityInvitation;
use App\Notifications\BuyerVendorMatchNotification;
use App\Notifications\VendorOpportunityNotification;
use App\Services\TwilioSmsService;
use App\Services\VendorOpportunityManager;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VendorOpportunityAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tier_job_creates_opportunity_and_sends_only_matching_vendor_invites(): void
    {
        Notification::fake();
        $smsFake = $this->fakeSmsService();
        app()->instance(TwilioSmsService::class, $smsFake);

        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'company' => 'Buyer Co',
            'phone_verified' => true,
            'phone' => '5551234567',
        ]);

        $matchingVendor = $this->createVendorWithCapability('vendor1@example.com', ['California'], ['Event Security']);
        $nonMatchingVendor = $this->createVendorWithCapability('vendor2@example.com', ['Texas'], ['Mobile Patrol']);

        $job = $this->createQualifiedJob($buyer);

        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($job);

        $this->assertSame('a', $opportunity->lead_tier);
        $this->assertSame(VendorOpportunity::STATUS_SENT, $opportunity->status);
        $this->assertDatabaseHas('vendor_opportunity_invitations', [
            'vendor_opportunity_id' => $opportunity->id,
            'vendor_id' => $matchingVendor->id,
        ]);
        $this->assertDatabaseMissing('vendor_opportunity_invitations', [
            'vendor_opportunity_id' => $opportunity->id,
            'vendor_id' => $nonMatchingVendor->id,
        ]);

        Notification::assertSentTo($matchingVendor, VendorOpportunityNotification::class, function (VendorOpportunityNotification $notification): bool {
            return $notification->type === 'new';
        });
        Notification::assertSentTo($buyer, BuyerVendorMatchNotification::class, function (BuyerVendorMatchNotification $notification): bool {
            return $notification->type === 'live'
                && $notification->acceptedCount === 0
                && str_contains($notification->smsBody(), '0 of 5 vendors accepted');
        });
        $this->assertTrue(collect($smsFake->messages)->contains(
            fn (array $message): bool => $message['to'] === '+15551234567'
                && str_contains($message['body'], '0 of 5 vendors accepted')
        ));
    }

    public function test_b_tier_job_stays_pending_review_until_admin_approval(): void
    {
        Notification::fake();
        $smsFake = $this->fakeSmsService();
        app()->instance(TwilioSmsService::class, $smsFake);

        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
            'phone' => '5551234567',
        ]);

        $job = $this->createQualifiedJob($buyer, [
            'move_forward_if_accepted' => 'no',
        ]);

        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($job);

        $this->assertSame('b', $opportunity->lead_tier);
        $this->assertSame(VendorOpportunity::STATUS_PENDING_REVIEW, $opportunity->status);
        $this->assertDatabaseCount('vendor_opportunity_invitations', 0);
        Notification::assertSentTo($buyer, BuyerVendorMatchNotification::class, function (BuyerVendorMatchNotification $notification): bool {
            return $notification->type === BuyerVendorMatchNotification::TYPE_PENDING_QUALIFICATION
                && $notification->smsBody() === '';
        });
        $this->assertCount(0, $smsFake->messages);
    }

    public function test_c_tier_job_is_held_internally(): void
    {
        Notification::fake();

        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
        ]);

        $job = $this->createQualifiedJob($buyer, [
            'final_decision_maker' => 'no',
            'funds_approval_status' => 'unknown',
            'move_forward_if_accepted' => 'no',
        ]);

        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($job);

        $this->assertSame('c', $opportunity->lead_tier);
        $this->assertSame(VendorOpportunity::STATUS_HELD, $opportunity->status);
        $this->assertDatabaseCount('vendor_opportunity_invitations', 0);
        Notification::assertSentTo($buyer, BuyerVendorMatchNotification::class, function (BuyerVendorMatchNotification $notification): bool {
            return $notification->type === BuyerVendorMatchNotification::TYPE_PENDING_QUALIFICATION;
        });
    }

    public function test_vendor_accept_deducts_credits_and_unlocks_details_once(): void
    {
        Notification::fake();
        $smsFake = $this->fakeSmsService();
        app()->instance(TwilioSmsService::class, $smsFake);

        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true, 'phone' => '5551234567']);
        $vendor = $this->createVendorWithCapability('vendor@example.com', ['California'], ['Event Security']);
        app(WalletService::class)->addTokens($vendor, 200, 'grant', 'Seed credits');

        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($this->createQualifiedJob($buyer));
        $invitation = $opportunity->invitations()->firstOrFail();

        $response = $this->actingAs($vendor)->post(route('vendor-opportunities.accept', $invitation));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('vendor_opportunity_invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $vendor->id,
            'reference_type' => 'vendor_opportunity_accept',
            'reference_id' => (string) $invitation->id,
            'tokens_change' => -75,
        ]);

        $balanceAfterFirstAccept = app(WalletService::class)->getBalance($vendor);
        $this->actingAs($vendor)->post(route('vendor-opportunities.accept', $invitation));
        $this->assertSame($balanceAfterFirstAccept, app(WalletService::class)->getBalance($vendor));

        Notification::assertSentTo($buyer, BuyerVendorMatchNotification::class, function (BuyerVendorMatchNotification $notification): bool {
            return $notification->type === 'accepted_progress'
                && $notification->acceptedCount === 1
                && str_contains($notification->smsBody(), '1 of 5 vendors accepted');
        });
        $this->assertSame(
            2,
            collect($smsFake->messages)
                ->filter(fn (array $message): bool => $message['to'] === '+15551234567')
                ->count()
        );
    }

    public function test_vendor_accept_fails_when_credits_are_insufficient(): void
    {
        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true]);
        $vendor = $this->createVendorWithCapability('vendor@example.com', ['California'], ['Event Security']);
        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($this->createQualifiedJob($buyer));
        $invitation = $opportunity->invitations()->firstOrFail();

        $response = $this->actingAs($vendor)->post(route('vendor-opportunities.accept', $invitation));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $vendor->id,
            'reference_type' => 'vendor_opportunity_accept',
            'reference_id' => (string) $invitation->id,
        ]);
    }

    public function test_sixth_vendor_cannot_accept_after_five_acceptances_but_existing_accepted_vendor_can_submit_bid(): void
    {
        Notification::fake();

        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true]);
        $job = $this->createQualifiedJob($buyer);
        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($job);

        $vendors = collect();
        for ($i = 1; $i <= 6; $i++) {
            $vendor = $this->createVendorWithCapability("vendor{$i}@example.com", ['California'], ['Event Security']);
            app(WalletService::class)->addTokens($vendor, 500, 'grant', 'Seed credits');
            $vendors->push($vendor);
        }

        $opportunity->delete();
        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($job);

        $invitations = $opportunity->fresh('invitations')->invitations->values();
        while ($invitations->count() < 6) {
            $vendor = $vendors[$invitations->count()];
            $invitation = VendorOpportunityInvitation::query()->create([
                'vendor_opportunity_id' => $opportunity->id,
                'vendor_id' => $vendor->id,
                'invite_key' => (string) \Illuminate\Support\Str::uuid(),
                'status' => 'new',
                'credits_to_unlock' => 50,
                'sent_at' => now(),
            ]);
            $invitations->push($invitation);
        }

        foreach ($invitations->take(5) as $index => $invitation) {
            $this->actingAs($vendors[$index])->post(route('vendor-opportunities.accept', $invitation))->assertRedirect();
        }

        $sixthResponse = $this->actingAs($vendors[5])->post(route('vendor-opportunities.accept', $invitations[5]));
        $sixthResponse->assertStatus(422);

        $acceptedVendor = $vendors[0];
        $acceptedInvitation = $invitations[0]->fresh();
        $bidResponse = $this->actingAs($acceptedVendor)->post(route('vendor-opportunities.submit-bid', $acceptedInvitation), [
            'hourly_bill_rate' => 28,
            'weekly_price' => 4704,
            'monthly_price' => 20384,
            'annual_price' => 244608,
            'staffing_plan' => 'Four officers rotating across all shifts.',
            'start_availability' => 'Within 7 days',
            'vendor_notes' => 'Ready to mobilize.',
        ]);

        $bidResponse->assertRedirect();
        $this->assertDatabaseHas('bids', [
            'vendor_opportunity_invitation_id' => $acceptedInvitation->id,
            'user_id' => $acceptedVendor->id,
            'status' => 'pending',
        ]);
    }

    public function test_decline_requires_reason(): void
    {
        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true]);
        $vendor = $this->createVendorWithCapability('vendor@example.com', ['California'], ['Event Security']);
        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($this->createQualifiedJob($buyer));
        $invitation = $opportunity->invitations()->firstOrFail();

        $response = $this->actingAs($vendor)->post(route('vendor-opportunities.decline', $invitation), []);

        $response->assertSessionHasErrors('decline_reason');
    }

    public function test_signed_invite_page_tracks_open_and_view(): void
    {
        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true]);
        $vendor = $this->createVendorWithCapability('vendor@example.com', ['California'], ['Event Security']);
        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($this->createQualifiedJob($buyer));
        $invitation = $opportunity->invitations()->firstOrFail();

        $url = URL::temporarySignedRoute('vendor-opportunities.show', now()->addHour(), ['invitation' => $invitation]);

        $response = $this->actingAs($vendor)->get($url);

        $response->assertOk();
        $this->assertDatabaseHas('vendor_opportunity_invitations', [
            'id' => $invitation->id,
            'status' => 'viewed',
        ]);
        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'vendor_opportunity_viewed',
            'user_id' => $vendor->id,
        ]);
    }

    public function test_automation_command_sends_reminders_and_expires_accepted_no_bid_invitation(): void
    {
        Notification::fake();

        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true]);
        $vendor = $this->createVendorWithCapability('vendor@example.com', ['California'], ['Event Security']);
        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($this->createQualifiedJob($buyer));
        $invitation = $opportunity->invitations()->firstOrFail();

        $invitation->forceFill([
            'sent_at' => now()->subHours(49),
            'opened_at' => now()->subHours(48),
            'status' => VendorOpportunityInvitation::STATUS_VIEWED,
        ])->save();

        Artisan::call('vendor-opportunities:process');

        Notification::assertSentTo($vendor, VendorOpportunityNotification::class, function (VendorOpportunityNotification $notification): bool {
            return $notification->type === 'final_notice';
        });

        app(WalletService::class)->addTokens($vendor, 200, 'grant', 'Seed credits');
        $this->actingAs($vendor)->post(route('vendor-opportunities.accept', $invitation))->assertRedirect();

        $invitation->refresh()->forceFill([
            'accepted_at' => now()->subHours(25),
            'expires_at' => now()->subHour(),
            'status' => VendorOpportunityInvitation::STATUS_ACCEPTED,
        ])->save();

        Artisan::call('vendor-opportunities:process');

        $this->assertDatabaseHas('vendor_opportunity_invitations', [
            'id' => $invitation->id,
            'status' => VendorOpportunityInvitation::STATUS_EXPIRED,
        ]);
    }

    public function test_admin_can_award_vendor_and_mark_others_not_selected(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['user_type' => 'admin']);
        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true]);
        $firstVendor = $this->createVendorWithCapability('vendor1@example.com', ['California'], ['Event Security']);
        $secondVendor = $this->createVendorWithCapability('vendor2@example.com', ['California'], ['Event Security']);
        app(WalletService::class)->addTokens($firstVendor, 200, 'grant', 'Seed credits');
        app(WalletService::class)->addTokens($secondVendor, 200, 'grant', 'Seed credits');

        $opportunity = app(VendorOpportunityManager::class)->createForPublishedJob($this->createQualifiedJob($buyer));
        $invitationOne = $opportunity->invitations()->firstOrFail();
        $invitationTwo = VendorOpportunityInvitation::query()->create([
            'vendor_opportunity_id' => $opportunity->id,
            'vendor_id' => $secondVendor->id,
            'invite_key' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'new',
            'credits_to_unlock' => 50,
            'sent_at' => now(),
        ]);

        $this->actingAs($firstVendor)->post(route('vendor-opportunities.accept', $invitationOne));
        $this->actingAs($firstVendor)->post(route('vendor-opportunities.submit-bid', $invitationOne), [
            'hourly_bill_rate' => 28,
            'weekly_price' => 4704,
            'monthly_price' => 20384,
            'annual_price' => 244608,
            'staffing_plan' => 'Plan A',
            'start_availability' => 'Immediately',
            'vendor_notes' => 'Ready',
        ]);

        $this->actingAs($secondVendor)->post(route('vendor-opportunities.accept', $invitationTwo));
        $this->actingAs($secondVendor)->post(route('vendor-opportunities.submit-bid', $invitationTwo), [
            'hourly_bill_rate' => 29,
            'weekly_price' => 4872,
            'monthly_price' => 21112,
            'annual_price' => 253344,
            'staffing_plan' => 'Plan B',
            'start_availability' => 'Immediately',
            'vendor_notes' => 'Ready',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.vendor-opportunities.award', $invitationOne));

        $response->assertRedirect();
        $this->assertDatabaseHas('vendor_opportunity_invitations', [
            'id' => $invitationOne->id,
            'status' => VendorOpportunityInvitation::STATUS_AWARDED,
        ]);
        $this->assertDatabaseHas('vendor_opportunity_invitations', [
            'id' => $invitationTwo->id,
            'status' => VendorOpportunityInvitation::STATUS_NOT_SELECTED,
        ]);

        Notification::assertSentTo($firstVendor, VendorOpportunityNotification::class, function (VendorOpportunityNotification $notification): bool {
            return $notification->type === 'awarded';
        });
        Notification::assertSentTo($secondVendor, VendorOpportunityNotification::class, function (VendorOpportunityNotification $notification): bool {
            return $notification->type === 'not_selected';
        });
    }

    private function createVendorWithCapability(string $email, array $serviceAreas, array $competencies): User
    {
        $vendor = User::factory()->create([
            'user_type' => 'vendor',
            'email' => $email,
            'company' => 'Vendor Co',
            'phone_verified' => true,
        ]);

        $vendor->vendorProfile()->create([
            'company_name' => 'Vendor Co',
            'description' => 'Security contractor',
            'phone' => '5551112222',
            'address' => '123 Main St',
            'capabilities' => $competencies,
            'is_verified' => true,
        ]);

        VendorCapability::query()->create([
            'user_id' => $vendor->id,
            'legal_business_name' => 'Vendor Co',
            'service_areas' => $serviceAreas,
            'states_licensed' => $serviceAreas,
            'core_competencies' => $competencies,
            'license_verified' => true,
            'insurance_verified' => true,
            'background_check_verified' => true,
            'profile_completion_score' => 100,
            'team_size' => '50 officers',
            'response_time' => '24 hours',
        ]);

        return $vendor;
    }

    private function fakeSmsService(): TwilioSmsService
    {
        return new class extends TwilioSmsService
        {
            /** @var list<array{to: string, body: string}> */
            public array $messages = [];

            public function send(string $toE164, string $body): void
            {
                $this->messages[] = [
                    'to' => $toE164,
                    'body' => $body,
                ];
            }
        };
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createQualifiedJob(User $buyer, array $overrides = []): JobPosting
    {
        $questionnaire = array_merge([
            'final_decision_maker' => 'yes',
            'funds_approval_status' => 'flexible_budget',
            'move_forward_if_accepted' => 'yes',
            'service_types' => ['Event Security'],
            'service_start_timeline' => '30_60_days',
            'primary_reason' => 'New requirement',
            'hours_per_day' => 24,
            'days_per_week' => 7,
            'weeks_per_year' => 52,
            'annual_budget' => 244608,
            'officer_licensing_required' => 'yes',
            'insurance_minimums_required' => 'yes',
            'background_checks_required' => 'yes',
        ], $overrides);

        return JobPosting::query()->create([
            'user_id' => $buyer->id,
            'title' => 'Event Security request',
            'category' => 'Event Security',
            'location' => 'Sacramento, California',
            'zip_code' => '95835',
            'service_start_date' => now()->addDays(30)->toDateString(),
            'guards_per_shift' => 4,
            'budget_min' => 220000,
            'budget_max' => 244608,
            'description' => 'Need full-site event security.',
            'property_type' => 'Commercial',
            'status' => 'open',
            'questionnaire_data' => $questionnaire,
        ]);
    }
}
