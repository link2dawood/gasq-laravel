<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorStateStore;
use App\Services\EstimateFollowUpService;
use App\Support\Funnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportPayloadController extends Controller
{
    public function __construct(
        private CalculatorStateStore $calculatorStateStore
    ) {}

    public function __invoke(Request $request, EstimateFollowUpService $followUps): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:120'],
            'scenario' => ['nullable', 'array'],
            'result' => ['required', 'array'],
        ]);

        session([
            'report_payload' => [
                'type' => $validated['type'],
                'scenario' => $validated['scenario'] ?? [],
                'result' => $validated['result'],
            ],
        ]);

        $this->calculatorStateStore->store(
            $request->user(),
            $validated['type'],
            $validated['scenario'] ?? [],
            $validated['result'],
        );
        if ($validated['type'] === 'instant-estimator') {
            Funnel::record(Funnel::ESTIMATE_COMPLETED, [], $request->user()?->id);
            $followUps->sendFor($request->user(), $validated['scenario'] ?? [], $validated['result']);
        }

        return response()->json(['ok' => true]);
    }
}
