<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCreditsController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * Create a Stripe Checkout session for a given pricing plan.
     */
    public function checkout(Request $request, PricingPlan $plan): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $plan->is_active) {
            abort(404);
        }

        $secretKey = config('services.stripe.secret');
        if (! $secretKey) {
            abort(500, 'Stripe secret key is not configured.');
        }

        $client = new StripeClient($secretKey);

        $successUrl = config('services.stripe.success_url') ?: url('/credits');
        $cancelUrl = config('services.stripe.cancel_url') ?: url('/credits');

        $session = $client->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => (int) round($plan->price * 100),
                    'product_data' => [
                        'name' => $plan->name,
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'user_id' => (string) $user->id,
                'pricing_plan_id' => (string) $plan->id,
                'tokens_included' => (string) $plan->tokens_included,
            ],
            'customer_email' => $user->email,
        ]);

        return response()->redirectTo($session->url);
    }

    /**
     * Stripe webhook to grant credits after successful payment.
     */
    public function webhook(Request $request): Response
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (! $secret || ! $sigHeader) {
            return response('Webhook not configured', 400);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response('Invalid signature', 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook payload error: ' . $e->getMessage());
            return response('Invalid payload', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;
            $metadata = $session->metadata ?? null;

            if ($metadata && isset($metadata->user_id, $metadata->tokens_included)) {
                $userId = (int) $metadata->user_id;
                $tokens = (int) $metadata->tokens_included;

                /** @var User|null $user */
                $user = User::find($userId);
                if ($user && $tokens > 0) {
                    $description = sprintf(
                        'Purchased %d credits via Stripe (%s)',
                        $tokens,
                        $session->id ?? 'checkout'
                    );

                    $this->walletService->addTokens(
                        $user,
                        $tokens,
                        type: 'purchase',
                        description: $description,
                        referenceType: 'stripe_checkout',
                        referenceId: $session->id ?? null
                    );
                }
            }
        }

        return response('OK', 200);
    }
}

