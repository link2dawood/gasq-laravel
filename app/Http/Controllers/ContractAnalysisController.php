<?php

namespace App\Http\Controllers;

use App\Services\ContractAnalysisService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractAnalysisController extends Controller
{
    public function __construct(
        private ContractAnalysisService $service
    ) {}

    public function index(Request $request): View
    {
        $result = null;
        if ($request->isMethod('post') && $request->filled('categories')) {
            $categories = $request->input('categories', []);
            $result = $this->service->analyze($categories);
            if ($result !== null) {
                session(['report_payload' => ['type' => 'contract-analysis', 'result' => $result]]);
            }
        }

        return view('calculators.contract-analysis', ['result' => $result]);
    }
}
