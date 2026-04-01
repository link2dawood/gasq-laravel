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
        if ($balance <= 0) {
            return redirect()
                ->route('credits')
                ->with('error', 'Purchase credits to unlock calculators.');
        }

        return $next($request);
    }
}

