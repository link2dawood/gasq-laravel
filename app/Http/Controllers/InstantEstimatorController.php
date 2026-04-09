<?php

namespace App\Http\Controllers;

use App\Services\CalculatorStateStore;
use App\Services\GasqEstimatorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstantEstimatorController extends Controller
{
    public function __construct(
        private GasqEstimatorService $estimator,
        private CalculatorStateStore $calculatorStateStore,
    ) {}

    public function index(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post') && $request->filled(['location', 'hours_per_week', 'number_of_guards'])) {
            $result = $this->estimator->estimate(
                $request->input('location'),
                (float) $request->input('hours_per_week', 0),
                (float) $request->input('number_of_guards', 1)
            );
            if ($result !== null) {
                session(['report_payload' => ['type' => 'instant-estimator', 'result' => $result]]);
                $this->calculatorStateStore->store(
                    $request->user(),
                    'instant-estimator',
                    $request->except('_token'),
                    $result,
                );
            }
        }

        return view('calculators.instant-estimator', [
            'locations' => $this->estimator->getLocations(),
            'result' => $result,
        ]);
    }
}
