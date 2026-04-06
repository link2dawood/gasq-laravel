<?php

namespace App\Http\Middleware;

use App\Services\WalletService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasCredits
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $balance = $this->walletService->getBalance($user);
        $minCredits = (int) config('credits.calculator_per_run');
        if ($balance < $minCredits) {
            return redirect()
                ->route('credits')
                ->with(
                    'error',
                    'Each calculator run uses ' . $minCredits . ' credits. Purchase more credits to continue.',
                );
        }

        return $next($request);
    }
}

