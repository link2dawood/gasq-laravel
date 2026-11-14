<?php

namespace Tests\Feature;

use App\Jobs\SendVendorAppointmentMessage;
use App\Jobs\SendVendorEstimateDelivery;
use App\Mail\VendorPdfVerificationMail;
use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorLeadEstimate;
use App\Models\VendorLeadLink;
use App\Support\BuyerQualificationQuestionnaire;
use App\Models\VendorPdfAccessEvent;
use App\Models\VendorReviewAppointment;
use App\Models\VendorReviewSlot;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorWorkflowEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->travelTo(now()->setDate(2026, 11, 8)->setTime(12, 0));
        Queue::fake();
        Mail::fake();
        Storage::fake('local');
    }

    private function workflow(): array
    {
        $vendor = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now(), 'currency' => 'USD']);
        $other = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now()]);
        $this->actingAs($vendor)->get(route('vendor-buyer-leads.index'))->assertOk();
        $link = VendorLeadLink::where('vendor_id', $vendor->id)->firstOrFail();
        auth()->forgetGuards();
        $this->get($link->invitationUrl())->assertOk();
        $this->post(route('buyer-enquiries.store', $link->token), array_merge([
            'company' => 'Buyer Company', 'contact_name' => 'Avery Buyer', 'contact_title' => 'Facilities Director',
            'email' => 'avery@example.test', 'phone' => '+14045550123', 'site_address' => '100 Main Street',
            'decision_maker_name' => 'Jordan Chief', 'decision_maker_phone' => '+14045550124', 'decision_maker_email' => 'jordan@example.test',
            'approved_budget' => 12000, 'approved_budget_basis' => 'monthly',
            'service_types' => ['unarmed'], 'industry' => 'Commercial property', 'preferred_contact_method' => 'email',
            'scope' => 'Lobby security.',
            'start_date' => now()->addDays(30)->toDateString(), 'service_end_date' => now()->addDays(395)->toDateString(),
            'hours_per_day' => 8, 'days_per_week' => 5, 'officers_per_shift' => 2, 'number_of_weeks' => 52,
            'answers' => array_fill_keys(array_keys(BuyerQualificationQuestionnaire::questions()), 'yes'),
            'vendor_id' => $other->id,
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $lead = VendorBuyerLead::firstOrFail();
        $this->assertSame($vendor->id, $lead->vendor_id);
        $this->actingAs($other)->get(route('vendor-buyer-leads.index'))->assertDontSee('Avery Buyer');
        $this->actingAs($vendor)->post(route('vendor-calendar.settings'), ['enabled' => 1, 'timezone' => 'America/New_York', 'meeting_details' => 'https://example.test/meeting'])->assertRedirect();
        $this->post(route('vendor-calendar.publish'), ['date' => '2026-11-11', 'from' => '09:00', 'until' => '10:00'])->assertRedirect();
        $this->post(route('vendor-estimates.store', $lead), ['title' => 'Lobby estimate', 'scope' => 'Lobby security.', 'hourly_rate' => 25.50, 'weekly_hours' => 80, 'weeks_per_year' => 52, 'one_time_cost' => 500, 'valid_until' => now()->addDays(30)->toDateString(), 'status' => 'ready'])->assertRedirect();
        $estimate = VendorLeadEstimate::firstOrFail();
        $this->post(route('vendor-estimates.send', $estimate), ['request_key' => (string) Str::uuid(), 'pdf_password' => 'WorkflowPassword2026'])->assertRedirect()->assertSessionHasNoErrors();
        $delivery = VendorEstimateDelivery::firstOrFail();
        (new SendVendorEstimateDelivery($delivery->id))->handle();
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertStringContainsString('/Encrypt', Storage::disk('local')->get($delivery->pdf_path));
        $this->assertNull($delivery->fresh()->released_password);
        auth()->forgetGuards();
        $this->get($delivery->schedulingUrl())->assertOk();
        $this->post(route('vendor-review.book', $delivery->scheduling_token), ['slot_id' => VendorReviewSlot::first()->id, 'timezone' => 'Asia/Karachi'])->assertRedirect();
        $appointment = VendorReviewAppointment::firstOrFail();
        foreach ($appointment->messages as $message) {
            (new SendVendorAppointmentMessage($message->id))->handle();
        }

        return compact('vendor', 'other', 'lead', 'estimate', 'delivery', 'appointment');
    }

    private function releaseData(VendorReviewAppointment $appointment, array $overrides = []): array
    {
        return array_merge(['appointment_id' => $appointment->id, 'review_completed' => 1, 'pdf_password' => 'WorkflowPassword2026'], $overrides);
    }

    public function test_invitation_to_released_password_and_verified_download_with_full_online_history(): void
    {
        extract($this->workflow());
        $this->get($delivery->accessUrl())->assertOk()->assertSee('password is restricted')->assertDontSee('WorkflowPassword2026');
        $this->post(route('vendor-pdf.reveal', $delivery->scheduling_token))->assertForbidden();
        $this->actingAs($vendor)->post(route('vendor-pdf.release', $delivery), $this->releaseData($appointment))->assertSessionHasErrors('release');
        $this->assertNull($delivery->fresh()->password_released_at);
        $this->travelTo($appointment->ends_at->addMinute());
        $this->post(route('vendor-pdf.release', $delivery), $this->releaseData($appointment))->assertRedirect()->assertSessionHasNoErrors();
        $releasedAt = $delivery->fresh()->password_released_at;
        $this->assertNotNull($releasedAt);
        $this->assertSame($vendor->id, $delivery->fresh()->password_released_by);
        $this->assertNotSame('WorkflowPassword2026', $delivery->fresh()->getRawOriginal('released_password'));
        $this->assertStringNotContainsString('WorkflowPassword2026', $delivery->fresh()->toJson());
        $this->post(route('vendor-pdf.release', $delivery), $this->releaseData($appointment))->assertRedirect();
        $this->assertTrue($delivery->fresh()->password_released_at->eq($releasedAt));
        $this->assertSame(1, VendorPdfAccessEvent::where('event', 'password_released')->count());
        auth()->forgetGuards();
        $this->post(route('vendor-pdf.reveal', $delivery->scheduling_token))->assertForbidden();
        $this->post(route('vendor-pdf.code', $delivery->scheduling_token), ['email' => 'attacker@example.test'])->assertRedirect();
        $code = null;
        Mail::assertSent(VendorPdfVerificationMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo('avery@example.test');
        });
        $this->post(route('vendor-pdf.verify', $delivery->scheduling_token), ['code' => $code])->assertRedirect($delivery->accessUrl());
        $this->get($delivery->accessUrl())->assertOk()->assertDontSee('WorkflowPassword2026');
        $this->post(route('vendor-pdf.reveal', $delivery->scheduling_token))->assertOk()->assertSee('WorkflowPassword2026')->assertHeader('Cache-Control', 'no-store, private');
        $file = $this->post(route('vendor-pdf.download', $delivery->scheduling_token))->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();
        $this->assertSame($delivery->pdf_sha256, hash('sha256', $file));
        Storage::disk('local')->put($delivery->pdf_path, 'tampered');
        $this->post(route('vendor-pdf.download', $delivery->scheduling_token))->assertStatus(409);
        $this->assertDatabaseHas('vendor_pdf_access_events', ['vendor_estimate_delivery_id' => $delivery->id, 'event' => 'pdf_integrity_failed']);
        Storage::disk('local')->put($delivery->pdf_path, $file);
        foreach (['protected_pdf_prepared', 'email_transport_accepted', 'release_denied', 'password_released', 'verification_code_sent', 'buyer_verified', 'password_revealed', 'pdf_downloaded'] as $event) {
            $this->assertDatabaseHas('vendor_pdf_access_events', ['vendor_estimate_delivery_id' => $delivery->id, 'event' => $event]);
        }
        $this->travelTo(now()->addMinutes(31));
        $this->post(route('vendor-pdf.reveal', $delivery->scheduling_token))->assertForbidden();
        $this->actingAs($vendor)->get(route('vendor-estimates.edit', $estimate))->assertOk()->assertSee('Protected PDF access history');
        $this->post(route('vendor-pdf.revoke', $delivery))->assertRedirect();
        auth()->forgetGuards();
        $this->post(route('vendor-pdf.reveal', $delivery->scheduling_token))->assertForbidden();
        $this->post(route('vendor-pdf.download', $delivery->scheduling_token))->assertForbidden();
    }

    public function test_other_vendor_wrong_password_missing_attestation_and_unverified_email_cannot_release_or_open(): void
    {
        extract($this->workflow());
        $this->travelTo($appointment->ends_at->addMinute());
        $this->actingAs($other)->post(route('vendor-pdf.release', $delivery), $this->releaseData($appointment))->assertNotFound();
        $this->post(route('vendor-pdf.revoke', $delivery))->assertNotFound();
        $this->actingAs($vendor)->post(route('vendor-pdf.release', $delivery), $this->releaseData($appointment, ['review_completed' => 0]))->assertSessionHasErrors('review_completed');
        $this->post(route('vendor-pdf.release', $delivery), $this->releaseData($appointment, ['pdf_password' => 'WrongPassword2026']))->assertSessionHasErrors('pdf_password');
        $this->assertNull($delivery->fresh()->password_released_at);
        $this->post(route('vendor-pdf.release', $delivery), $this->releaseData($appointment))->assertRedirect();
        auth()->forgetGuards();
        $this->post(route('vendor-pdf.download', $delivery->scheduling_token))->assertForbidden();
        $this->post(route('vendor-pdf.code', $delivery->scheduling_token))->assertRedirect();
        $this->post(route('vendor-pdf.verify', $delivery->scheduling_token), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('vendor-pdf.reveal', $delivery->scheduling_token))->assertForbidden();
        $this->get(route('vendor-pdf.show', 'invalid'))->assertNotFound();
        $this->assertStringNotContainsString('WorkflowPassword2026', VendorPdfAccessEvent::all()->toJson());
    }

    public function test_canceled_review_and_expired_or_revoked_access_are_blocked(): void
    {
        extract($this->workflow());
        $this->actingAs($vendor)->post(route('vendor-calendar.cancel', $appointment))->assertRedirect();
        $this->travelTo($appointment->ends_at->addMinute());
        $this->post(route('vendor-pdf.release', $delivery), $this->releaseData($appointment))->assertSessionHasErrors('release');
        $this->assertNull($delivery->fresh()->released_password);
        $this->post(route('vendor-pdf.revoke', $delivery))->assertRedirect();
        $this->post(route('vendor-pdf.release', $delivery), $this->releaseData($appointment))->assertSessionHasErrors('release');
        auth()->forgetGuards();
        $this->post(route('vendor-pdf.code', $delivery->scheduling_token))->assertForbidden();
    }

    public function test_expired_access_and_external_review_attestation_are_enforced(): void
    {
        extract($this->workflow());
        $delivery->update(['native_scheduling' => false]);
        $this->travelTo($appointment->ends_at->addMinute());
        $external = ['review_completed' => 1, 'pdf_password' => 'WorkflowPassword2026', 'external_review_reference' => 'Bookafy review 123', 'external_review_started_at' => $appointment->starts_at->toIso8601String(), 'external_review_ended_at' => $appointment->ends_at->toIso8601String()];
        $this->actingAs($vendor)->post(route('vendor-pdf.release', $delivery), array_merge($external, ['external_review_ended_at' => now()->addDay()->toIso8601String()]))->assertSessionHasErrors('external_review_ended_at');
        $this->post(route('vendor-pdf.release', $delivery), $external)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vendor_pdf_access_events', ['vendor_estimate_delivery_id' => $delivery->id, 'event' => 'external_review_attested']);
        $delivery->refresh();
        $this->travelTo($delivery->access_expires_at->copy()->addMinute());
        auth()->forgetGuards();
        $this->post(route('vendor-pdf.code', $delivery->scheduling_token))->assertForbidden();
        $this->post(route('vendor-pdf.reveal', $delivery->scheduling_token))->assertForbidden();
        $this->post(route('vendor-pdf.download', $delivery->scheduling_token))->assertForbidden();
    }

    public function test_preflight_detects_test_mail_transport_and_passes_configuration_checks_with_live_transport(): void
    {
        $this->artisan('vendor-workflow:preflight')->assertFailed();
        config(['mail.default' => 'smtp']);
        $this->artisan('vendor-workflow:preflight')->assertSuccessful();
    }

    public function test_a_new_pdf_after_a_review_requires_and_can_book_a_new_appointment(): void
    {
        extract($this->workflow());
        $this->travelTo($appointment->ends_at->addMinute());
        $this->actingAs($vendor)->post(route('vendor-estimates.send', $estimate), ['request_key' => (string) Str::uuid(), 'pdf_password' => 'RevisedPassword2026'])->assertRedirect()->assertSessionHasNoErrors();
        $revised = $estimate->deliveries()->firstOrFail();
        (new SendVendorEstimateDelivery($revised->id))->handle();
        $this->post(route('vendor-pdf.release', $revised), $this->releaseData($appointment, ['pdf_password' => 'RevisedPassword2026']))->assertSessionHasErrors('release');
        $this->post(route('vendor-calendar.publish'), ['date' => '2026-11-11', 'from' => '11:00', 'until' => '12:00'])->assertRedirect()->assertSessionHasNoErrors();
        auth()->forgetGuards();
        $this->get($revised->schedulingUrl())->assertOk();
        $nextSlot = VendorReviewSlot::where('starts_at', '>', now())->firstOrFail();
        $this->post(route('vendor-review.book', $revised->scheduling_token), ['slot_id' => $nextSlot->id, 'timezone' => 'UTC'])->assertRedirect()->assertSessionHasNoErrors();
        $nextReview = VendorReviewAppointment::where('vendor_estimate_delivery_id', $revised->id)->firstOrFail();
        $this->assertNotSame($appointment->id, $nextReview->id);
        $this->travelTo($nextReview->ends_at->addMinute());
        $this->actingAs($vendor)->post(route('vendor-pdf.release', $revised), $this->releaseData($nextReview, ['pdf_password' => 'RevisedPassword2026']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($revised->fresh()->password_released_at);
    }
}
