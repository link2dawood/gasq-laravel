<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\V24\Standalone\BudgetTcoEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Credit-free server-side compute for the Budget / Workforce calculator's
 * GASQ Workforce-to-Post TCO derivation. Powers the live page + appraisal
 * table without exposing the formula or charging credits.
 */
class BudgetTcoComputeController extends Controller
{
    public function __invoke(Request $request, BudgetTcoEngine $engine): JsonResponse
    {
        $validated = $request->validate([
            'scenario' => ['required', 'array'],
            'scenario.meta' => ['nullable', 'array'],
        ]);

        return response()->json(['kpis' => $engine->compute($validated['scenario'])]);
    }
}
