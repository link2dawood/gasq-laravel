<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Models\PricingPlan;
use App\Services\CouponRedemptionService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditsController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        private CouponRedemptionService $couponRedemptionService,
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

        // If user has credits, send buyers to post a job next.
        if ($balance > 0 && $user->isBuyer()) {
            $hasJob = \App\Models\JobPosting::query()->where('user_id', $user->id)->exists();
            if (! $hasJob) {
                return redirect()->route('jobs.create')->with('success', 'Credits added. Now post your job to unlock calculators.');
            }
        }

        return view('credits.success', [
            'sessionId' => $sessionId,
            'balance' => $balance,
        ]);
    }

    public function redeem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $user = $request->user();
        $coupon = $this->couponRedemptionService->redeem($user, $data['code']);

        if ($user->isBuyer()) {
            $hasJob = JobPosting::query()->where('user_id', $user->id)->exists();

            if (! $hasJob) {
                return redirect()
                    ->route('jobs.create')
                    ->with('success', "Coupon {$coupon->code} redeemed. Credits added to your account. Post your job to unlock calculators.");
            }
        }

        return redirect()
            ->route('credits')
            ->with('success', "Coupon {$coupon->code} redeemed. {$coupon->credits_amount} credits added to your account.");
    }
}
