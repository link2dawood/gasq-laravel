<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorStateStore;
use App\Services\V24\MainMenu\MainMenuComputeService;
use App\Services\VendorWeightedRateApplication;
use App\Services\VendorWeightedRateEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorWeightedRateController extends Controller
{
    public function __construct(
        private VendorWeightedRateEngine $engine,
        private VendorWeightedRateApplication $application,
        private MainMenuComputeService $main,
        private CalculatorStateStore $store,
    ) {}

    public function compute(Request $request): JsonResponse
    {
        return $this->run($request, false);
    }

    public function apply(Request $request): JsonResponse
    {
        return $this->run($request, true);
    }

    private function run(Request $request, bool $apply): JsonResponse
    {
        abort_unless($request->user()->isVendor() || $request->user()->isAdmin(), 403);
        $data = $request->validate(['inputs' => 'required|array', 'scenario' => 'sometimes|array', 'scenario.meta' => 'sometimes|array']);
        $result = $this->engine->compute($data['inputs']);
        if (! $result['isValid']) {
            return response()->json(['ok' => false, 'result' => $result, 'errors' => $result['fieldErrors']], 422);
        }

        return DB::transaction(function () use ($request, $data, $result, $apply) {
            $saved = $request->user()->calculatorStates()->where('calculator_type', 'main-menu')->lockForUpdate()->first();
            $scenario = (array) ($saved?->scenario ?? []);
            $posted = (array) ($data['scenario'] ?? []);
            $meta = array_replace((array) ($scenario['meta'] ?? []), (array) ($posted['meta'] ?? []));
            // Preserve the stored buyer baseline and authoritative applied snapshot.
            foreach (['basePayRate', 'basePay', 'vendorWeightedRates'] as $key) {
                if (array_key_exists($key, (array) ($scenario['meta'] ?? []))) {
                    $meta[$key] = $scenario['meta'][$key];
                } elseif ($key === 'vendorWeightedRates') {
                    unset($meta[$key]);
                }
            }
            $scenario = array_replace($scenario, $posted);
            $scenario['meta'] = $meta;
            if ($apply) {
                $scenario = $this->application->apply($scenario, $data['inputs']);
            } else {
                $scenario['meta']['vendorWeightedRates']['draft'] = $data['inputs'];
                $scenario['meta']['vendorWeightedRates']['draftResult'] = $result;
                $scenario = $this->application->normalize($scenario);
            }
            $out = $this->main->compute($scenario);
            $preview = $this->main->compute($this->application->apply($scenario, $data['inputs']))['tabs']['vendorComparison'];
            $this->store->store($request->user(), 'main-menu', $scenario, $out);
            session(['report_payload' => ['type' => 'main-menu', 'scenario' => $scenario, 'result' => $out]]);

            return response()->json(['ok' => true, 'result' => $result, 'scenario' => $scenario, 'main' => $out, 'preview' => $preview, 'applied' => $apply]);
        });
    }
}
