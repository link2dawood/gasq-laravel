<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MasterInputsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterInputsApiController extends Controller
{
    public function __construct(
        private MasterInputsService $masterInputs,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'inputs' => $this->masterInputs->forUser($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inputs' => ['required', 'array'],
            'is_complete' => ['nullable', 'boolean'],
        ]);

        $profile = $this->masterInputs->getOrCreate($request->user());
        $profile->inputs = array_replace($this->masterInputs->defaults(), (array) ($validated['inputs'] ?? []));
        if (array_key_exists('is_complete', $validated)) {
            $profile->is_complete = (bool) $validated['is_complete'];
        }
        $profile->save();

        return response()->json([
            'ok' => true,
            'inputs' => $profile->inputs,
            'is_complete' => $profile->is_complete,
        ]);
    }
}

