<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\V24\MainMenu\MainMenuComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MainMenuComputeController extends Controller
{
    public function __construct(
        private MainMenuComputeService $compute
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'in:v24'],
            'scenario' => ['required', 'array'],
            'scenario.assumptions' => ['nullable', 'array'],
            'scenario.scope' => ['nullable', 'array'],
            'scenario.posts' => ['nullable', 'array'],
            'scenario.posts.*' => ['array'],
            'scenario.vehicle' => ['nullable', 'array'],
            'scenario.uniform' => ['nullable', 'array'],
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

