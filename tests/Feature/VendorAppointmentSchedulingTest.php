<?php

namespace Tests\Feature;

use App\Jobs\SendVendorAppointmentMessage;
use App\Mail\VendorAppointmentMail;
use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorLeadEstimate;
use App\Models\VendorReviewAppointment;
use App\Models\VendorReviewSlot;
use App\Models\VendorSchedulingProfile;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorAppointmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->travelTo(now()->setDate(2026, 11, 7)->setTime(12, 0));
        Queue::fake();
        Mail::fake();
        Storage::fake('local');
    }

    private function vendor(): User
    {
        $vendor = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now()]);
        VendorSchedulingProfile::create(['vendor_id' => $vendor->id, 'enabled' => true, 'timezone' => 'America/New_York', 'meeting_details' => 'https://example.test/meeting']);

        return $vendor;
    }

    private function publish(User $vendor): void
    {
        $this->actingAs($vendor)->post(route('vendor-calendar.publish'), ['date' => '2026-11-10', 'from' => '09:00', 'until' => '11:00'])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function delivery(User $vendor): VendorEstimateDelivery
    {
        $lead = VendorBuyerLead::create(['vendor_id' => $vendor->id, 'contact_name' => 'Avery Buyer', 'email' => 'avery@example.test', 'phone' => '+14045550123', 'site_address' => '100 Main Street', 'service_type' => 'Guarding', 'scope' => 'Lobby security.']);
        $estimate = VendorLeadEstimate::create(['vendor_id' => $vendor->id, 'vendor_buyer_lead_id' => $lead->id, 'number' => 'GASQ-EST-'.Str::uuid(), 'title' => 'Lobby estimate', 'buyer' => $lead->only(['contact_name', 'email', 'phone', 'site_address']), 'currency' => 'USD', 'scope' => 'Lobby security.', 'hourly_rate' => 25, 'weekly_hours' => 80, 'weeks_per_year' => 52, 'one_time_cost' => 0, 'valid_until' => now()->addDays(30), 'status' => 'ready']);
        $token = Str::random(64);

        return VendorEstimateDelivery::create(['vendor_lead_estimate_id' => $estimate->id, 'request_key' => (string) Str::uuid(), 'snapshot' => ['number' => $estimate->number, 'buyer' => $estimate->buyer, 'reply_to' => $vendor->email, 'vendor_name' => $vendor->name], 'recipient' => 'avery@example.test', 'scheduling_token' => $token, 'scheduling_token_hash' => hash('sha256', $token), 'scheduling_target' => 'native', 'native_scheduling' => true, 'scheduling_expires_at' => now()->addDays(30), 'pdf_path' => 'unused.pdf', 'pdf_sha256' => str_repeat('a', 64), 'status' => 'sent']);
    }

    private function book(VendorEstimateDelivery $delivery, VendorReviewSlot $slot): VendorReviewAppointment
    {
        $this->post(route('vendor-review.book', $delivery->scheduling_token), ['slot_id' => $slot->id, 'timezone' => 'Asia/Karachi'])->assertRedirect()->assertSessionHasNoErrors();

        return VendorReviewAppointment::where('vendor_lead_estimate_id', $delivery->vendor_lead_estimate_id)->firstOrFail();
    }

    public function test_vendor_publishes_local_availability_and_guest_books_in_their_time_zone(): void
    {
        $vendor = $this->vendor();
        $this->publish($vendor);
        $this->assertDatabaseCount('vendor_review_slots', 4);
        $slot = VendorReviewSlot::firstOrFail();
        $this->assertSame('2026-11-10 14:00', $slot->starts_at->format('Y-m-d H:i'));
        $delivery = $this->delivery($vendor);
        auth()->forgetGuards();
        $this->get($delivery->schedulingUrl().'?timezone=Asia/Karachi')->assertOk()->assertSee('7:00 PM')->assertSee('Asia/Karachi');
        $appointment = $this->book($delivery, $slot);
        $this->assertSame($vendor->id, $appointment->vendor_id);
        $this->assertSame('Asia/Karachi', $appointment->buyer_timezone);
        $this->get($appointment->manageUrl())->assertOk()->assertSee('Your appointment is confirmed')->assertSee('7:00 PM');
        $this->get($delivery->schedulingUrl())->assertRedirect($appointment->manageUrl());
        $this->assertDatabaseCount('vendor_appointment_messages', 2);
        Queue::assertPushed(SendVendorAppointmentMessage::class, 2);
        foreach ($appointment->messages as $message) {
            $job = new SendVendorAppointmentMessage($message->id);
            $job->handle();
            $job->handle();
        }
        Mail::assertSentCount(2);
        Mail::assertSent(VendorAppointmentMail::class, function ($mail) {
            $this->assertStringContainsString('confirmed', $mail->render());

            return $mail->hasTo('avery@example.test');
        });
        Mail::assertSent(VendorAppointmentMail::class, fn ($mail) => $mail->hasTo($vendor->email));
    }

    public function test_double_booking_and_slots_from_another_vendor_are_rejected_and_repeat_submit_is_idempotent(): void
    {
        $vendor = $this->vendor();
        $this->publish($vendor);
        $slot = VendorReviewSlot::firstOrFail();
        $first = $this->delivery($vendor);
        $appointment = $this->book($first, $slot);
        $this->book($first, $slot);
        $second = $this->delivery($vendor);
        $this->post(route('vendor-review.book', $second->scheduling_token), ['slot_id' => $slot->id, 'timezone' => 'UTC'])->assertSessionHasErrors('slot_id');
        $other = $this->vendor();
        $this->publish($other);
        $foreignSlot = VendorReviewSlot::where('vendor_id', $other->id)->firstOrFail();
        $this->post(route('vendor-review.book', $second->scheduling_token), ['slot_id' => $foreignSlot->id, 'timezone' => 'UTC'])->assertSessionHasErrors('slot_id');
        $this->assertDatabaseCount('vendor_review_appointments', 1);
        $this->assertDatabaseCount('vendor_appointment_messages', 2);
    }

    public function test_cancellation_releases_slot_suppresses_queued_reminders_and_notifies_both_parties(): void
    {
        $vendor = $this->vendor();
        $this->publish($vendor);
        $delivery = $this->delivery($vendor);
        $appointment = $this->book($delivery, VendorReviewSlot::firstOrFail());
        $this->actingAs($vendor)->delete(route('vendor-calendar.remove', $appointment->vendor_review_slot_id))->assertStatus(422);
        auth()->forgetGuards();
        $this->post(route('vendor-review.cancel', $appointment->manage_token))->assertRedirect();
        $this->post(route('vendor-review.cancel', $appointment->manage_token))->assertRedirect();
        $this->assertSame('canceled', $appointment->fresh()->status);
        $this->assertNull($appointment->fresh()->active_slot_id);
        $this->assertSame(2, $appointment->messages()->where('status', 'suppressed')->count());
        $this->assertSame(2, $appointment->messages()->where('kind', 'canceled')->count());
        foreach ($appointment->messages()->get() as $message) {
            (new SendVendorAppointmentMessage($message->id))->handle();
        }
        Mail::assertSentCount(2);
        $this->get($appointment->manageUrl())->assertSee('Your appointment is canceled');
        $this->get($delivery->schedulingUrl())->assertOk();
    }

    public function test_reminders_are_queued_once_at_24_hours_and_one_hour_and_never_after_cancellation(): void
    {
        $vendor = $this->vendor();
        $this->publish($vendor);
        $appointment = $this->book($this->delivery($vendor), VendorReviewSlot::firstOrFail());
        $this->travelTo($appointment->starts_at->subHours(24));
        $this->artisan('vendor-appointments:process')->assertSuccessful();
        $this->artisan('vendor-appointments:process')->assertSuccessful();
        $this->assertSame(2, $appointment->messages()->where('kind', 'reminder_24h')->count());
        foreach ($appointment->messages()->where('kind', 'reminder_24h')->get() as $message) {
            (new SendVendorAppointmentMessage($message->id))->handle();
        }
        Mail::assertSentCount(2);
        $this->travelTo($appointment->starts_at->subHour());
        $this->artisan('vendor-appointments:process')->assertSuccessful();
        $this->assertSame(2, $appointment->messages()->where('kind', 'reminder_1h')->count());
        $this->post(route('vendor-review.cancel', $appointment->manage_token));
        foreach ($appointment->messages()->where('kind', 'reminder_1h')->get() as $message) {
            (new SendVendorAppointmentMessage($message->id))->handle();
        }
        Mail::assertSentCount(2);
    }

    public function test_vendor_can_remove_availability_but_cannot_manage_another_vendors_calendar(): void
    {
        $vendor = $this->vendor();
        $this->publish($vendor);
        $slot = VendorReviewSlot::firstOrFail();
        $appointment = $this->book($this->delivery($vendor), $slot);
        $other = $this->vendor();
        $this->actingAs($other)->delete(route('vendor-calendar.remove', $slot))->assertNotFound();
        $this->post(route('vendor-calendar.cancel', $appointment))->assertNotFound();
        $this->get(route('vendor-calendar.index'))->assertOk()->assertDontSee('Avery Buyer');
        $this->actingAs($vendor)->post(route('vendor-calendar.cancel', $appointment))->assertRedirect();
        $this->delete(route('vendor-calendar.remove', $slot))->assertRedirect();
        $this->assertFalse($slot->fresh()->active);
    }

    public function test_invalid_dates_overlaps_expired_links_and_disabled_calendar_are_rejected(): void
    {
        $vendor = $this->vendor();
        $this->publish($vendor);
        $this->post(route('vendor-calendar.publish'), ['date' => '2026-11-10', 'from' => '09:00', 'until' => '10:00'])->assertSessionHasErrors('from');
        $this->post(route('vendor-calendar.publish'), ['date' => '2026-11-01', 'from' => '09:00', 'until' => '10:00'])->assertSessionHasErrors('date');
        $delivery = $this->delivery($vendor);
        $delivery->update(['scheduling_expires_at' => now()->subMinute()]);
        $this->post(route('vendor-review.book', $delivery->scheduling_token), ['slot_id' => VendorReviewSlot::first()->id, 'timezone' => 'UTC'])->assertStatus(410);
        $delivery->update(['scheduling_expires_at' => now()->addDay()]);
        VendorSchedulingProfile::where('vendor_id', $vendor->id)->update(['enabled' => false]);
        $this->post(route('vendor-review.book', $delivery->scheduling_token), ['slot_id' => VendorReviewSlot::first()->id, 'timezone' => 'UTC'])->assertStatus(422);
        $this->get(route('vendor-review.manage', 'invalid'))->assertNotFound();
    }

    public function test_buyers_and_guests_cannot_manage_vendor_availability(): void
    {
        $this->get(route('vendor-calendar.index'))->assertRedirect(route('login'));
        $buyer = User::factory()->create(['user_type' => 'buyer', 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->get(route('vendor-calendar.index'))->assertForbidden();
        $this->post(route('vendor-calendar.settings'), ['enabled' => 1, 'timezone' => 'UTC', 'meeting_details' => 'Phone review'])->assertForbidden();
    }

    public function test_native_calendar_enables_estimate_delivery_without_external_provider_configuration(): void
    {
        $vendor = $this->vendor();
        $delivery = $this->delivery($vendor);
        config(['appraisal_reviews.booking_url' => null, 'appraisal_reviews.bookafy.booking_url' => null]);
        $this->actingAs($vendor)->get(route('vendor-estimates.edit', $delivery->estimate))->assertOk()->assertSee('Send estimate to buyer');
        $this->post(route('vendor-estimates.send', $delivery->estimate), ['request_key' => (string) Str::uuid(), 'pdf_password' => 'NativeReviewPassword'])->assertRedirect()->assertSessionHasNoErrors();
        $created = $delivery->estimate->deliveries()->firstOrFail();
        $this->assertTrue($created->native_scheduling);
        $this->assertSame('native', $created->scheduling_target);
    }

    public function test_settings_are_vendor_owned_and_invalid_daylight_saving_times_are_rejected(): void
    {
        $vendor = $this->vendor();
        $this->actingAs($vendor)->post(route('vendor-calendar.settings'), ['enabled' => 1, 'timezone' => 'Asia/Karachi', 'meeting_details' => 'Phone +14045550100', 'vendor_id' => 999])->assertRedirect();
        $this->assertDatabaseHas('vendor_scheduling_profiles', ['vendor_id' => $vendor->id, 'timezone' => 'Asia/Karachi']);
        $this->post(route('vendor-calendar.settings'), ['enabled' => 1, 'timezone' => 'Invalid/Zone', 'meeting_details' => 'Phone'])->assertSessionHasErrors('timezone');
        VendorSchedulingProfile::where('vendor_id', $vendor->id)->update(['timezone' => 'America/New_York']);
        $this->travelTo(now()->setDate(2027, 3, 1));
        $this->post(route('vendor-calendar.publish'), ['date' => '2027-03-14', 'from' => '02:00', 'until' => '03:30'])->assertSessionHasErrors('date');
        $this->travelTo(now()->setDate(2027, 11, 1));
        $this->post(route('vendor-calendar.publish'), ['date' => '2027-11-07', 'from' => '01:00', 'until' => '01:30'])->assertSessionHasErrors('date');
        $this->assertDatabaseCount('vendor_review_slots', 0);
    }
}
