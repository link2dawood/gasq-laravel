<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Scenario;
use App\Services\ScenarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScenarioController extends Controller
{
    public function __construct(
        private ScenarioService $scenarioService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $rows = $request->user()
            ->scenarios()
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'status', 'workbook_version', 'created_at', 'updated_at']);

        return response()->json([
            'ok' => true,
            'scenarios' => $rows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,active,archived'],
            'workbook_version' => ['nullable', 'string', 'max:32'],
            'workbookVersion' => ['nullable', 'string', 'max:32'],
            'assumptions' => ['nullable', 'array'],
            'vehicle' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'scope' => ['required', 'array'],
            'sites' => ['nullable', 'array'],
            'sites.*' => ['array'],
            'posts' => ['nullable', 'array'],
            'posts.*' => ['array'],
            'shifts' => ['nullable', 'array'],
            'shifts.*' => ['array'],
        ]);

        $scenario = $this->scenarioService->createScenario($request->user(), $validated);

        return response()->json([
            'ok' => true,
            'data' => $this->scenarioService->toPayload($scenario),
        ], JsonResponse::HTTP_CREATED);
    }

    public function show(Request $request, Scenario $scenario): JsonResponse
    {
        $this->authorizeScenario($request, $scenario);

        return response()->json([
            'ok' => true,
            'data' => $this->scenarioService->toPayload($scenario),
        ]);
    }

    public function payload(Request $request, Scenario $scenario): JsonResponse
    {
        $this->authorizeScenario($request, $scenario);

        return response()->json([
            'ok' => true,
            'data' => $this->scenarioService->toPayload($scenario),
        ]);
    }

    public function update(Request $request, Scenario $scenario): JsonResponse
    {
        $this->authorizeScenario($request, $scenario);

        $validated = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:draft,active,archived'],
            'workbook_version' => ['sometimes', 'nullable', 'string', 'max:32'],
            'workbookVersion' => ['sometimes', 'nullable', 'string', 'max:32'],
            'assumptions' => ['sometimes', 'nullable', 'array'],
            'vehicle' => ['sometimes', 'nullable', 'array'],
            'meta' => ['sometimes', 'nullable', 'array'],
            'scope' => ['sometimes', 'array'],
            'sites' => ['sometimes', 'array'],
            'sites.*' => ['array'],
            'posts' => ['sometimes', 'array'],
            'posts.*' => ['array'],
            'shifts' => ['sometimes', 'array'],
            'shifts.*' => ['array'],
        ]);

        $scenario = $this->scenarioService->updateScenario($scenario, $validated);

        return response()->json([
            'ok' => true,
            'data' => $this->scenarioService->toPayload($scenario),
        ]);
    }

    private function authorizeScenario(Request $request, Scenario $scenario): void
    {
        abort_if($scenario->user_id !== $request->user()->id, JsonResponse::HTTP_NOT_FOUND);
    }
}
