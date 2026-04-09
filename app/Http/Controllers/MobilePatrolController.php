<?php

namespace App\Http\Controllers;

use App\Services\CalculatorStateStore;
use App\Services\MobilePatrolService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobilePatrolController extends Controller
{
    public function __construct(
        private MobilePatrolService $service,
        private CalculatorStateStore $calculatorStateStore,
    ) {}

    public function calculator(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post') && $request->filled(['cost_per_visit', 'visits_per_month'])) {
            $result = $this->service->scenarioCost(
                (float) $request->input('cost_per_visit', 0),
                (float) $request->input('visits_per_month', 0),
                (float) $request->input('monthly_base', 0)
            );
            if ($result !== null) {
                session(['report_payload' => ['type' => 'mobile-patrol', 'result' => $result]]);
                $this->calculatorStateStore->store(
                    $request->user(),
                    'mobile-patrol',
                    $request->except('_token'),
                    $result,
                );
            }
        }

        return view('calculators.mobile-patrol', ['result' => $result]);
    }

    public function comparison(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post')) {
            $a = [
                'cost_per_visit' => (float) $request->input('a_cost_per_visit', 0),
                'visits_per_month' => (float) $request->input('a_visits_per_month', 0),
                'monthly_base' => (float) $request->input('a_monthly_base', 0),
            ];
            $b = [
                'cost_per_visit' => (float) $request->input('b_cost_per_visit', 0),
                'visits_per_month' => (float) $request->input('b_visits_per_month', 0),
                'monthly_base' => (float) $request->input('b_monthly_base', 0),
            ];
            $scenarioA = $this->service->scenarioCost($a['cost_per_visit'], $a['visits_per_month'], $a['monthly_base']);
            $scenarioB = $this->service->scenarioCost($b['cost_per_visit'], $b['visits_per_month'], $b['monthly_base']);
            $result = $this->service->compare($scenarioA, $scenarioB);
            $result['scenario_a'] = $scenarioA;
            $result['scenario_b'] = $scenarioB;
            session(['report_payload' => ['type' => 'mobile-patrol-comparison', 'result' => $result]]);
            $this->calculatorStateStore->store(
                $request->user(),
                'mobile-patrol-comparison',
                ['a' => $a, 'b' => $b],
                $result,
            );
        }

        return view('calculators.mobile-patrol-comparison', ['result' => $result]);
    }
}
