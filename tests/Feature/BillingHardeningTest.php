<?php

namespace Tests\Feature;

use App\Listeners\SendCreditsGrantedNotification;
use App\Models\Bid;
use App\Models\Coupon;
use App\Models\JobPosting;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BillingHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_stripe_webhook_is_idempotent_for_duplicate_checkout_events(): void
    {
        Queue::fake();

        config()->set('services.stripe.secret', 'sk_test_123');
        config()->set('services.stripe.webhook_secret', 'whsec_test_123');

        $user = User::factory()->create([
            'user_type' => 'vendor',
            'phone' => null,
            'phone_verified' => true,
        ]);

        $plan = PricingPlan::create([
            'name' => 'Starter',
            'price' => 29.00,
            'tokens_included' => 25,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $payload = json_encode([
            'id' => 'evt_test_checkout_completed',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_duplicate_once',
                    'object' => 'checkout.session',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'pricing_plan_id' => (string) $plan->id,
                        'tokens_included' => '25',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $headers = [
            'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload, 'whsec_test_123'),
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->call('POST', route('stripe.webhook'), [], [], [], $headers, $payload)
            ->assertOk();
        $this->call('POST', route('stripe.webhook'), [], [], [], $headers, $payload)
            ->assertOk();

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 25,
        ]);

        $this->assertSame(1, Transaction::query()
            ->where('reference_type', 'stripe_checkout')
            ->where('reference_id', 'cs_test_duplicate_once')
            ->count());

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'purchase',
            'reference_type' => 'stripe_checkout',
            'reference_id' => 'cs_test_duplicate_once',
            'idempotency_key' => 'stripe_checkout:cs_test_duplicate_once',
            'tokens_change' => 25,
        ]);
    }

    public function test_coupon_redemption_queues_credits_granted_notification_listener(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'user_type' => 'vendor',
            'phone' => null,
            'phone_verified' => true,
        ]);

        Coupon::create([
            'code' => 'QUEUED12',
            'credits_amount' => 12,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('credits.redeem'), ['code' => 'QUEUED12'])
            ->assertRedirect(route('credits'));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
            return $job->class === SendCreditsGrantedNotification::class;
        });
    }

    public function test_transactions_idempotency_key_is_unique(): void
    {
        $user = User::factory()->create([
            'user_type' => 'vendor',
            'phone' => null,
            'phone_verified' => true,
        ]);

        Transaction::query()->create([
            'user_id' => $user->id,
            'tokens_change' => 10,
            'type' => 'purchase',
            'reference_type' => 'stripe_checkout',
            'reference_id' => 'cs_test_unique_key',
            'idempotency_key' => 'stripe_checkout:cs_test_unique_key',
            'description' => 'Initial credit grant',
            'balance_after' => 10,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Transaction::query()->create([
            'user_id' => $user->id,
            'tokens_change' => 10,
            'type' => 'purchase',
            'reference_type' => 'stripe_checkout',
            'reference_id' => 'cs_test_unique_key',
            'idempotency_key' => 'stripe_checkout:cs_test_unique_key',
            'description' => 'Duplicate credit grant',
            'balance_after' => 20,
        ]);
    }

    public function test_bid_notification_remains_queueable(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone' => null,
            'phone_verified' => true,
        ]);

        $vendor = User::factory()->create([
            'user_type' => 'vendor',
            'phone' => null,
            'phone_verified' => true,
        ]);

        $job = JobPosting::query()->create([
            'user_id' => $buyer->id,
            'title' => 'Queueable bid',
            'guards_per_shift' => 1,
        ]);

        $bid = Bid::query()->create([
            'job_posting_id' => $job->id,
            'user_id' => $vendor->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(ShouldQueue::class, new BidNotification($bid, 'submitted'));
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}
