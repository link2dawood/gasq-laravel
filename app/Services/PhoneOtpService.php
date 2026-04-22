<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;

class PhoneOtpService
{
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const OTP_EXPIRY_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private TwilioSmsService $sms
    ) {
    }

    /**
     * @return array{ok: bool, field?: string, message?: string, phone?: string, verification?: VerificationCode}
     */
    public function sendOtp(User $user, string $type, string $rawPhone): array
    {
        $phone = $this->normalizePhoneToE164($rawPhone);
        if ($phone === null) {
            return [
                'ok' => false,
                'field' => 'phone',
                'message' => 'Phone number must be in E.164 format, e.g. +12345678900.',
            ];
        }

        $latest = VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('phone_number', $phone)
            ->orderByDesc('id')
            ->first();

        if ($latest && $latest->last_sent_at && $latest->last_sent_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            return [
                'ok' => false,
                'field' => 'otp',
                'message' => 'Please wait a moment before requesting another code.',
                'phone' => $phone,
            ];
        }

        VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('status', 'pending')
            ->update(['status' => 'failed']);

        $code = (string) random_int(100000, 999999);

        $verification = VerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => '',
            'code_hash' => Hash::make($code),
            'type' => $type,
            'phone_number' => $phone,
            'email' => null,
            'status' => 'pending',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'last_sent_at' => now(),
        ]);

        try {
            $this->sms->send($phone, "Your GASQ verification code is {$code}. It expires in 10 minutes.");
        } catch (\Throwable $e) {
            $verification->status = 'failed';
            $verification->save();

            return [
                'ok' => false,
                'field' => 'phone',
                'message' => $this->sms->userFacingError($e),
                'phone' => $phone,
            ];
        }

        return [
            'ok' => true,
            'phone' => $phone,
            'verification' => $verification,
        ];
    }

    /**
     * @return array{ok: bool, field?: string, message?: string, phone?: string, verification?: VerificationCode}
     */
    public function verifyOtp(User $user, string $type, string $rawPhone, string $code): array
    {
        $phone = $this->normalizePhoneToE164($rawPhone);
        if ($phone === null) {
            return [
                'ok' => false,
                'field' => 'phone',
                'message' => 'Invalid phone number format.',
            ];
        }

        $verification = VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('phone_number', $phone)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();

        if (! $verification) {
            return [
                'ok' => false,
                'field' => 'otp',
                'message' => 'No active code found. Please request a new code.',
                'phone' => $phone,
            ];
        }

        if ($verification->expires_at && $verification->expires_at->isPast()) {
            $verification->status = 'failed';
            $verification->save();

            return [
                'ok' => false,
                'field' => 'otp',
                'message' => 'Code expired. Please request a new code.',
                'phone' => $phone,
            ];
        }

        if (($verification->attempts ?? 0) >= self::MAX_ATTEMPTS) {
            $verification->status = 'failed';
            $verification->save();

            return [
                'ok' => false,
                'field' => 'otp',
                'message' => 'Too many attempts. Please request a new code.',
                'phone' => $phone,
            ];
        }

        if (! is_string($verification->code_hash) || $verification->code_hash === '' || ! Hash::check($code, $verification->code_hash)) {
            $verification->attempts = (int) ($verification->attempts ?? 0) + 1;
            if ($verification->attempts >= self::MAX_ATTEMPTS) {
                $verification->status = 'failed';
            }
            $verification->save();

            return [
                'ok' => false,
                'field' => 'otp',
                'message' => 'Invalid code. Please try again.',
                'phone' => $phone,
            ];
        }

        $verification->status = 'verified';
        $verification->verified_at = now();
        $verification->save();

        return [
            'ok' => true,
            'phone' => $phone,
            'verification' => $verification,
        ];
    }

    public function normalizePhoneToE164(string $phone): ?string
    {
        $normalized = preg_replace('/[\s\-\(\)]+/', '', trim($phone)) ?? '';
        if ($normalized === '' || ! str_starts_with($normalized, '+')) {
            return null;
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $normalized) ? $normalized : null;
    }
}
