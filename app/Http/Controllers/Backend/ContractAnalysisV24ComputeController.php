<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorRunBillingService;
use App\Services\ScenarioMasterInputsMerger;
use App\Services\V24\ContractAnalysis\ContractAnalysisV24ComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractAnalysisV24ComputeController extends Controller
{
    public function __construct(
        private ContractAnalysisV24ComputeService $compute,
        private CalculatorRunBillingService $calculatorBilling,
        private ScenarioMasterInputsMerger $masterInputsMerger,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'in:v24'],
            'scenario' => ['required', 'array'],
            'scenario.categories' => ['nullable', 'array'],
            'scenario.categories.*' => ['array'],
        ]);

        $scenario = $this->masterInputsMerger->merge($request->user(), $validated['scenario']);

        [$out, $remaining] = $this->calculatorBilling->chargeAndRun(
            $request->user(),
            'contract_analysis_v24',
            'contract-analysis',
            fn () => $this->compute->compute($scenario),
        );

        session([
            'report_payload' => [
                'type' => 'contract-analysis',
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
