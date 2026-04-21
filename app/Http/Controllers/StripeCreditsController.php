<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\QueryException;
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
            $sessionId = $session->id ?? null;

            if ($metadata && $sessionId && isset($metadata->user_id, $metadata->tokens_included)) {
                $userId = (int) $metadata->user_id;
                $tokens = (int) $metadata->tokens_included;
                $idempotencyKey = 'stripe_checkout:' . $sessionId;

                $alreadyProcessed = Transaction::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->exists();

                if ($alreadyProcessed) {
                    Log::info('Stripe checkout webhook already processed; skipping duplicate grant.', [
                        'session_id' => $sessionId,
                        'user_id' => $userId,
                    ]);

                    return response('OK', 200);
                }

                /** @var User|null $user */
                $user = User::find($userId);
                if ($user && $tokens > 0) {
                    $description = sprintf(
                        'Purchased %d credits via Stripe (%s)',
                        $tokens,
                        $sessionId
                    );

                    try {
                        $this->walletService->addTokens(
                            $user,
                            $tokens,
                            type: 'purchase',
                            description: $description,
                            referenceType: 'stripe_checkout',
                            referenceId: $sessionId,
                            idempotencyKey: $idempotencyKey,
                        );
                    } catch (QueryException $e) {
                        if ($this->isDuplicateTransactionKey($e)) {
                            Log::warning('Stripe checkout duplicate credit grant prevented by transaction idempotency key.', [
                                'session_id' => $sessionId,
                                'user_id' => $userId,
                            ]);

                            return response('OK', 200);
                        }

                        throw $e;
                    }
                }
            }
        }

        return response('OK', 200);
    }

    private function isDuplicateTransactionKey(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        return $sqlState === '23000' || $driverCode === 1062 || $driverCode === 19;
    }
}
