<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorRunBillingService;
use App\Services\V24\Standalone\StandaloneV24ComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StandaloneV24ComputeController extends Controller
{
    public function __construct(
        private StandaloneV24ComputeService $compute,
        private CalculatorRunBillingService $calculatorBilling,
    ) {}

    public function __invoke(Request $request, string $type): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'in:v24'],
            'scenario' => ['required', 'array'],
            'scenario.meta' => ['nullable', 'array'],
        ]);

        [$out, $remaining] = $this->calculatorBilling->chargeAndRun(
            $request->user(),
            'standalone_v24',
            $type,
            fn () => $this->compute->compute($type, $validated['scenario']),
        );

        return response()->json([
            'ok' => true,
            'version' => 'v24',
            'type' => $type,
            'credits_spent' => $this->calculatorBilling->creditsPerRun(),
            'credits_remaining' => $remaining,
            ...$out,
        ]);
    }
}

