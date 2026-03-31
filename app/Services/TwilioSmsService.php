<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class TwilioSmsService
{
    public function send(string $toE164, string $body): void
    {
        $sid = (string) config('services.twilio.account_sid');
        $token = (string) config('services.twilio.auth_token');
        $from = (string) config('services.twilio.from');

        if ($sid === '' || $token === '' || $from === '') {
            throw new RuntimeException('Twilio SMS is not configured (missing TWILIO_ACCOUNT_SID / TWILIO_AUTH_TOKEN / TWILIO_FROM).');
        }

        $url = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $sid);

        $res = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post($url, [
                'To' => $toE164,
                'From' => $from,
                'Body' => $body,
            ]);

        if (! $res->successful()) {
            $msg = Str::limit($res->body(), 500);
            throw new RuntimeException("Twilio SMS send failed ({$res->status()}): {$msg}");
        }
    }
}

