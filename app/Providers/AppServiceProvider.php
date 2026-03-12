<?php

namespace App\Providers;

use App\Events\CreditsGranted;
use App\Listeners\SendCreditsGrantedNotification;
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
            if (auth()->check()) {
                $walletBalance = app(WalletService::class)->getBalance(auth()->user());
            }
            $view->with('walletBalance', $walletBalance);
        });
    }
}
