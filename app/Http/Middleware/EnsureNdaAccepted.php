<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNdaAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->nda_accepted_at) {
            return $next($request);
        }

        if ($request->routeIs('nda.show', 'nda.accept', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'error' => 'nda_required',
                'message' => 'You must accept the Beta Test NDA before continuing.',
                'redirect' => route('nda.show'),
            ], 423);
        }

        return redirect()
            ->route('nda.show')
            ->with('error', 'Please review and acknowledge the Beta Test NDA to continue.');
    }
}
