<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isVendor() || $user->isAdmin()) {
            return $next($request);
        }

        return redirect()
            ->route('instant-estimator.index')
            ->with('error', 'Buyers use the Instant Estimator. The full calculator suite is reserved for vendor access.');
    }
}
