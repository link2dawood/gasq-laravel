<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TwilioSmsService
{
    /**
     * @return array{ok: bool, status: int, summary: string, details: array<string, mixed>}
     */
    public function healthCheck(): array
    {
        $sid = $this->cleanConfigValue(config('services.twilio.account_sid'));
        $token = $this->cleanConfigValue(config('services.twilio.auth_token'));

        if ($sid === '' || $token === '') {
            return [
                'ok' => false,
                'status' => 0,
                'summary' => 'Twilio credentials are missing.',
                'details' => $this->debugContext(),
            ];
        }

        $url = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s.json', $sid);
        $res = Http::withBasicAuth($sid, $token)->get($url);

        $bodyData = $res->json();
        $twilioCode = is_array($bodyData) ? Arr::get($bodyData, 'code') : null;
        $twilioMessage = is_array($bodyData)
            ? (string) Arr::get($bodyData, 'message', '')
            : Str::limit($res->body(), 500);

        $details = $this->debugContext();
        $details['http_status'] = $res->status();
        $details['twilio_code'] = $twilioCode;
        $details['twilio_message'] = $twilioMessage;

        if ($res->successful()) {
            $details['friendly_name'] = is_array($bodyData) ? Arr::get($bodyData, 'friendly_name') : null;
            $details['account_status'] = is_array($bodyData) ? Arr::get($bodyData, 'status') : null;

            return [
                'ok' => true,
                'status' => $res->status(),
                'summary' => 'Twilio account authentication succeeded.',
                'details' => $details,
            ];
        }

        return [
            'ok' => false,
            'status' => $res->status(),
            'summary' => $twilioCode === 20003
                ? 'Twilio rejected the current SID/token pair.'
                : 'Twilio health check failed.',
            'details' => $details,
        ];
    }

    public function send(string $toE164, string $body): void
    {
        $sid = $this->cleanConfigValue(config('services.twilio.account_sid'));
        $token = $this->cleanConfigValue(config('services.twilio.auth_token'));
        $from = $this->cleanConfigValue(config('services.twilio.from'));
        $messagingServiceSid = $this->cleanConfigValue(config('services.twilio.messaging_service_sid'));

        if ($sid === '' || $token === '') {
            throw new RuntimeException('Twilio SMS is not configured (missing TWILIO_ACCOUNT_SID / TWILIO_AUTH_TOKEN).');
        }

        if ($messagingServiceSid === '' && $from === '') {
            throw new RuntimeException('Twilio SMS is not configured (missing TWILIO_MESSAGING_SERVICE_SID or TWILIO_FROM).');
        }

        $url = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $sid);
        $payload = [
            'To' => $toE164,
            'Body' => $body,
        ];

        if ($messagingServiceSid !== '') {
            $payload['MessagingServiceSid'] = $messagingServiceSid;
        } else {
            $payload['From'] = $from;
        }

        $res = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post($url, $payload);

        if (! $res->successful()) {
            $bodyData = $res->json();
            $twilioCode = is_array($bodyData) ? Arr::get($bodyData, 'code') : null;
            $twilioMessage = is_array($bodyData)
                ? (string) Arr::get($bodyData, 'message', '')
                : Str::limit($res->body(), 500);

            $diagnostics = $this->debugContext();
            $diagnostics['http_status'] = $res->status();
            $diagnostics['twilio_code'] = $twilioCode;
            $diagnostics['twilio_message'] = $twilioMessage;
            $diagnostics['to'] = $this->maskPhone($toE164);

            throw new RuntimeException('Twilio SMS send failed: '.json_encode($diagnostics));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function debugContext(): array
    {
        $sid = $this->cleanConfigValue(config('services.twilio.account_sid'));
        $token = $this->cleanConfigValue(config('services.twilio.auth_token'));
        $from = $this->cleanConfigValue(config('services.twilio.from'));
        $messagingServiceSid = $this->cleanConfigValue(config('services.twilio.messaging_service_sid'));

        return [
            'account_sid' => $this->maskValue($sid, 6),
            'auth_token_length' => strlen($token),
            'auth_token_suffix' => $this->maskValue($token, 4),
            'from' => $from !== '' ? $this->maskPhone($from) : null,
            'messaging_service_sid' => $messagingServiceSid !== '' ? $this->maskValue($messagingServiceSid, 6) : null,
            'delivery_mode' => $messagingServiceSid !== '' ? 'messaging_service' : 'from_number',
        ];
    }

    public function userFacingError(Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, '"twilio_code":20003') || str_contains($message, '"http_status":401')) {
            return 'SMS service authentication failed. Please contact support if this continues.';
        }

        if (
            str_contains($message, '"twilio_code":21608')
            || str_contains($message, '"twilio_code":21614')
            || str_contains($message, '"twilio_code":21408')
        ) {
            return 'This phone number cannot receive SMS verification right now. Please confirm the number and try again.';
        }

        return 'We could not send a verification code right now. Please try again in a moment.';
    }

    private function cleanConfigValue(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value, " \t\n\r\0\x0B\"'");
    }

    private function maskValue(string $value, int $visibleSuffix = 4): string
    {
        if ($value === '') {
            return '';
        }

        $suffix = substr($value, -$visibleSuffix);

        return str_repeat('*', max(strlen($value) - strlen($suffix), 0)).$suffix;
    }

    private function maskPhone(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return preg_replace('/\d(?=\d{4})/', '*', $value) ?? $value;
    }
}
