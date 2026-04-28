<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;
use Stripe\StripeClient;

class StripeCheckoutService
{
    public function createInstantEstimatorFeeCheckout(
        User $user,
        int $amountCents,
        string $successUrl,
        string $cancelUrl,
    ): string {
        $client = $this->client();

        $session = $client->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => 'GASQ Instant Estimator Appraisal Fee',
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'user_id' => (string) $user->id,
                'checkout_context' => 'instant_estimator_fee',
                'appraisal_fee_cents' => (string) $amountCents,
            ],
            'customer_email' => $user->email,
        ]);

        $checkoutUrl = $session->url ?? null;
        if (! is_string($checkoutUrl) || $checkoutUrl === '') {
            throw new RuntimeException('Stripe did not return a checkout URL for the instant estimator fee.');
        }

        return $checkoutUrl;
    }

    public function instantEstimatorFeeCheckoutPaid(User $user, string $sessionId): bool
    {
        $client = $this->client();
        $session = $client->checkout->sessions->retrieve($sessionId, []);
        $metadata = $session->metadata ?? null;

        return ($session->payment_status ?? null) === 'paid'
            && (string) ($metadata->checkout_context ?? '') === 'instant_estimator_fee'
            && (string) ($metadata->user_id ?? '') === (string) $user->id;
    }

    private function client(): StripeClient
    {
        $secretKey = config('services.stripe.secret');
        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        return new StripeClient($secretKey);
    }
}
