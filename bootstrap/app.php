<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\AnalyticsLogger::class,
            \App\Http\Middleware\EnsureNdaAccepted::class,
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'vendor' => \App\Http\Middleware\EnsureUserIsVendor::class,
            'phone.verified' => \App\Http\Middleware\EnsurePhoneVerified::class,
            'has.credits' => \App\Http\Middleware\EnsureHasCredits::class,
            'buyer.has_job' => \App\Http\Middleware\EnsureBuyerHasPostedJob::class,
            'master.inputs' => \App\Http\Middleware\EnsureMasterInputsComplete::class,
            'calc.credits' => \App\Http\Middleware\EnsureCreditsForCalculator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // When the session/CSRF token has expired (e.g. after the auto-logout
        // timer fires or a form sits open too long), Laravel throws a 419
        // TokenMismatchException and renders the bare "PAGE EXPIRED" screen.
        // Instead, send the user cleanly back to the login page.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page and try again.',
                ], 419);
            }

            return redirect()->route('login')
                ->with('status', 'Your session expired and you were signed out. Please sign in again.');
        });
    })->create();
