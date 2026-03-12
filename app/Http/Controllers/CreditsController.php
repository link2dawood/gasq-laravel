<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditsController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $balance = $this->walletService->getBalance($user);
        $plans = PricingPlan::where('is_active', true)->orderBy('sort_order')->get();

        return view('credits.index', [
            'balance' => $balance,
            'plans' => $plans,
        ]);
    }

    public function success(Request $request): View
    {
        $sessionId = $request->query('session_id');
        $user = $request->user();
        $balance = $this->walletService->getBalance($user);

        return view('credits.success', [
            'sessionId' => $sessionId,
            'balance' => $balance,
        ]);
    }
}
