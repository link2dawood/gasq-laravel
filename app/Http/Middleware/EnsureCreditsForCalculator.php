<?php

namespace App\Http\Middleware;

use App\Models\FeatureUsageRule;
use App\Services\WalletService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCreditsForCalculator
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        // Buyers and admins access calculators for free.
        if (! $user->isVendor()) {
            return $next($request);
        }

        $sessionKey = 'paid_calc_access.' . $featureKey;
        if ($request->session()->get($sessionKey) === true) {
            return $next($request);
        }

        $rule = FeatureUsageRule::query()
            ->where('feature_key', $featureKey)
            ->where('is_active', true)
            ->first();

        if (! $rule) {
            // No rule configured = treat as free
            $request->session()->put($sessionKey, true);
            return $next($request);
        }

        $cost = (int) $rule->tokens_required;
        $balance = $this->walletService->getBalance($user);

        if ($balance < $cost) {
            return redirect()
                ->route('credits')
                ->with(
                    'error',
                    "Opening the {$rule->feature_name} requires {$cost} credits. Your balance is {$balance}."
                );
        }

        $this->walletService->spendTokens(
            $user,
            $cost,
            $featureKey,
            "Vendor session access to {$rule->feature_name}",
            null,
        );

        $request->session()->put($sessionKey, true);

        return $next($request);
    }
}
