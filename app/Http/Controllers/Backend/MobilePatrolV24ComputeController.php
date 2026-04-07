<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorRunBillingService;
use App\Services\ScenarioMasterInputsMerger;
use App\Services\V24\MobilePatrol\MobilePatrolV24ComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePatrolV24ComputeController extends Controller
{
    public function __construct(
        private MobilePatrolV24ComputeService $compute,
        private CalculatorRunBillingService $calculatorBilling,
        private ScenarioMasterInputsMerger $masterInputsMerger,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'in:v24'],
            'scenario' => ['required', 'array'],
            'scenario.meta' => ['nullable', 'array'],
        ]);

        $scenario = $this->masterInputsMerger->merge($request->user(), $validated['scenario']);

        [$out, $remaining] = $this->calculatorBilling->chargeAndRun(
            $request->user(),
            'mobile_patrol_v24',
            'mobile-patrol-calculator',
            fn () => $this->compute->compute($scenario),
        );

        // Persist last run for PDF download/email.
        session([
            'report_payload' => [
                'type' => 'mobile-patrol',
                'scenario' => $scenario,
                'result' => $out,
            ],
        ]);

        return response()->json([
            'ok' => true,
            'version' => 'v24',
            'credits_spent' => $this->calculatorBilling->creditsPerRun(),
            'credits_remaining' => $remaining,
            ...$out,
        ]);
    }
}

