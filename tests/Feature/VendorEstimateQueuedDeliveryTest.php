<?php

namespace Tests\Feature;

use App\Mail\VendorEstimateMail;
use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorLeadEstimate;
use App\Models\VendorLeadLink;
use App\Support\BuyerQualificationQuestionnaire;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A delivery stays `queued` when no queue worker claims its send job. That is
 * what the live site was doing: the buyer never got the estimate, and the
 * "Buyer PDF access page" link the vendor was shown 404d, because that page
 * only answers for a delivery the mail transport has accepted.
 */
class VendorEstimateQueuedDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->travelTo(now()->setDate(2026, 11, 8)->setTime(12, 0));
        // A real database queue with nobody draining it: exactly the live
        // situation these tests exist for. Faking the queue would also swallow
        // the synchronous dispatch the fix depends on.
        config(['queue.default' => 'database']);
        Mail::fake();
        Storage::fake('local');
    }

    /** Vendor with a ready estimate already emailed, with the job left unclaimed. */
    private function queuedDelivery(): array
    {
        $vendor = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now(), 'currency' => 'USD']);
        $this->actingAs($vendor)->get(route('vendor-buyer-leads.index'))->assertOk();
        $link = VendorLeadLink::where('vendor_id', $vendor->id)->firstOrFail();
        auth()->forgetGuards();
        $this->post(route('buyer-enquiries.store', $link->token), [
            'company' => 'Buyer Company', 'contact_name' => 'Avery Buyer', 'contact_title' => 'Facilities Director',
            'email' => 'avery@example.test', 'phone' => '+14045550123', 'site_address' => '100 Main Street',
            'decision_maker_name' => 'Jordan Chief', 'decision_maker_phone' => '+14045550124', 'decision_maker_email' => 'jordan@example.test',
            'approved_budget' => 12000, 'approved_budget_basis' => 'monthly',
            'service_types' => ['unarmed'], 'industry' => 'Commercial Office Buildings', 'preferred_contact_method' => 'email',
            'scope' => 'Lobby security.',
            'start_date' => now()->addDays(30)->toDateString(), 'service_end_date' => now()->addDays(395)->toDateString(),
            'hours_per_day' => 8, 'days_per_week' => 5, 'officers_per_shift' => 2, 'number_of_weeks' => 52,
            'answers' => array_merge(array_fill_keys(array_keys(BuyerQualificationQuestionnaire::questions()), 'yes'), ['q3a' => 'operating_budget', 'q9a' => 'standard']),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $lead = VendorBuyerLead::firstOrFail();
        $this->actingAs($vendor);
        $this->post(route('vendor-calendar.settings'), ['enabled' => 1, 'timezone' => 'America/New_York', 'meeting_details' => 'https://example.test/meeting'])->assertRedirect();
        $this->post(route('vendor-estimates.store', $lead), ['title' => 'Lobby estimate', 'scope' => 'Lobby security.', 'hourly_rate' => 25.50, 'weekly_hours' => 80, 'weeks_per_year' => 52, 'one_time_cost' => 500, 'valid_until' => now()->addDays(30)->toDateString(), 'status' => 'ready'])->assertRedirect();
        $estimate = VendorLeadEstimate::firstOrFail();
        $this->post(route('vendor-estimates.send', $estimate), ['request_key' => (string) Str::uuid(), 'pdf_password' => 'WorkflowPassword2026'])->assertRedirect()->assertSessionHasNoErrors();
        $delivery = VendorEstimateDelivery::firstOrFail();
        $this->assertSame('queued', $delivery->status);

        return compact('vendor', 'estimate', 'delivery');
    }

    public function test_access_page_404s_while_the_delivery_is_still_queued(): void
    {
        ['delivery' => $delivery] = $this->queuedDelivery();
        auth()->forgetGuards();

        $this->get($delivery->accessUrl())->assertNotFound();
        $this->assertFalse($delivery->accessPageLive());
    }

    public function test_vendor_sees_the_stall_warning_instead_of_a_dead_access_link(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate, 'delivery' => $delivery] = $this->queuedDelivery();
        $this->travel(2)->minutes();

        $this->actingAs($vendor)->get(route('vendor-estimates.edit', $estimate))
            ->assertOk()
            ->assertDontSee('Buyer PDF access page')
            ->assertSee('still waiting on the background queue', false)
            ->assertSee(route('vendor-estimates.deliveries.flush', [$estimate, $delivery]), false);
    }

    public function test_vendor_can_send_a_stuck_delivery_and_the_access_page_then_opens(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate, 'delivery' => $delivery] = $this->queuedDelivery();

        $this->actingAs($vendor)->post(route('vendor-estimates.deliveries.flush', [$estimate, $delivery]))
            ->assertRedirect(route('vendor-estimates.edit', $estimate));

        $this->assertSame('sent', $delivery->fresh()->status);
        Mail::assertSent(VendorEstimateMail::class, 1);

        auth()->forgetGuards();
        $this->get($delivery->fresh()->accessUrl())->assertOk();
    }

    public function test_sending_an_already_sent_delivery_is_rejected(): void
    {
        ['vendor' => $vendor, 'estimate' => $estimate, 'delivery' => $delivery] = $this->queuedDelivery();
        $this->actingAs($vendor)->post(route('vendor-estimates.deliveries.flush', [$estimate, $delivery]))->assertRedirect();

        $this->actingAs($vendor)->post(route('vendor-estimates.deliveries.flush', [$estimate, $delivery]))
            ->assertStatus(422);

        Mail::assertSent(VendorEstimateMail::class, 1);
    }

    public function test_another_vendor_cannot_send_this_delivery(): void
    {
        ['estimate' => $estimate, 'delivery' => $delivery] = $this->queuedDelivery();
        $other = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now()]);

        $this->actingAs($other)->post(route('vendor-estimates.deliveries.flush', [$estimate, $delivery]))
            ->assertNotFound();

        $this->assertSame('queued', $delivery->fresh()->status);
    }

    public function test_scheduled_flush_delivers_what_the_queue_never_claimed(): void
    {
        ['delivery' => $delivery] = $this->queuedDelivery();
        $this->travel(3)->minutes();

        $this->artisan('vendor-estimates:flush-deliveries')->assertSuccessful();

        $this->assertSame('sent', $delivery->fresh()->status);
        Mail::assertSent(VendorEstimateMail::class, 1);
    }

    public function test_scheduled_flush_leaves_a_delivery_inside_the_grace_window_alone(): void
    {
        ['delivery' => $delivery] = $this->queuedDelivery();

        $this->artisan('vendor-estimates:flush-deliveries')->assertSuccessful();

        $this->assertSame('queued', $delivery->fresh()->status);
        Mail::assertNotSent(VendorEstimateMail::class);
    }
}
