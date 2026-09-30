<?php

namespace App\Services\PrivateEstimate;

use App\Mail\PrivateEstimateOtpMail;
use App\Models\PrivateEstimate;
use App\Models\VerificationCode;
use App\Services\EmailOtpService;
use Illuminate\Support\Facades\Mail;

/**
 * Six-digit email codes for invited buyers (spec 5).
 *
 * The rules live in EmailOtpService, which the secure document centre uses
 * too; this class owns the estimate's context and its email. Codes are stored
 * hashed and bound to one estimate, so a code issued for one cannot open
 * another.
 */
class BuyerEmailOtpService
{
    public const RESEND_COOLDOWN_SECONDS = EmailOtpService::RESEND_COOLDOWN_SECONDS;

    public const OTP_EXPIRY_MINUTES = EmailOtpService::OTP_EXPIRY_MINUTES;

    public const MAX_ATTEMPTS = EmailOtpService::MAX_ATTEMPTS;

    public function __construct(private EmailOtpService $otp) {}

    /**
     * @return array{ok: bool, message?: string, retry_after?: int, verification?: VerificationCode}
     */
    public function send(PrivateEstimate $estimate): array
    {
        $email = mb_strtolower(trim($estimate->buyer_email));
        $result = $this->otp->issue($this->context($estimate), $email, $estimate->buyer_id);

        if (! $result['ok']) {
            return $result;
        }

        Mail::to($email)->send(new PrivateEstimateOtpMail($estimate, $result['code'], self::OTP_EXPIRY_MINUTES));

        return ['ok' => true, 'verification' => $result['verification']];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function verify(PrivateEstimate $estimate, string $code): array
    {
        return $this->otp->check($this->context($estimate), mb_strtolower(trim($estimate->buyer_email)), $code);
    }

    private function context(PrivateEstimate $estimate): string
    {
        return 'private_estimate:'.$estimate->id;
    }
}
