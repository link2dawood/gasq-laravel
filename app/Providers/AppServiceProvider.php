<?php

namespace App\Providers;

use App\Events\CreditsGranted;
use App\Listeners\SendCreditsGrantedNotification;
use App\Services\CalculatorViewStateResolver;
use App\Services\MasterInputsService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(CreditsGranted::class, SendCreditsGrantedNotification::class);

        View::composer('layouts.app', function ($view) {
            $walletBalance = null;
            $masterInputs = null;
            $savedCalculatorType = null;
            $savedCalculatorState = null;
            $savedCalculatorScenario = null;
            $savedCalculatorResult = null;
            if (auth()->check()) {
                $walletBalance = app(WalletService::class)->getBalance(auth()->user());
                $masterInputs = app(MasterInputsService::class)->forUser(auth()->user());

                $resolved = app(CalculatorViewStateResolver::class)->resolve(
                    auth()->user(),
                    request()->route()?->getName(),
                );

                $savedCalculatorType = $resolved['type'];
                $savedCalculatorState = $resolved['state'];
                $savedCalculatorScenario = $resolved['scenario'];
                $savedCalculatorResult = $resolved['result'];
            }
            $view->with([
                'walletBalance' => $walletBalance,
                'masterInputs' => $masterInputs,
                'savedCalculatorType' => $savedCalculatorType,
                'savedCalculatorState' => $savedCalculatorState,
                'savedCalculatorScenario' => $savedCalculatorScenario,
                'savedCalculatorResult' => $savedCalculatorResult,
            ]);
        });
    }
}
