<?php

namespace Tests\Feature;

use App\Jobs\SendVendorEstimateDelivery;
use App\Mail\VendorEstimateMail;
use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorLeadEstimate;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class VendorEstimateDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Queue::fake();
        Mail::fake();
        Storage::fake('local');
        config(['appraisal_reviews.provider' => 'bookafy', 'appraisal_reviews.bookafy.booking_url' => 'https://example.bookafy.com/schedule/?type=iframe']);
    }

    private function estimate(): VendorLeadEstimate
    {
        $vendor = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now()]);
        $lead = VendorBuyerLead::create(['vendor_id' => $vendor->id, 'contact_name' => 'Avery Buyer', 'email' => 'avery@example.test', 'phone' => '+14045550123', 'site_address' => '100 Main Street', 'service_type' => 'Guarding', 'scope' => 'Lobby security.']);
        $this->actingAs($vendor);

        return VendorLeadEstimate::create(['vendor_id' => $vendor->id, 'vendor_buyer_lead_id' => $lead->id, 'number' => 'GASQ-EST-'.Str::uuid(), 'title' => 'Lobby estimate', 'buyer' => $lead->only(['contact_name', 'email', 'phone', 'site_address']), 'currency' => 'USD', 'scope' => 'Lobby security.', 'hourly_rate' => 25.50, 'weekly_hours' => 80, 'weeks_per_year' => 52, 'one_time_cost' => 500, 'valid_until' => now()->addDays(30), 'status' => 'ready']);
    }

    private function prepare(VendorLeadEstimate $estimate, ?string $key = null): VendorEstimateDelivery
    {
        $this->post(route('vendor-estimates.send', $estimate), ['pdf_password' => 'DeliveryTestPassword', 'request_key' => $key ?? (string) Str::uuid(), 'email' => 'forged@example.test'])->assertRedirect(route('vendor-estimates.edit', $estimate))->assertSessionHasNoErrors();

        return $estimate->deliveries()->firstOrFail();
    }

    public function test_send_snapshots_encrypted_pdf_and_queues_only_delivery_id_then_sends_once(): void
    {
        $estimate = $this->estimate();
        $key = (string) Str::uuid();
        $delivery = $this->prepare($estimate, $key);
        $this->assertSame('avery@example.test', $delivery->recipient);
        $this->assertSame('queued', $delivery->status);
        $bytes = Storage::disk('local')->get($delivery->pdf_path);
        $this->assertStringContainsString('/Encrypt', $bytes);
        $this->assertSame(hash('sha256', $bytes), $delivery->pdf_sha256);
        $this->assertStringNotContainsString('DeliveryTestPassword', $delivery->toJson());
        Queue::assertPushed(SendVendorEstimateDelivery::class, function ($job) use ($delivery) {
            $this->assertStringNotContainsString('DeliveryTestPassword', serialize($job));

            return $job->deliveryId === $delivery->id;
        });
        $this->prepare($estimate, $key);
        $this->assertDatabaseCount('vendor_estimate_deliveries', 1);
        $estimate->update(['title' => 'Changed after send', 'hourly_rate' => 99]);
        $job = new SendVendorEstimateDelivery($delivery->id);
        $job->handle();
        $job->handle();
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->sent_at);
        Mail::assertSent(VendorEstimateMail::class, function ($mail) use ($delivery, $bytes) {
            $this->assertStringContainsString('Lobby estimate', $mail->render());
            $this->assertStringNotContainsString('Changed after send', $mail->render());
            $this->assertStringNotContainsString('DeliveryTestPassword', $mail->render());
            $this->assertStringNotContainsString('106,580', $mail->render());
            $this->assertStringContainsString($delivery->schedulingUrl(), $mail->render());
            $this->assertTrue($mail->hasAttachedData($bytes, $delivery->snapshot['number'].'.pdf', ['mime' => 'application/pdf']));

            return $mail->hasTo('avery@example.test');
        });
        Mail::assertSentCount(1);
        $this->get(route('vendor-estimates.edit', $estimate))->assertOk()->assertSee('Sent (transport accepted)');
        $this->get(route('vendor-estimates.index'))->assertOk()->assertSee('Sent (transport accepted)');
    }

    public function test_private_scheduling_links_are_unique_expire_and_track_only_link_opening(): void
    {
        $estimate = $this->estimate();
        $first = $this->prepare($estimate);
        $second = $this->prepare($estimate);
        $this->assertNotSame($first->schedulingUrl(), $second->schedulingUrl());
        $this->assertNotSame($first->scheduling_token, $first->getRawOriginal('scheduling_token'));
        $first->update(['status' => 'sent']);
        auth()->forgetGuards();
        $this->get($first->schedulingUrl())->assertRedirect($first->scheduling_target)->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertNotNull($first->fresh()->scheduling_opened_at);
        $this->assertStringContainsString('type=iframe&Reference=gasq_estimate_', $first->scheduling_target);
        $this->get($second->schedulingUrl())->assertNotFound();
        $first->update(['scheduling_expires_at' => now()->subMinute()]);
        $this->get($first->schedulingUrl())->assertStatus(410);
        $this->get(route('vendor-estimate-scheduling', 'invalid'))->assertNotFound();
    }

    public function test_invalid_password_unready_estimate_and_missing_scheduler_do_not_queue_emails(): void
    {
        $estimate = $this->estimate();
        $url = route('vendor-estimates.send', $estimate);
        $this->post($url, ['pdf_password' => 'short', 'request_key' => (string) Str::uuid()])->assertSessionHasErrors('pdf_password');
        $this->assertArrayNotHasKey('pdf_password', session()->getOldInput());
        $estimate->update(['status' => 'draft']);
        $this->post($url, ['pdf_password' => 'DeliveryTestPassword', 'request_key' => (string) Str::uuid()])->assertSessionHasErrors('delivery');
        $estimate->update(['status' => 'ready']);
        config(['appraisal_reviews.bookafy.booking_url' => 'https://evil.example/schedule']);
        $this->post($url, ['pdf_password' => 'DeliveryTestPassword', 'request_key' => (string) Str::uuid()])->assertSessionHasErrors('delivery');
        $this->assertDatabaseCount('vendor_estimate_deliveries', 0);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
    }

    public function test_transport_failure_is_uncertain_and_does_not_automatically_resend(): void
    {
        $estimate = $this->estimate();
        $delivery = $this->prepare($estimate);
        Mail::shouldReceive('to')->once()->with('avery@example.test')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Sensitive provider error'));
        $job = new SendVendorEstimateDelivery($delivery->id);
        $job->handle();
        $job->handle();
        $this->assertSame('uncertain', $delivery->fresh()->status);
        $this->assertSame('TransportException', $delivery->fresh()->failure_code);
        $this->assertStringNotContainsString('Sensitive provider error', $delivery->fresh()->toJson());
    }

    public function test_corrupt_attachment_or_archived_estimate_fails_without_sending(): void
    {
        $estimate = $this->estimate();
        $delivery = $this->prepare($estimate);
        Storage::disk('local')->put($delivery->pdf_path, 'corrupt');
        (new SendVendorEstimateDelivery($delivery->id))->handle();
        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertSame('PdfIntegrityCheckFailed', $delivery->fresh()->failure_code);
        $other = $this->prepare($estimate);
        $estimate->update(['status' => 'archived']);
        (new SendVendorEstimateDelivery($other->id))->handle();
        $this->assertSame('EstimateNotReady', $other->fresh()->failure_code);
        Mail::assertNothingSent();
    }

    public function test_other_vendor_cannot_send_or_view_delivery_history(): void
    {
        $estimate = $this->estimate();
        $this->prepare($estimate);
        $other = User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now()]);
        $this->actingAs($other)->post(route('vendor-estimates.send', $estimate), ['pdf_password' => 'DeliveryTestPassword', 'request_key' => (string) Str::uuid()])->assertNotFound();
        $this->get(route('vendor-estimates.edit', $estimate))->assertNotFound();
        $this->assertDatabaseCount('vendor_estimate_deliveries', 1);
    }

    public function test_fallback_booking_link_uses_the_actual_provider_reference_field(): void
    {
        config(['appraisal_reviews.provider' => 'calendly', 'appraisal_reviews.booking_url' => null]);
        $estimate = $this->estimate();
        $delivery = $this->prepare($estimate);
        $this->assertStringContainsString('https://example.bookafy.com/', $delivery->scheduling_target);
        $this->assertStringContainsString('Reference=gasq_estimate_', $delivery->scheduling_target);
        $this->assertStringNotContainsString('utm_content=', $delivery->scheduling_target);
        $this->assertSame('calendly', config('appraisal_reviews.provider'));
    }
}
