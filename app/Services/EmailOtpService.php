<?php

namespace App\Services;

use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;

/**
 * Six-digit email codes for people who have no account.
 *
 * The rules match PhoneOtpService — 60 second cooldown, 10 minute expiry, five
 * attempts — but a code is keyed on a context string rather than a user, so an
 * invited buyer or a document recipient can hold one. A code issued for one
 * context can never verify another.
 *
 * Contexts in use:
 *   private_estimate:41   the buyer opening a private estimate
 *   document:128:7        recipient 7 opening document 128
 *
 * Callers own delivery: this service creates and checks codes, and hands the
 * plain code back once so the caller can email it.
 */
class EmailOtpService
{
    public const RESEND_COOLDOWN_SECONDS = 60;

    public const OTP_EXPIRY_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    private const TYPE = 'email';

    /**
     * Issue a code, or refuse while the cooldown is running.
     *
     * @return array{ok: bool, code?: string, message?: string, retry_after?: int, verification?: VerificationCode}
     */
    public function issue(string $context, string $email, ?int $userId = null): array
    {
        $email = mb_strtolower(trim($email));

        $latest = $this->latest($context, $email);
        if ($latest && $latest->last_sent_at && $latest->last_sent_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            return [
                'ok' => false,
                'message' => 'Please wait a moment before requesting another code.',
                'retry_after' => self::RESEND_COOLDOWN_SECONDS - (int) now()->diffInSeconds($latest->last_sent_at, true),
            ];
        }

        // One active code per context at a time.
        VerificationCode::query()
            ->where('context', $context)
            ->where('status', 'pending')
            ->update(['status' => 'failed']);

        $code = (string) random_int(100000, 999999);

        $verification = VerificationCode::query()->create([
            'user_id' => $userId,
            'type' => self::TYPE,
            'context' => $context,
            'email' => $email,
            'code' => '',
            'code_hash' => Hash::make($code),
            'status' => 'pending',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'last_sent_at' => now(),
        ]);

        return ['ok' => true, 'code' => $code, 'verification' => $verification];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function check(string $context, string $email, string $code): array
    {
        $verification = $this->latest($context, mb_strtolower(trim($email)), 'pending');

        if (! $verification) {
            return ['ok' => false, 'message' => 'No active code found. Please request a new code.'];
        }

        if ($verification->expires_at && $verification->expires_at->isPast()) {
            $verification->forceFill(['status' => 'failed'])->save();

            return ['ok' => false, 'message' => 'That code has expired. Please request a new one.'];
        }

        if ((int) ($verification->attempts ?? 0) >= self::MAX_ATTEMPTS) {
            $verification->forceFill(['status' => 'failed'])->save();

            return ['ok' => false, 'message' => 'Too many attempts. Please request a new code.'];
        }

        $verification->forceFill(['attempts' => (int) $verification->attempts + 1])->save();

        if (! Hash::check(trim($code), (string) $verification->code_hash)) {
            $remaining = max(0, self::MAX_ATTEMPTS - (int) $verification->attempts);

            return [
                'ok' => false,
                'message' => $remaining > 0
                    ? "That code is not correct. {$remaining} attempt".($remaining === 1 ? '' : 's').' left.'
                    : 'Too many attempts. Please request a new code.',
            ];
        }

        $verification->forceFill(['status' => 'verified', 'verified_at' => now()])->save();

        return ['ok' => true];
    }

    private function latest(string $context, string $email, ?string $status = null): ?VerificationCode
    {
        return VerificationCode::query()
            ->where('context', $context)
            ->where('email', $email)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->first();
    }
}
