<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\JobPosting;
use App\Models\User;
use App\Http\Middleware\EnsureNdaAccepted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JobOfferResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureNdaAccepted::class);
    }

    public function test_vendor_can_accept_job_offer_and_create_response_bid(): void
    {
        Notification::fake();

        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
        ]);

        $vendor = User::factory()->create([
            'user_type' => 'vendor',
            'phone_verified' => true,
        ]);
        $this->qualifiedCapability($vendor);

        $job = JobPosting::query()->create([
            'user_id' => $buyer->id,
            'title' => 'Security Coverage',
            'category' => 'Event Security',
            'location' => 'Sacramento, CA',
            'service_start_date' => '2026-05-01',
            'guards_per_shift' => 1,
            'property_type' => 'Commercial Office',
            'status' => 'open',
        ]);

        $response = $this->actingAs($vendor)->post(route('bids.offer-response', $job), [
            'status' => 'accepted',
            'baseline_wage_acknowledged' => true,
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Offer accepted. Please complete the qualification questionnaire to send your full response to the buyer.');

        $this->assertDatabaseHas('bids', [
            'job_posting_id' => $job->id,
            'user_id' => $vendor->id,
            'status' => 'pending',
            'vendor_response_status' => 'accepted',
        ]);

        $bid = Bid::query()->where('job_posting_id', $job->id)->where('user_id', $vendor->id)->first();
        $this->assertNotNull($bid);
        $this->assertNotNull($bid?->vendor_responded_at);
    }

    public function test_vendor_can_change_response_while_job_offer_is_open(): void
    {
        Notification::fake();

        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
        ]);

        $vendor = User::factory()->create([
            'user_type' => 'vendor',
            'phone_verified' => true,
        ]);
        $this->qualifiedCapability($vendor);

        $job = JobPosting::query()->create([
            'user_id' => $buyer->id,
            'title' => 'Security Coverage',
            'category' => 'Event Security',
            'location' => 'Sacramento, CA',
            'service_start_date' => '2026-05-01',
            'guards_per_shift' => 1,
            'property_type' => 'Commercial Office',
            'status' => 'open',
        ]);

        $bid = Bid::query()->create([
            'job_posting_id' => $job->id,
            'user_id' => $vendor->id,
            'amount' => 0,
            'status' => 'pending',
            'vendor_response_status' => 'declined',
            'vendor_responded_at' => now()->subHour(),
            'message' => 'Declined job offer announcement.',
        ]);

        $response = $this->actingAs($vendor)->post(route('bids.offer-response', $job), [
            'status' => 'accepted',
            'baseline_wage_acknowledged' => true,
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Offer accepted. Please complete the qualification questionnaire to send your full response to the buyer.');

        $bid->refresh();
        $this->assertSame('accepted', $bid->vendor_response_status);
        $this->assertTrue($bid->vendorAccepted());
    }

    public function test_job_show_page_displays_response_progress_and_vendor_action_buttons(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
        ]);

        $viewerVendor = User::factory()->create([
            'user_type' => 'vendor',
            'phone_verified' => true,
            'name' => 'Vendor One',
        ]);

        $secondVendor = User::factory()->create([
            'user_type' => 'vendor',
            'phone_verified' => true,
            'name' => 'Vendor Two',
        ]);

        $job = JobPosting::query()->create([
            'user_id' => $buyer->id,
            'title' => 'Event Security request for 2345 Bay Horse Ln',
            'category' => 'Event Security',
            'location' => 'Sacramento, CA',
            'service_start_date' => '2026-05-01',
            'guards_per_shift' => 1,
            'budget_min' => 25,
            'budget_max' => 25,
            'property_type' => 'Commercial Office',
            'status' => 'open',
            'description' => 'Primary reason: test',
        ]);

        Bid::query()->create([
            'job_posting_id' => $job->id,
            'user_id' => $viewerVendor->id,
            'amount' => 0,
            'status' => 'pending',
            'vendor_response_status' => 'accepted',
            'vendor_responded_at' => now()->subMinutes(20),
            'message' => 'Accepted job offer announcement.',
        ]);

        Bid::query()->create([
            'job_posting_id' => $job->id,
            'user_id' => $secondVendor->id,
            'amount' => 0,
            'status' => 'pending',
            'vendor_response_status' => 'declined',
            'vendor_responded_at' => now()->subMinutes(10),
            'message' => 'Declined job offer announcement.',
        ]);

        $response = $this->actingAs($viewerVendor)->get(route('jobs.show', $job));

        $response->assertOk();
        $response->assertSeeText('Responses 2/5');
        $response->assertSeeText('1 accepted');
        $response->assertSeeText('1 declined');
        $response->assertSeeText('Interested in this opportunity?');
        $response->assertSeeText('Accept opportunity');
        $response->assertSeeText('Decline');
        $response->assertSeeText('You accepted this opportunity.');
    }

    private function qualifiedCapability(User $vendor): void
    {
        $vendor->vendorCapability()->create([
            'license_verified' => true,
            'insurance_verified' => true,
            'profile_completion_score' => 100,
            'team_size' => '25 officers',
        ]);
    }
}
