<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Optional: run monthly free-pool grant (e.g. first day of month at 09:00)
// Schedule::command('credits:grant-free-pool', ['--amount' => 5])->monthlyOn(1, '09:00');
