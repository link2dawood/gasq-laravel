<?php

namespace App\Http\Middleware;

use App\Services\MasterInputsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMasterInputsComplete
{
    public function __construct(
        private MasterInputsService $masterInputs,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $profile = $this->masterInputs->getOrCreate($user);
        $isInputsRoute = $request->routeIs('master-inputs.*');

        if (! $profile->is_complete && ! $isInputsRoute) {
            return redirect()->route('master-inputs.index');
        }

        return $next($request);
    }
}

