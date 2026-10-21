<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\CalculatorStateStore;
use App\Services\V24\MainMenu\MainMenuComputeService;
use App\Services\VendorReportSigningService;
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
        private VendorReportSigningService $signatures,
    ) {}

    public function compute(Request $request): JsonResponse
    {
        return $this->run($request, false);
    }

    public function apply(Request $request): JsonResponse
    {
        return $this->run($request, true);
    }

    public function sign(Request $request): JsonResponse
    {
        abort_unless($request->user()->isVendor() || $request->user()->isAdmin(), 403);
        $input = $request->validate([
            'reportHash' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'signerName' => ['required', 'string', 'max:120'],
            'signerTitle' => ['nullable', 'string', 'max:120'],
            'method' => ['required', 'in:typed,drawn'],
            'signatureImage' => ['nullable', 'string', 'max:100000', 'required_if:method,drawn'],
            'consent' => ['required', 'accepted'],
        ]);

        return DB::transaction(function () use ($request, $input) {
            $saved = $request->user()->calculatorStates()->where('calculator_type', 'main-menu')->lockForUpdate()->first();
            abort_unless($saved, 422, 'Calculate and save the report before signing.');
            $this->signatures->sign($saved->scenario, $request->user(), $input);

            return response()->json(['ok' => true, ...$this->signatures->status($saved->scenario, $request->user())]);
        });
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

            return response()->json(['ok' => true, 'result' => $result, 'scenario' => $scenario, 'main' => $out, 'preview' => $preview, 'applied' => $apply,
                'currency' => \App\Support\Currency::jsConfig(),
                ...$this->signatures->status($scenario, $request->user()),
            ]);
        });
    }
}
