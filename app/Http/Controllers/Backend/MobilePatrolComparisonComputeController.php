<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\V24\MobilePatrol\MobilePatrolComparisonEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server-side compute for the Mobile Patrol Comparison page (Scenario A vs B).
 *
 * Credit-free: powers the live on-page comparison as the user types. The
 * cost/markup formula stays in the engine, never in the browser.
 */
class MobilePatrolComparisonComputeController extends Controller
{
    public function __invoke(Request $request, MobilePatrolComparisonEngine $engine): JsonResponse
    {
        $validated = $request->validate([
            'scenario' => ['required', 'array'],
            'scenario.a' => ['nullable', 'array'],
            'scenario.b' => ['nullable', 'array'],
        ]);

        return response()->json([
            'kpis' => [
                'a' => $engine->computeScenario($validated['scenario']['a'] ?? []),
                'b' => $engine->computeScenario($validated['scenario']['b'] ?? []),
            ],
        ]);
    }
}
