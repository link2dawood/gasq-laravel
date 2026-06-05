<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\V24\MobilePatrol\MobilePatrolV24Engine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server-side compute for the Mobile Patrol Calculator live preview.
 *
 * The cost/bill-rate formula lives only in the engine, never in the browser.
 * This endpoint is intentionally credit-free: it powers the live on-page
 * results as the user types. Credit-charged report generation stays on the
 * existing report-payload / v24 compute path.
 */
class MobilePatrolComputeController extends Controller
{
    public function __invoke(Request $request, MobilePatrolV24Engine $engine): JsonResponse
    {
        $validated = $request->validate([
            'scenario' => ['required', 'array'],
            'scenario.meta' => ['nullable', 'array'],
        ]);

        $kpis = $engine->compute($validated['scenario']);

        return response()->json(['kpis' => $kpis]);
    }
}
