<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\V24\MobilePatrol\MobilePatrolV24ComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePatrolV24ComputeController extends Controller
{
    public function __construct(
        private MobilePatrolV24ComputeService $compute
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

