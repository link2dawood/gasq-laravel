<?php

namespace App\Http\Controllers;

use App\Services\MainMenuCalculatorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MainMenuCalculatorController extends Controller
{
    public function __construct(
        private MainMenuCalculatorService $calculator
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'security');
        $result = null;

        if ($request->isMethod('post')) {
            $tab = $request->input('tab', 'security');
            if ($tab === 'security') {
                $result = $this->calculator->securityCost(
                    $request->input('location', ''),
                    (float) $request->input('hours_per_week', 0),
                    (float) $request->input('guards', 1)
                );
            } elseif ($tab === 'manpower') {
                $result = $this->calculator->manpowerHours(
                    (float) $request->input('site_coverage', 0),
                    $request->input('shift_pattern', '8-hour'),
                    (float) $request->input('scheduling_factor', 1.4)
                );
            } elseif ($tab === 'economic') {
                $result = $this->calculator->economicJustification(
                    (float) $request->input('employee_hourly_cost', 0),
                    (float) $request->input('vendor_hourly_cost', 0),
                    (float) $request->input('weekly_hours', 0),
                    (int) $request->input('weeks_in_year', 52)
                );
            } elseif ($tab === 'billrate') {
                $result = $this->calculator->billRate(
                    (float) $request->input('base_pay', 0),
                    (float) $request->input('overhead', 35),
                    (float) $request->input('profit_margin', 15)
                );
            }
            if ($result !== null) {
                session(['report_payload' => ['type' => 'main-menu', 'result' => $result]]);
            }
        }

        return view('calculators.main-menu', [
            'activeTab' => $tab,
            'result' => $result,
        ]);
    }
}
