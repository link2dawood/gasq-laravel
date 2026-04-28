<?php

namespace App\Http\Controllers;

use App\Services\CalculatorStateStore;
use App\Services\GasqEstimatorService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class InstantEstimatorController extends Controller
{
    public function __construct(
        private GasqEstimatorService $estimator,
        private CalculatorStateStore $calculatorStateStore,
        private StripeCheckoutService $stripeCheckoutService,
    ) {}

    public function index(Request $request): View
    {
        $result = null;
        $feeCheckoutPaid = false;
        $feeCheckoutStatus = null;
        $postJobPublished = $request->query('post_job') === 'success';
        $postJobStatus = $postJobPublished
            ? session('success', 'Job announcement published successfully. Your estimate results are now unlocked.')
            : null;

        if ($request->isMethod('post') && $request->filled(['location', 'hours_per_week', 'number_of_guards'])) {
            $result = $this->estimator->estimate(
                $request->input('location'),
                (float) $request->input('hours_per_week', 0),
                (float) $request->input('number_of_guards', 1)
            );
            if ($result !== null) {
                session(['report_payload' => ['type' => 'instant-estimator', 'result' => $result]]);
                $this->calculatorStateStore->store(
                    $request->user(),
                    'instant-estimator',
                    $request->except('_token'),
                    $result,
                );
            }
        }

        if ($request->query('fee_checkout') === 'success' && $request->filled('session_id') && $request->user()) {
            try {
                $feeCheckoutPaid = $this->stripeCheckoutService->instantEstimatorFeeCheckoutPaid(
                    $request->user(),
                    (string) $request->query('session_id')
                );
            } catch (Throwable $e) {
                report($e);
            }

            $feeCheckoutStatus = $feeCheckoutPaid
                ? 'Card payment confirmed. Your Step 3 estimate is now unlocked.'
                : 'We could not verify the card payment for this estimate. Please try again.';
        } elseif ($request->query('fee_checkout') === 'cancelled') {
            $feeCheckoutStatus = 'Card payment was canceled before the estimate was unlocked.';
        }

        return view('calculators.instant-estimator', [
            'locations' => $this->estimator->getLocations(),
            'result' => $result,
            'feeCheckoutPaid' => $feeCheckoutPaid,
            'feeCheckoutStatus' => $feeCheckoutStatus,
            'postJobPublished' => $postJobPublished,
            'postJobStatus' => $postJobStatus,
        ]);
    }
}
