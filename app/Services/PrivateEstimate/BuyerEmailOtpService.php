<?php

namespace App\Services\PrivateEstimate;

use App\Mail\PrivateEstimateOtpMail;
use App\Models\PrivateEstimate;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Six-digit email codes for invited buyers (spec 5).
 *
 * Mirrors PhoneOtpService — same cooldown, expiry and attempt ceiling — but
 * keyed on the estimate and email rather than a user, because an invited buyer
 * has no account. Codes are stored hashed and a code is bound to one estimate,
 * so a code issued for one estimate cannot open another.
 */
class BuyerEmailOtpService
{
    public const RESEND_COOLDOWN_SECONDS = 60;

    public const OTP_EXPIRY_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    private const TYPE = 'email';

    /**
     * @return array{ok: bool, message?: string, retry_after?: int, verification?: VerificationCode}
     */
    public function send(PrivateEstimate $estimate): array
    {
        $email = mb_strtolower(trim($estimate->buyer_email));
        $context = $this->context($estimate);

        $latest = $this->latest($email, $context);
        if ($latest && $latest->last_sent_at && $latest->last_sent_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            return [
                'ok' => false,
                'message' => 'Please wait a moment before requesting another code.',
                'retry_after' => self::RESEND_COOLDOWN_SECONDS - now()->diffInSeconds($latest->last_sent_at, true),
            ];
        }

        // One active code at a time for this estimate.
        VerificationCode::query()
            ->where('context', $context)
            ->where('status', 'pending')
            ->update(['status' => 'failed']);

        $code = (string) random_int(100000, 999999);

        $verification = VerificationCode::query()->create([
            'user_id' => $estimate->buyer_id,
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

        Mail::to($email)->send(new PrivateEstimateOtpMail($estimate, $code, self::OTP_EXPIRY_MINUTES));

        return ['ok' => true, 'verification' => $verification];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function verify(PrivateEstimate $estimate, string $code): array
    {
        $context = $this->context($estimate);
        $verification = $this->latest(mb_strtolower(trim($estimate->buyer_email)), $context, 'pending');

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

    private function latest(string $email, string $context, ?string $status = null): ?VerificationCode
    {
        return VerificationCode::query()
            ->where('context', $context)
            ->where('email', $email)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->first();
    }

    private function context(PrivateEstimate $estimate): string
    {
        return 'private_estimate:'.$estimate->id;
    }
}
