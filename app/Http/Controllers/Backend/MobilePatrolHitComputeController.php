<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\V24\Standalone\MobilePatrolHitCalculatorEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server-side compute for the Mobile Patrol Hit Calculator.
 *
 * The pricing formula lives only here (and in the engine) — never in the
 * browser. The client posts the raw inputs and receives only the computed
 * results, so the cost/markup math is not exposed in page source or DevTools.
 */
class MobilePatrolHitComputeController extends Controller
{
    public function __invoke(Request $request, MobilePatrolHitCalculatorEngine $engine): JsonResponse
    {
        $validated = $request->validate([
            'scenario' => ['required', 'array'],
            'scenario.meta' => ['nullable', 'array'],
        ]);

        $kpis = $engine->compute($validated['scenario']);

        return response()->json(['kpis' => $kpis]);
    }
}
