<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneVerified
{
    /**
     * Redirect logged-in users to phone verification until verified.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $isVerified = (bool) ($user->phone_verified ?? false);
        $hasPhone = is_string($user->phone) && trim($user->phone) !== '';

        if ($hasPhone && ! $isVerified) {
            if ($request->routeIs('phone.verify.show', 'phone.verify.send', 'phone.verify.check', 'logout')) {
                return $next($request);
            }

            return redirect()->route('phone.verify.show');
        }

        return $next($request);
    }
}

