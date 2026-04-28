<?php

namespace App\Http\Controllers;

use App\Services\StripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class InstantEstimatorFeeCheckoutController extends Controller
{
    public function __construct(
        private StripeCheckoutService $stripeCheckoutService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'appraisal_fee' => ['required', 'numeric', 'min:0.50', 'max:999999.99'],
        ]);

        $amountCents = (int) round(((float) $data['appraisal_fee']) * 100);
        if ($amountCents < 50) {
            return response()->json([
                'message' => 'The appraisal fee must be at least $0.50 before checkout.',
            ], 422);
        }

        $successUrl = route('instant-estimator.index', ['fee_checkout' => 'success']);
        $successUrl .= (str_contains($successUrl, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';

        $cancelUrl = route('instant-estimator.index', ['fee_checkout' => 'cancelled']);

        try {
            $checkoutUrl = $this->stripeCheckoutService->createInstantEstimatorFeeCheckout(
                $request->user(),
                $amountCents,
                $successUrl,
                $cancelUrl,
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'We could not start the card checkout right now.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'checkout_url' => $checkoutUrl,
        ]);
    }
}
