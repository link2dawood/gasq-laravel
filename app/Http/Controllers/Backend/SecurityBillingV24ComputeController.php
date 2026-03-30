<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\V24\SecurityBilling\SecurityBillingV24ComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityBillingV24ComputeController extends Controller
{
    public function __construct(
        private SecurityBillingV24ComputeService $compute
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'in:v24'],
            'scenario' => ['required', 'array'],
            'scenario.meta' => ['nullable', 'array'],
        ]);

        $out = $this->compute->compute($validated['scenario']);

        return response()->json([
            'ok' => true,
            'version' => 'v24',
            ...$out,
        ]);
    }
}

