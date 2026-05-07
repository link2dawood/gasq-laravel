<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Wraps Twilio Verify API for OTP delivery + validation.
 *
 * Verify generates and validates the code on Twilio's side, so we never
 * see or store it. Exempt from US A2P 10DLC carrier filtering, unlike
 * raw Messages-API sends.
 */
class TwilioVerifyService
{
    public function isConfigured(): bool
    {
        return $this->cleanConfigValue(config('services.twilio.account_sid')) !== ''
            && $this->cleanConfigValue(config('services.twilio.auth_token')) !== ''
            && $this->cleanConfigValue(config('services.twilio.verify_service_sid')) !== '';
    }

    /**
     * Trigger an SMS OTP. Twilio holds the code; we just track that a send happened.
     *
     * @return array{status: string, sid: ?string}
     */
    public function start(string $toE164, string $channel = 'sms'): array
    {
        [$sid, $token, $serviceSid] = $this->credentials();

        $url = sprintf('https://verify.twilio.com/v2/Services/%s/Verifications', $serviceSid);

        $res = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post($url, [
                'To' => $toE164,
                'Channel' => $channel,
            ]);

        if (! $res->successful()) {
            $this->throwFromResponse($res, $toE164);
        }

        $body = $res->json();

        return [
            'status' => (string) Arr::get($body, 'status', 'pending'),
            'sid' => Arr::get($body, 'sid'),
        ];
    }

    /**
     * Validate the user-supplied 6-digit code. True only when Twilio replies
     * status=approved (and valid=true).
     */
    public function check(string $toE164, string $code): bool
    {
        [$sid, $token, $serviceSid] = $this->credentials();

        $url = sprintf('https://verify.twilio.com/v2/Services/%s/VerificationCheck', $serviceSid);

        $res = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post($url, [
                'To' => $toE164,
                'Code' => $code,
            ]);

        // 404 from VerificationCheck means "no pending verification" — treat as a normal
        // failed check rather than throwing, so the caller can show "Invalid code".
        if ($res->status() === 404) {
            return false;
        }

        if (! $res->successful()) {
            $this->throwFromResponse($res, $toE164);
        }

        $body = $res->json();

        return Arr::get($body, 'status') === 'approved'
            && Arr::get($body, 'valid') === true;
    }

    /**
     * Map Twilio Verify error codes (60xxx) to user-facing messages.
     */
    public function userFacingError(Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, '"twilio_code":20003') || str_contains($message, '"http_status":401')) {
            return 'SMS service authentication failed. Please contact support if this continues.';
        }

        if (str_contains($message, '"twilio_code":60200')) {
            return 'That phone number could not be verified. Please double-check it and try again.';
        }

        if (str_contains($message, '"twilio_code":60202')) {
            return 'Too many attempts on this code. Please request a new one.';
        }

        if (str_contains($message, '"twilio_code":60203')) {
            return 'Too many verification requests for this number. Please wait a few minutes and try again.';
        }

        if (str_contains($message, '"twilio_code":60410')) {
            return 'Your verification has expired. Please request a new code.';
        }

        if (str_contains($message, '"twilio_code":60605') || str_contains($message, '"twilio_code":60606')) {
            return 'This phone number is not currently allowed for verification. Please use a different number.';
        }

        return 'We could not send a verification code right now. Please try again in a moment.';
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function credentials(): array
    {
        $sid = $this->cleanConfigValue(config('services.twilio.account_sid'));
        $token = $this->cleanConfigValue(config('services.twilio.auth_token'));
        $serviceSid = $this->cleanConfigValue(config('services.twilio.verify_service_sid'));

        if ($sid === '' || $token === '' || $serviceSid === '') {
            throw new RuntimeException('Twilio Verify is not configured (missing TWILIO_ACCOUNT_SID / TWILIO_AUTH_TOKEN / TWILIO_VERIFY_SERVICE_SID).');
        }

        return [$sid, $token, $serviceSid];
    }

    private function throwFromResponse(\Illuminate\Http\Client\Response $res, string $to): void
    {
        $bodyData = $res->json();
        $diagnostics = [
            'http_status' => $res->status(),
            'twilio_code' => is_array($bodyData) ? Arr::get($bodyData, 'code') : null,
            'twilio_message' => is_array($bodyData) ? (string) Arr::get($bodyData, 'message', '') : '',
            'to' => $this->maskPhone($to),
        ];

        throw new RuntimeException('Twilio Verify request failed: ' . json_encode($diagnostics));
    }

    private function cleanConfigValue(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }
        return trim($value, " \t\n\r\0\x0B\"'");
    }

    private function maskPhone(string $value): string
    {
        return preg_replace('/\d(?=\d{4})/', '*', $value) ?? $value;
    }
}
