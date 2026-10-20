<?php

namespace Tests\Feature;

use App\Jobs\SendAppraisalDelivery;
use App\Mail\AppraisalAppointmentMail;
use App\Models\AppraisalAppointment;
use App\Models\AppraisalDelivery;
use App\Models\AppraisalReview;
use App\Models\CalculatorState;
use App\Models\User;
use App\Services\AppraisalReviewService;
use App\Services\CalendlyAppraisalService;
use App\Services\CostToProtectEstimate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AppraisalReviewAutomationTest extends TestCase
{
    use RefreshDatabase;

    private array $invitees = [];

    private array $events = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(12, 0));
        config(['appraisal_reviews.enabled' => true, 'appraisal_reviews.calendly_token' => 'test-token',
            'appraisal_reviews.webhook_signing_key' => 'test-secret',
            'appraisal_reviews.event_type_uri' => 'https://api.calendly.com/event_types/appraisal']);
        Queue::fake();
        Mail::fake();
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (isset($this->invitees[$request->url()])) {
                return Http::response(['resource' => $this->invitees[$request->url()]]);
            }
            if (isset($this->events[$request->url()])) {
                return Http::response(['resource' => $this->events[$request->url()]]);
            }

            return Http::response([], 404);
        });
    }

    private function review(): AppraisalReview
    {
        $owner = User::factory()->create(['user_type' => 'admin', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        $state = CalculatorState::create(['user_id' => $owner->id, 'calculator_type' => 'budget-calculator',
            'scenario' => ['meta' => ['baselineWage' => 25, 'hoursPerDay' => 24, 'daysPerWeek' => 7, 'weeksPerYear' => 52]], 'result' => []]);

        return AppraisalReview::create(['created_by' => $owner->id, 'calculator_state_id' => $state->id,
            'buyer_name' => 'Avery Buyer', 'buyer_email' => 'avery@example.test', 'report_number' => 'GASQ-TEST-'.Str::uuid()]);
    }

    private function appointment(AppraisalReview $review, string $id = 'first', int $hours = 48, ?string $old = null): AppraisalAppointment
    {
        $event = 'https://api.calendly.com/scheduled_events/'.$id;
        $uri = $event.'/invitees/'.$id;
        $this->invitees[$uri] = ['uri' => $uri, 'email' => $review->buyer_email, 'event' => $event,
            'tracking' => ['utm_content' => 'gasq_appraisal_'.$review->id], 'status' => 'active',
            'created_at' => now()->toIso8601String(), 'timezone' => 'America/New_York', 'old_invitee' => $old,
            'cancel_url' => 'https://calendly.com/cancellations/'.$id, 'reschedule_url' => 'https://calendly.com/reschedulings/'.$id];
        $this->events[$event] = ['event_type' => config('appraisal_reviews.event_type_uri'), 'status' => 'active',
            'start_time' => now()->addHours($hours)->toIso8601String(), 'end_time' => now()->addHours($hours)->addMinutes(30)->toIso8601String(),
            'location' => ['kind' => 'zoom_conference', 'join_url' => 'https://zoom.us/j/'.$id]];
        app(CalendlyAppraisalService::class)->ingest($uri);

        return AppraisalAppointment::where('invitee_uri', $uri)->firstOrFail();
    }

    private function ready(AppraisalReview $review): void
    {
        $path = 'appraisal-reviews/'.$review->id.'/final.pdf';
        $bytes = '%PDF-test /Encrypt '.$review->report_number;
        Storage::disk('local')->put($path, $bytes);
        $review->update(['prepared_at' => now(), 'ready_at' => now(), 'pdf_path' => $path, 'pdf_sha256' => hash('sha256', $bytes),
            'findings' => ['inHouseCost' => 150000, 'capitalRecovery' => 45000, 'paybackMonths' => 8.4, 'currency' => 'USD']]);
    }

    private function send(AppraisalReview $review, string $kind): void
    {
        $delivery = AppraisalDelivery::where('appraisal_review_id', $review->id)->where('kind', $kind)->latest('id')->firstOrFail();
        (new SendAppraisalDelivery($delivery->id))->handle();
    }

    public function test_booking_before_final_report_sends_booking_then_one_combined_report(): void
    {
        $review = $this->review();
        $appointment = $this->appointment($review);
        $this->send($review, 'booking');
        Mail::assertSent(AppraisalAppointmentMail::class, fn ($mail) => $mail->kind === 'booking' && $mail->attachments() === []);
        $this->ready($review);
        app(AppraisalReviewService::class)->queueReady($review);
        $this->send($review, 'report');
        app(CalendlyAppraisalService::class)->ingest($appointment->invitee_uri);
        app(AppraisalReviewService::class)->queueReady($review->fresh());
        $this->send($review, 'report');
        Mail::assertSent(AppraisalAppointmentMail::class, 2);
        $this->assertNotNull($review->fresh()->delivered_at);
    }

    public function test_final_report_before_booking_waits_then_sends_personalized_protected_attachment(): void
    {
        $review = $this->review();
        app(AppraisalReviewService::class)->finalize($review, 'MeetingOnlyPassword');
        Queue::assertNothingPushed();
        $review->refresh();
        $bytes = Storage::disk('local')->get($review->pdf_path);
        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertStringContainsString('/Encrypt', $bytes);
        $figures = app(CostToProtectEstimate::class)->build($review->calculatorState->scenario, $review->calculatorState->user);
        $this->assertEquals($figures['totalAnnualInternal'], $review->findings['inHouseCost']);
        $this->assertStringNotContainsString('MeetingOnlyPassword', json_encode($review->fresh()));
        $appointment = $this->appointment($review);
        $this->send($review, 'report');
        Mail::assertSent(AppraisalAppointmentMail::class, function ($mail) use ($review, $appointment) {
            $body = $mail->render();
            foreach (['Avery Buyer', $review->report_number, 'October 20, 2026', 'America/New_York', '8:00 AM EDT (-04:00)',
                'CONFIDENTIAL • PROPRIETARY • TRACEABLE', 'Built for CFO-Level Cost Analysis.', AppraisalAppointmentMail::PASSWORD_NOTICE] as $text) {
                $this->assertStringContainsString($text, $body);
            }
            $this->assertStringContainsString($appointment->reschedule_url, $body);
            $this->assertStringContainsString($appointment->cancel_url, $body);
            $this->assertStringNotContainsString('MeetingOnlyPassword', $body);
            $this->assertStringNotContainsString('GASQ-20261018-204310-V9', $body);
            $this->assertCount(1, $mail->attachments());
            $this->assertSame('info@getasecurityquote.com', $mail->envelope()->replyTo[0]->address);

            return $mail->hasTo('avery@example.test');
        });
    }

    public function test_reschedule_updates_links_and_zoom_without_resending_report_and_old_reminders_stop(): void
    {
        $review = $this->review();
        $this->ready($review);
        $first = $this->appointment($review);
        $this->send($review, 'report');
        app(AppraisalReviewService::class)->queueMessage($first, 'reminder_24h');
        $this->travel(1)->minutes();
        $second = $this->appointment($review->fresh(), 'second', 72, $first->invitee_uri);
        $this->assertSame('superseded', $first->fresh()->status);
        $this->send($review, 'reminder_24h');
        $this->send($review, 'rescheduled');
        Mail::assertSent(AppraisalAppointmentMail::class, 2);
        Mail::assertSent(AppraisalAppointmentMail::class, fn ($mail) => $mail->kind === 'rescheduled'
            && str_contains($mail->render(), 'https://zoom.us/j/second') && $mail->attachments() === []);
        $this->assertSame('scheduled', $second->fresh()->status);
    }

    public function test_cancellation_suppresses_queued_report_and_reminders_and_replayed_creation(): void
    {
        $review = $this->review();
        $this->ready($review);
        $appointment = $this->appointment($review);
        app(AppraisalReviewService::class)->queueMessage($appointment, 'reminder_24h');
        $this->invitees[$appointment->invitee_uri]['status'] = 'canceled';
        app(CalendlyAppraisalService::class)->ingest($appointment->invitee_uri);
        $this->send($review, 'report');
        $this->send($review, 'reminder_24h');
        Mail::assertNothingSent();
        $this->send($review, 'canceled');
        $this->assertSame('canceled', $appointment->fresh()->status);
        $this->assertNull($review->fresh()->delivered_at);
        app(CalendlyAppraisalService::class)->ingest($appointment->invitee_uri);
        $this->send($review, 'canceled');
        Mail::assertSent(AppraisalAppointmentMail::class, 1);
    }

    public function test_reminders_run_once_at_24_hours_and_one_hour_without_attachment_or_password(): void
    {
        $review = $this->review();
        $appointment = $this->appointment($review);
        $this->travel(24)->hours();
        $this->artisan('appraisals:process')->assertSuccessful();
        $this->send($review, 'reminder_24h');
        $this->artisan('appraisals:process')->assertSuccessful();
        $this->send($review, 'reminder_24h');
        $this->travel(23)->hours();
        $this->artisan('appraisals:process')->assertSuccessful();
        $this->send($review, 'reminder_1h');
        Mail::assertSent(AppraisalAppointmentMail::class, 2);
        Mail::assertSent(AppraisalAppointmentMail::class, fn ($mail) => $mail->kind === 'reminder_1h' && $mail->attachments() === []);
        $this->assertNull($appointment->fresh()->confirmed_at);
        $this->travel(2)->hours();
        $this->artisan('appraisals:process')->assertSuccessful();
        Mail::assertSent(AppraisalAppointmentMail::class, 2);
    }

    public function test_attendance_requires_explicit_signed_post_and_canceled_or_expired_links_fail(): void
    {
        $appointment = $this->appointment($this->review());
        $url = $appointment->viewUrl();
        $this->get($url)->assertOk()->assertSee('Confirm Appointment');
        $this->assertNull($appointment->fresh()->confirmed_at);
        $this->post($url, ['attendance' => 1])->assertRedirect();
        $this->assertNotNull($appointment->fresh()->confirmed_at);
        $this->get(str_replace('signature=', 'signature=bad', $url))->assertForbidden();
        $appointment->update(['status' => 'canceled']);
        $this->post($url, ['attendance' => 1])->assertStatus(409);
        $this->travel(10)->days();
        $this->get($url)->assertForbidden();
    }

    public function test_webhook_requires_correct_signature_recent_timestamp_and_matching_buyer_and_event_type(): void
    {
        $review = $this->review();
        $appointment = $this->appointment($review);
        $body = json_encode(['event' => 'invitee.created', 'payload' => ['uri' => $appointment->invitee_uri]]);
        $endpoint = route('appraisals.calendly-webhook');
        $this->call('POST', $endpoint, [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertUnauthorized();
        $header = 't='.now()->timestamp.',v1='.hash_hmac('sha256', now()->timestamp.'.'.$body, 'test-secret');
        $this->call('POST', $endpoint, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_CALENDLY_WEBHOOK_SIGNATURE' => $header], $body)->assertOk();
        $this->travel(4)->minutes();
        $this->call('POST', $endpoint, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_CALENDLY_WEBHOOK_SIGNATURE' => $header], $body)->assertUnauthorized();
        $this->invitees[$appointment->invitee_uri]['email'] = 'different@example.test';
        $this->assertFalse(app(CalendlyAppraisalService::class)->ingest($appointment->invitee_uri));
        $this->invitees[$appointment->invitee_uri]['email'] = $review->buyer_email;
        $this->events[$appointment->event_uri]['event_type'] = 'https://api.calendly.com/event_types/other';
        $this->assertFalse(app(CalendlyAppraisalService::class)->ingest($appointment->invitee_uri));
    }

    public function test_disabled_delivery_and_uncertain_smtp_acceptance_cannot_duplicate_report(): void
    {
        $review = $this->review();
        $this->ready($review);
        $this->appointment($review);
        config(['appraisal_reviews.enabled' => false]);
        $this->send($review, 'report');
        Mail::assertNothingSent();
        config(['appraisal_reviews.enabled' => true]);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Transport acceptance unknown'));
        $this->send($review, 'report');
        $this->assertDatabaseHas('appraisal_deliveries', ['appraisal_review_id' => $review->id, 'kind' => 'report', 'status' => 'uncertain']);
        $this->send($review, 'report');
        $this->assertNull($review->fresh()->delivered_at);
    }

    public function test_non_admin_cannot_finalize_and_final_report_cannot_be_overwritten(): void
    {
        $review = $this->review();
        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->post(route('admin.appraisal-reviews.finalize', $review), ['pdf_password' => 'MeetingOnlyPassword', 'approved_final' => 1])->assertForbidden();
        $this->ready($review);
        $this->actingAs(User::find($review->created_by))->post(route('admin.appraisal-reviews.finalize', $review), ['pdf_password' => 'MeetingOnlyPassword', 'approved_final' => 1])->assertSessionHasErrors('report');
    }

    public function test_out_of_order_cancellation_cannot_cancel_the_replacement_booking(): void
    {
        $review = $this->review();
        $first = $this->appointment($review);
        $this->travel(1)->minutes();
        $second = $this->appointment($review, 'second', 72, $first->invitee_uri);
        $this->invitees[$first->invitee_uri]['status'] = 'canceled';
        $this->invitees[$first->invitee_uri]['rescheduled'] = true;
        app(CalendlyAppraisalService::class)->ingest($first->invitee_uri);
        $this->assertSame('scheduled', $second->fresh()->status);
        $this->assertSame(1, $review->appointments()->where('status', 'scheduled')->count());
        $this->assertDatabaseMissing('appraisal_deliveries', ['appraisal_appointment_id' => $first->id, 'kind' => 'canceled']);
    }

    public function test_rebooking_after_suppression_delivers_the_report_once_for_the_new_appointment(): void
    {
        $review = $this->review();
        $this->ready($review);
        $first = $this->appointment($review);
        $this->invitees[$first->invitee_uri]['status'] = 'canceled';
        app(CalendlyAppraisalService::class)->ingest($first->invitee_uri);
        $this->send($review, 'report');
        $this->travel(1)->minutes();
        $second = $this->appointment($review, 'second', 72, $first->invitee_uri);
        $this->send($review, 'report');
        Mail::assertSent(AppraisalAppointmentMail::class, 1);
        Mail::assertSent(AppraisalAppointmentMail::class, fn ($mail) => $mail->appointment->id === $second->id && $mail->kind === 'report');
        $this->assertSame(1, AppraisalDelivery::where('appraisal_review_id', $review->id)->where('kind', 'report')->count());
    }

    public function test_dst_offset_matches_buyer_timezone_after_fall_back(): void
    {
        $review = $this->review();
        $appointment = $this->appointment($review);
        $this->events[$appointment->event_uri]['start_time'] = '2026-11-01T06:30:00Z';
        $this->events[$appointment->event_uri]['end_time'] = '2026-11-01T07:00:00Z';
        app(CalendlyAppraisalService::class)->ingest($appointment->invitee_uri);
        $body = (new AppraisalAppointmentMail($review, $appointment->fresh(), 'booking'))->render();
        $this->assertStringContainsString('1:30 AM EST (-05:00)', $body);
        $this->assertStringContainsString('America/New_York', $body);
    }

    public function test_corrupt_attachment_and_untrusted_api_url_are_rejected(): void
    {
        $review = $this->review();
        $this->ready($review);
        $appointment = $this->appointment($review);
        Storage::disk('local')->put($review->pdf_path, 'a different file');
        try {
            (new AppraisalAppointmentMail($review, $appointment, 'report'))->attachments();
            $this->fail('A replaced attachment must fail its checksum.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('integrity check', $exception->getMessage());
        }
        $this->expectException(HttpException::class);
        app(CalendlyAppraisalService::class)->resource('https://attacker.example/scheduled_events/fake');
    }

    public function test_late_queued_reminders_are_suppressed_after_activation_or_worker_delay(): void
    {
        $review = $this->review();
        $appointment = $this->appointment($review);
        app(AppraisalReviewService::class)->queueMessage($appointment, 'reminder_24h');
        app(AppraisalReviewService::class)->queueMessage($appointment, 'reminder_1h');
        $this->travel(47)->hours();
        $this->travel(6)->minutes();
        $this->send($review, 'reminder_24h');
        $this->send($review, 'reminder_1h');
        Mail::assertNothingSent();
        $this->assertSame(2, AppraisalDelivery::where('appraisal_review_id', $review->id)->where('status', 'suppressed')->count());
    }

    public function test_admin_can_register_review_and_render_management_page_without_password_data(): void
    {
        $review = $this->review();
        $admin = User::find($review->created_by);
        $this->actingAs($admin)->post(route('admin.appraisal-reviews.store'), [
            'calculator_state_id' => $review->calculator_state_id, 'buyer_name' => 'Morgan Buyer', 'buyer_email' => 'morgan@example.test',
        ])->assertRedirect();
        $this->assertDatabaseHas('appraisal_reviews', ['buyer_email' => 'morgan@example.test', 'ready_at' => null]);
        $this->get(route('admin.appraisal-reviews.index'))->assertOk()->assertSee('Morgan Buyer')->assertSee('Link an appointment booked earlier');
    }

    public function test_preflight_and_test_send_stop_when_accounts_are_not_connected(): void
    {
        config(['appraisal_reviews.calendly_token' => null]);
        $this->artisan('appraisals:preflight')->assertFailed();
        $this->artisan('appraisals:test-email '.Str::uuid().' --send')->assertFailed();
        Mail::assertNothingSent();
    }

    public function test_existing_booking_without_tracking_can_only_be_linked_explicitly_and_to_matching_buyer(): void
    {
        $review = $this->review();
        $appointment = $this->appointment($review);
        $uri = $appointment->invitee_uri;
        AppraisalDelivery::where('appraisal_appointment_id', $appointment->id)->delete();
        $appointment->delete();
        $this->invitees[$uri]['tracking'] = [];
        $this->assertFalse(app(CalendlyAppraisalService::class)->ingest($uri));
        $this->assertTrue(app(CalendlyAppraisalService::class)->ingest($uri, $review->id));
        $other = $this->review();
        $this->actingAs(User::find($other->created_by))->post(route('admin.appraisal-reviews.appointment', $other), ['invitee_uri' => $uri])->assertStatus(409);
    }

    public function test_host_updates_refresh_zoom_reset_attendance_and_reschedule_reminders(): void
    {
        $review = $this->review();
        $appointment = $this->appointment($review);
        $appointment->update(['confirmed_at' => now()]);
        app(AppraisalReviewService::class)->queueMessage($appointment, 'reminder_24h');
        $oldReminder = AppraisalDelivery::where('kind', 'reminder_24h')->firstOrFail();
        $this->events[$appointment->event_uri]['start_time'] = now()->addHours(72)->toIso8601String();
        $this->events[$appointment->event_uri]['end_time'] = now()->addHours(72)->addMinutes(30)->toIso8601String();
        $this->events[$appointment->event_uri]['location']['join_url'] = 'https://zoom.us/j/replacement';
        app(CalendlyAppraisalService::class)->ingest($appointment->invitee_uri);
        $appointment->refresh();
        $this->assertNull($appointment->confirmed_at);
        $this->assertSame('https://zoom.us/j/replacement', $appointment->meeting_details);
        $this->send($review, 'rescheduled');
        $this->travel(48)->hours();
        (new SendAppraisalDelivery($oldReminder->id))->handle();
        $this->assertSame('suppressed', $oldReminder->fresh()->status);
        $this->artisan('appraisals:process')->assertSuccessful();
        $this->send($review, 'reminder_24h');
        Mail::assertSent(AppraisalAppointmentMail::class, 2);
    }
}
