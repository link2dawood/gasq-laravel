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
        //
    })->create();
