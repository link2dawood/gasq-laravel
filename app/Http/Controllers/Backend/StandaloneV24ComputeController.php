<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorRunBillingService;
use App\Services\CalculatorStateStore;
use App\Services\ScenarioMasterInputsMerger;
use App\Services\V24\Standalone\StandaloneV24ComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StandaloneV24ComputeController extends Controller
{
    public function __construct(
        private StandaloneV24ComputeService $compute,
        private CalculatorRunBillingService $calculatorBilling,
        private ScenarioMasterInputsMerger $masterInputsMerger,
        private CalculatorStateStore $calculatorStateStore,
    ) {}

    public function __invoke(Request $request, string $type): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'in:v24'],
            'scenario' => ['required', 'array'],
            'scenario.meta' => ['nullable', 'array'],
        ]);

        $scenario = $this->masterInputsMerger->merge($request->user(), $validated['scenario']);

        // Calculators billed at the report recalculate free, so a vendor can
        // work through a scenario without paying per keystroke. They are
        // charged once when they download or email the report.
        $billedAtReport = in_array($type, (array) config('credits.report_billed_types', []), true);

        if ($billedAtReport) {
            $out = $this->compute->compute($type, $scenario);
            $spent = 0;
            $remaining = $this->calculatorBilling->balanceFor($request->user());
        } else {
            [$out, $remaining] = $this->calculatorBilling->chargeAndRun(
                $request->user(),
                'standalone_v24',
                $type,
                fn () => $this->compute->compute($type, $scenario),
            );
            $spent = $this->calculatorBilling->creditsPerRun();
        }

        // Persist last run for PDF download/email.
        session([
            'report_payload' => [
                'type' => $type,
                'scenario' => $scenario,
                'result' => $out,
            ],
        ]);

        $this->calculatorStateStore->store($request->user(), $type, $scenario, $out);

        return response()->json([
            'ok' => true,
            'version' => 'v24',
            'type' => $type,
            'credits_spent' => $spent,
            'credits_remaining' => $remaining,
            ...$out,
        ]);
    }
}
