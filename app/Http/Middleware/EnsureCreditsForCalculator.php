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

        // Guests are treated like buyers: free. A guest can never be a vendor, so
        // there is nothing to charge. This is what lets the Instant Estimator show a
        // result before asking anyone to register.
        //
        // NOTE: this middleware no longer forces a login. Any calculator route that
        // genuinely requires an account must declare 'auth' itself — every route
        // using calc.credits does, except /instant-estimator, which is public by design.
        if (! $user) {
            return $next($request);
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
