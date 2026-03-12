<?php

namespace App\Http\Controllers;

use App\Services\SecurityBillingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityBillingController extends Controller
{
    public function __construct(
        private SecurityBillingService $service
    ) {}

    public function index(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post') && $request->filled(['hourly_rate', 'hours_per_week'])) {
            $result = $this->service->calculate(
                (float) $request->input('hourly_rate', 0),
                (float) $request->input('hours_per_week', 0),
                (int) $request->input('weeks', 52)
            );
            if ($result !== null) {
                session(['report_payload' => ['type' => 'security-billing', 'result' => $result]]);
            }
        }

        return view('calculators.security-billing', ['result' => $result]);
    }
}
