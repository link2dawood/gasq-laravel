<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorStateStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportPayloadController extends Controller
{
    public function __construct(
        private CalculatorStateStore $calculatorStateStore
    ) {}

    public function __invoke(Request $request): JsonResponse
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

        return response()->json(['ok' => true]);
    }
}
