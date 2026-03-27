<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\SecurityBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityBillingComputeController extends Controller
{
    public function __construct(
        private SecurityBillingService $service
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hourly_rate' => ['required', 'numeric'],
            'hours_per_week' => ['required', 'numeric'],
            'weeks' => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->service->calculate(
            (float) $validated['hourly_rate'],
            (float) $validated['hours_per_week'],
            (int) ($validated['weeks'] ?? 52)
        );

        return response()->json([
            'ok' => true,
            'kpis' => $result,
        ]);
    }
}

