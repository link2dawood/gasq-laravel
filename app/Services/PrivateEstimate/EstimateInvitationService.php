<?php

namespace App\Services\PrivateEstimate;

use App\Mail\PrivateEstimateInvitationMail;
use App\Models\EstimateInvitation;
use App\Models\PrivateEstimate;
use App\Models\User;
use App\Support\PrivateEstimate as Flow;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Issues and resolves invitation links (spec 2-3).
 *
 * The token is shown exactly once, in the invitation email. Only its SHA-256
 * lands in the database, so a leaked database copy cannot rebuild a working
 * link. Tokens expire, can be revoked, and the link alone never authorises
 * anything: the buyer still has to pass the email OTP (spec 5).
 */
class EstimateInvitationService
{
    /** Expiry choices the vendor picks from, in days (spec 3). */
    public const EXPIRY_CHOICES = [3, 7, 15, 30];

    public const DEFAULT_EXPIRY_DAYS = 7;

    public function __construct(private PrivateEstimateService $estimates) {}

    /**
     * Create an invitation and return it with the one-time plain token.
     *
     * @return array{invitation: EstimateInvitation, token: string, url: string}
     */
    public function issue(PrivateEstimate $estimate, ?int $expiresInDays = null, ?User $actor = null): array
    {
        // Any earlier link stops working the moment a new one is issued.
        $estimate->invitations()->whereNull('revoked_at')->update([
            'revoked_at' => now(),
            'revoked_reason' => 'Replaced by a new invitation',
        ]);

        $token = Str::random(48);
        $days = in_array($expiresInDays, self::EXPIRY_CHOICES, true)
            ? $expiresInDays
            : ($expiresInDays > 0 ? $expiresInDays : self::DEFAULT_EXPIRY_DAYS);

        $invitation = $estimate->invitations()->create([
            'token_hash' => $this->hash($token),
            'invited_email' => $estimate->buyer_email,
            'expires_at' => now()->addDays($days),
        ]);

        return [
            'invitation' => $invitation,
            'token' => $token,
            'url' => route('private-estimates.buyer.show', ['token' => $token]),
        ];
    }

    /** Issue a link and email it to the buyer. Sent synchronously: no queue worker runs. */
    public function issueAndSend(PrivateEstimate $estimate, ?int $expiresInDays = null, ?User $actor = null): EstimateInvitation
    {
        ['invitation' => $invitation, 'url' => $url] = $this->issue($estimate, $expiresInDays, $actor);

        Mail::to($estimate->buyer_email)->send(new PrivateEstimateInvitationMail($estimate, $url, $invitation->expires_at));

        $invitation->forceFill(['sent_at' => now()])->save();
        $this->estimates->transition(
            $estimate,
            Flow::INVITATION_SENT,
            $actor,
            $actor ? 'vendor' : 'system',
            Flow::EVENT_INVITATION_SENT,
            ['expires_at' => $invitation->expires_at->toIso8601String()],
        );

        return $invitation;
    }

    /**
     * Find a usable invitation for a plain token.
     *
     * @return array{invitation: ?EstimateInvitation, reason: ?string}
     *                                                                 reason is 'not_found', 'expired' or 'revoked' when there is no usable link.
     */
    public function resolve(string $token): array
    {
        $invitation = EstimateInvitation::query()
            ->with('privateEstimate')
            ->where('token_hash', $this->hash($token))
            ->first();

        if (! $invitation) {
            return ['invitation' => null, 'reason' => 'not_found'];
        }
        if ($invitation->isRevoked()) {
            return ['invitation' => null, 'reason' => 'revoked'];
        }
        if ($invitation->isExpired()) {
            return ['invitation' => null, 'reason' => 'expired'];
        }

        return ['invitation' => $invitation, 'reason' => null];
    }

    /** First open of the link. Records the moment and nudges the status along. */
    public function markOpened(EstimateInvitation $invitation): void
    {
        if ($invitation->opened_at === null) {
            $invitation->forceFill(['opened_at' => now()])->save();
        }

        $estimate = $invitation->privateEstimate;
        if ($estimate && $this->estimates->can($estimate, Flow::INVITATION_OPENED) && $estimate->status !== Flow::INVITATION_OPENED) {
            $this->estimates->transition($estimate, Flow::INVITATION_OPENED, null, 'buyer', Flow::EVENT_INVITATION_OPENED);
        }
    }

    public function revoke(EstimateInvitation $invitation, string $reason): void
    {
        $invitation->forceFill(['revoked_at' => now(), 'revoked_reason' => $reason])->save();
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
