<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorRunBillingService;
use App\Services\CalculatorStateStore;
use App\Services\ScenarioMasterInputsMerger;
use App\Services\V24\MainMenu\MainMenuComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MainMenuComputeController extends Controller
{
    public function __construct(
        private MainMenuComputeService $compute,
        private CalculatorRunBillingService $calculatorBilling,
        private ScenarioMasterInputsMerger $masterInputsMerger,
        private CalculatorStateStore $calculatorStateStore,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'in:v24'],
            'scenario' => ['required', 'array'],
            'scenario.assumptions' => ['nullable', 'array'],
            'scenario.scope' => ['nullable', 'array'],
            'scenario.posts' => ['nullable', 'array'],
            'scenario.posts.*' => ['array'],
            'scenario.vehicle' => ['nullable', 'array'],
            'scenario.uniform' => ['nullable', 'array'],
            'scenario.meta' => ['nullable', 'array'],
        ]);

        $scenario = $this->masterInputsMerger->merge($request->user(), $validated['scenario']);

        [$out, $remaining] = $this->calculatorBilling->chargeAndRun(
            $request->user(),
            'main_menu_v24',
            'main-menu',
            fn () => $this->compute->compute($scenario),
        );

        // Persist last run for PDF download/email.
        session([
            'report_payload' => [
                'type' => 'main-menu',
                'scenario' => $scenario,
                'result' => $out,
            ],
        ]);

        $this->calculatorStateStore->store($request->user(), 'main-menu', $scenario, $out);

        return response()->json([
            'ok' => true,
            'version' => 'v24',
            'credits_spent' => $this->calculatorBilling->creditsPerRun(),
            'credits_remaining' => $remaining,
            ...$out,
        ]);
    }
}
