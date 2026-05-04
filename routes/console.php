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

Artisan::command('twilio:health {to?}', function (TwilioSmsService $sms) {
    $health = $sms->healthCheck();

    $this->info($health['summary']);
    $this->table(
        ['Key', 'Value'],
        collect($health['details'])->map(fn ($value, $key) => [$key, is_scalar($value) || $value === null ? (string) $value : json_encode($value)])->all()
    );

    $to = $this->argument('to');
    if (! is_string($to) || trim($to) === '') {
        return;
    }

    try {
        $sms->send(trim($to), 'GASQ Twilio health check message.');
        $this->info('Test SMS sent successfully.');
    } catch (\Throwable $e) {
        $this->error($sms->userFacingError($e));
        $this->line($e->getMessage());
    }
})->purpose('Check Twilio auth and optionally send a test SMS');

// Optional: run monthly free-pool grant (e.g. first day of month at 09:00)
// Schedule::command('credits:grant-free-pool', ['--amount' => 5])->monthlyOn(1, '09:00');
Schedule::command('vendor-opportunities:process')->hourly();
Schedule::command('jobs:send-inactivity-survey --days=14')->dailyAt('09:00');
