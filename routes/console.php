<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\TwilioSmsService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('twilio:debug', function (TwilioSmsService $sms) {
    $context = $sms->debugContext();

    $this->info('Twilio runtime configuration');
    $this->table(
        ['Key', 'Value'],
        collect($context)->map(fn ($value, $key) => [$key, is_scalar($value) || $value === null ? (string) $value : json_encode($value)])->all()
    );
})->purpose('Show masked Twilio runtime configuration');

// Optional: run monthly free-pool grant (e.g. first day of month at 09:00)
// Schedule::command('credits:grant-free-pool', ['--amount' => 5])->monthlyOn(1, '09:00');
