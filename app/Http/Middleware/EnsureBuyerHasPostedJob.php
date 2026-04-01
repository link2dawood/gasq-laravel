<?php

namespace App\Http\Middleware;

use App\Models\JobPosting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBuyerHasPostedJob
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        // Only buyers are required to post a job before using calculators.
        if (! $user->isBuyer()) {
            return $next($request);
        }

        $hasJob = JobPosting::query()->where('user_id', $user->id)->exists();
        if (! $hasJob) {
            return redirect()
                ->route('jobs.create')
                ->with('error', 'Post your job first to unlock calculators.');
        }

        return $next($request);
    }
}

