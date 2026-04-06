<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorRunBillingService;
use App\Services\V24\InstantEstimator\InstantEstimatorComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstantEstimatorComputeController extends Controller
{
    public function __construct(
        private InstantEstimatorComputeService $compute,
        private CalculatorRunBillingService $calculatorBilling,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'in:v24'],
            'scenario' => ['required', 'array'],
            'scenario.posts' => ['nullable', 'array'],
            'scenario.posts.*' => ['array'],
            'scenario.meta' => ['nullable', 'array'],
        ]);

        [$out, $remaining] = $this->calculatorBilling->chargeAndRun(
            $request->user(),
            'instant_estimator_v24',
            'instant-estimator',
            fn () => $this->compute->compute($validated['scenario']),
        );

        return response()->json([
            'ok' => true,
            'version' => 'v24',
            'credits_spent' => $this->calculatorBilling->creditsPerRun(),
            'credits_remaining' => $remaining,
            ...$out,
        ]);
    }
}

