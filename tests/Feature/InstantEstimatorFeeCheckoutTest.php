<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class InstantEstimatorFeeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_authenticated_user_can_start_instant_estimator_fee_checkout(): void
    {
        $user = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('createInstantEstimatorFeeCheckout')
                ->once()
                ->withArgs(function (User $passedUser, int $amountCents, string $successUrl, string $cancelUrl) use ($user): bool {
                    return $passedUser->is($user)
                        && $amountCents === 1250
                        && str_contains($successUrl, 'fee_checkout=success')
                        && str_contains($successUrl, 'session_id={CHECKOUT_SESSION_ID}')
                        && str_contains($cancelUrl, 'fee_checkout=cancelled');
                })
                ->andReturn('https://checkout.stripe.test/session_123');
        });

        $this->actingAs($user)
            ->postJson(route('instant-estimator.fee-checkout'), [
                'appraisal_fee' => 12.50,
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'checkout_url' => 'https://checkout.stripe.test/session_123',
            ]);
    }

    public function test_instant_estimator_fee_checkout_requires_authentication(): void
    {
        $this->postJson(route('instant-estimator.fee-checkout'), [
            'appraisal_fee' => 12.50,
        ])->assertStatus(401);
    }

    public function test_instant_estimator_fee_checkout_validates_positive_fee_amount(): void
    {
        $user = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('instant-estimator.fee-checkout'), [
                'appraisal_fee' => 0.25,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['appraisal_fee']);
    }

    public function test_instant_estimator_page_marks_fee_path_paid_only_after_verified_checkout_success(): void
    {
        $user = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('instantEstimatorFeeCheckoutPaid')
                ->once()
                ->with($user, 'cs_test_paid')
                ->andReturn(true);
        });

        $this->actingAs($user)
            ->get(route('instant-estimator.index', [
                'fee_checkout' => 'success',
                'session_id' => 'cs_test_paid',
            ]))
            ->assertOk()
            ->assertSee('const FEE_CHECKOUT_PAID = true;', false)
            ->assertSeeText('Card payment confirmed. Your Step 3 estimate is now unlocked.');
    }

    public function test_instant_estimator_page_keeps_fee_path_locked_when_checkout_cannot_be_verified(): void
    {
        $user = User::factory()->create([
            'user_type' => 'buyer',
            'phone_verified' => true,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('instantEstimatorFeeCheckoutPaid')
                ->once()
                ->with($user, 'cs_test_unverified')
                ->andReturn(false);
        });

        $this->actingAs($user)
            ->get(route('instant-estimator.index', [
                'fee_checkout' => 'success',
                'session_id' => 'cs_test_unverified',
            ]))
            ->assertOk()
            ->assertSee('const FEE_CHECKOUT_PAID = false;', false)
            ->assertSeeText('We could not verify the card payment for this estimate. Please try again.');
    }
}
