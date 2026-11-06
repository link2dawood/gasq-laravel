<?php

namespace Tests\Feature;

use App\Contracts\AppraisalScheduler;
use App\Models\AppraisalAppointment;
use App\Models\AppraisalDelivery;
use App\Models\AppraisalReview;
use App\Models\CalculatorState;
use App\Models\User;
use App\Services\BookafyAppraisalService;
use App\Services\CalendlyAppraisalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BookafyAppraisalTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'bookafy-callback-secret-at-least-32-chars';

    /** @var array<string, array<string, mixed>> */
    private array $bookings = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 6)->setTime(12, 0));
        config([
            'appraisal_reviews.enabled' => true,
            'appraisal_reviews.provider' => 'bookafy',
            'appraisal_reviews.bookafy.api_key' => 'test-key',
            'appraisal_reviews.bookafy.webhook_token' => self::SECRET,
            'appraisal_reviews.bookafy.staff_email' => 'staff@getasecurityquote.test',
            'appraisal_reviews.bookafy.service_id' => '352084',
            'appraisal_reviews.bookafy.booking_url' => 'https://getasecurityquote.bookafy.com/schedule/?type=iframe',
            'appraisal_reviews.bookafy.cancel_url' => 'https://getasecurityquote.bookafy.com/cancel/{cancel_token}',
            'appraisal_reviews.bookafy.reschedule_url' => 'https://getasecurityquote.bookafy.com/reschedule/{update_token}',
            'appraisal_reviews.bookafy.reference_field' => 'Reference',
        ]);
        Queue::fake();
        Mail::fake();
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response(['response' => ['message' => 'Success', 'appointment' => array_values($this->bookings)]]));
    }

    private function review(string $email = 'avery@example.test'): AppraisalReview
    {
        $owner = User::factory()->create(['user_type' => 'admin', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        $state = CalculatorState::create(['user_id' => $owner->id, 'calculator_type' => 'budget-calculator',
            'scenario' => ['meta' => ['baselineWage' => 25, 'hoursPerDay' => 24, 'daysPerWeek' => 7, 'weeksPerYear' => 52]], 'result' => []]);

        return AppraisalReview::create(['created_by' => $owner->id, 'calculator_state_id' => $state->id,
            'buyer_name' => 'Avery Buyer', 'buyer_email' => $email, 'report_number' => 'GASQ-TEST-'.Str::uuid()]);
    }

    /** A Bookafy listing row, shaped as the live account returns it. */
    private function booking(array $overrides = []): array
    {
        $id = $overrides['id'] ?? 182875666;
        $booking = array_merge([
            'id' => $id,
            'service_id' => 352084,
            'category_id' => 46141,
            'appointment_start_time' => now()->addHours(48)->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'appointment_end_time' => now()->addHours(48)->addMinutes(30)->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'created_at' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'time_zone' => 'Eastern Time (US & Canada)',
            'is_active' => true,
            'deleted_at' => null,
            'token' => 'tok'.$id,
            'cancel_token' => 'cancel'.$id,
            'update_token' => 'update'.$id,
            'zoom_join_url' => 'https://zoom.us/j/'.$id,
            'appointment_customer_info' => ['name' => 'Avery Buyer', 'email' => 'avery@example.test'],
        ], $overrides);
        $this->bookings[(string) $booking['id']] = $booking;

        return $booking;
    }

    private function ingest(int|string $id, ?string $reviewId = null): bool
    {
        return app(BookafyAppraisalService::class)->ingest(BookafyAppraisalService::reference($id), $reviewId);
    }

    public function test_booking_is_matched_to_the_review_by_buyer_email_and_stored_in_utc(): void
    {
        $review = $this->review();
        $this->booking();

        $this->assertTrue($this->ingest(182875666));
        $appointment = AppraisalAppointment::firstOrFail();
        $this->assertSame($review->id, $appointment->appraisal_review_id);
        $this->assertSame('scheduled', $appointment->status);
        $this->assertSame('America/New_York', $appointment->timezone);
        $this->assertSame(now()->addHours(48)->utc()->format('Y-m-d H:i'), $appointment->starts_at->format('Y-m-d H:i'));
        $this->assertSame('https://zoom.us/j/182875666', $appointment->meeting_details);
        $this->assertSame('https://getasecurityquote.bookafy.com/cancel/cancel182875666', $appointment->cancel_url);
        $this->assertSame('https://getasecurityquote.bookafy.com/reschedule/update182875666', $appointment->reschedule_url);
        $this->assertDatabaseHas('appraisal_deliveries', ['kind' => 'booking', 'appraisal_review_id' => $review->id]);
    }

    public function test_booking_reference_question_wins_over_the_email_match(): void
    {
        $this->review();
        $target = $this->review();
        $this->booking(['appointment_customer_info' => ['email' => 'avery@example.test', 'Reference' => 'gasq_appraisal_'.$target->id]]);

        $this->assertTrue($this->ingest(182875666));
        $this->assertSame($target->id, AppraisalAppointment::firstOrFail()->appraisal_review_id);
    }

    public function test_a_booking_of_another_service_is_ignored(): void
    {
        $this->review();
        $this->booking(['service_id' => 58227]);

        $this->assertFalse($this->ingest(182875666));
        $this->assertSame(0, AppraisalAppointment::count());
    }

    public function test_a_booking_whose_email_matches_no_review_is_ignored(): void
    {
        $this->review();
        $this->booking(['appointment_customer_info' => ['email' => 'someone-else@example.test']]);

        $this->assertFalse($this->ingest(182875666));
        $this->assertSame(0, AppraisalAppointment::count());
    }

    public function test_a_moved_booking_clears_attendance_and_queues_one_reschedule_message(): void
    {
        $this->review();
        $this->booking();
        $this->ingest(182875666);
        AppraisalAppointment::firstOrFail()->update(['confirmed_at' => now()]);

        $this->booking(['appointment_start_time' => now()->addHours(72)->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'appointment_end_time' => now()->addHours(72)->addMinutes(30)->utc()->format('Y-m-d\TH:i:s.v\Z')]);
        $this->assertTrue($this->ingest(182875666));

        $appointment = AppraisalAppointment::firstOrFail();
        $this->assertNull($appointment->confirmed_at);
        $this->assertSame(1, AppraisalDelivery::where('kind', 'rescheduled')->count());
        // A repeat callback for the same booking must not queue a second message.
        $this->ingest(182875666);
        $this->assertSame(1, AppraisalDelivery::where('kind', 'rescheduled')->count());
    }

    public function test_a_cancelled_booking_is_marked_cancelled_once(): void
    {
        $this->review();
        $this->booking();
        $this->ingest(182875666);

        $this->booking(['is_active' => false]);
        $this->assertTrue($this->ingest(182875666));
        $this->assertSame('canceled', AppraisalAppointment::firstOrFail()->status);
        // A repeated callback for the same cancellation must not notify twice.
        $this->ingest(182875666);
        $this->assertSame(1, AppraisalDelivery::where('kind', 'canceled')->count());
    }

    public function test_a_booking_bookafy_no_longer_lists_is_treated_as_cancelled(): void
    {
        $this->review();
        $this->booking();
        $this->ingest(182875666);

        $this->bookings = [];
        $this->assertTrue($this->ingest(182875666));
        $this->assertSame('canceled', AppraisalAppointment::firstOrFail()->status);
    }

    public function test_a_second_booking_supersedes_the_first(): void
    {
        $this->review();
        $this->booking();
        $this->ingest(182875666);
        $this->travel(1)->minutes();
        $this->booking(['id' => 182875999]);
        $this->assertTrue($this->ingest(182875999));

        $this->assertSame('superseded', AppraisalAppointment::where('invitee_uri', 'bookafy:appointment:182875666')->firstOrFail()->status);
        $this->assertSame('scheduled', AppraisalAppointment::where('invitee_uri', 'bookafy:appointment:182875999')->firstOrFail()->status);
    }

    public function test_an_unmappable_time_zone_is_refused(): void
    {
        $this->review();
        $this->booking(['time_zone' => 'Nowhere Standard Time']);

        $this->expectException(HttpException::class);
        $this->ingest(182875666);
    }

    public function test_the_callback_requires_the_secret_in_the_url(): void
    {
        $this->review();
        $this->booking();

        $this->postJson(route('appraisals.bookafy-webhook', ['token' => 'wrong-secret-wrong-secret-wrong-x']),
            ['appointment' => ['id' => 182875666]])->assertNotFound();
        $this->assertSame(0, AppraisalAppointment::count());

        $this->postJson(route('appraisals.bookafy-webhook', ['token' => self::SECRET]),
            ['appointment' => ['id' => 182875666]])->assertOk()->assertJson(['processed' => true]);
        $this->assertSame(1, AppraisalAppointment::count());
    }

    public function test_the_callback_body_is_never_trusted_for_booking_details(): void
    {
        $review = $this->review();
        $this->booking();

        $this->postJson(route('appraisals.bookafy-webhook', ['token' => self::SECRET]), ['appointment' => [
            'id' => 182875666, 'appointment_customer_info' => ['email' => 'attacker@example.test'],
            'service_id' => 1, 'zoom_join_url' => 'https://attacker.example/join',
        ]])->assertOk();

        $appointment = AppraisalAppointment::firstOrFail();
        $this->assertSame($review->id, $appointment->appraisal_review_id);
        $this->assertSame('https://zoom.us/j/182875666', $appointment->meeting_details);
    }

    public function test_the_configured_provider_is_the_one_the_flow_uses(): void
    {
        $this->assertInstanceOf(BookafyAppraisalService::class, app(AppraisalScheduler::class));
        config(['appraisal_reviews.provider' => 'calendly']);
        $this->assertInstanceOf(CalendlyAppraisalService::class, app(AppraisalScheduler::class));
    }

    public function test_the_buyer_booking_link_carries_the_review_reference(): void
    {
        $review = $this->review();
        $url = $review->bookingUrl();

        $this->assertStringContainsString('type=iframe&', $url);
        $this->assertStringContainsString('Reference=gasq_appraisal_'.$review->id, $url);
        $this->assertStringContainsString('email=avery%40example.test', $url);
    }
}
