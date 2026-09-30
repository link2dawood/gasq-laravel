<?php

namespace App\Services\SecureDocument;

use App\Models\DocumentAccessRequest;
use App\Models\DocumentAccessToken;
use App\Models\DocumentEvent;
use App\Models\DocumentRecipient;
use App\Models\DocumentSession;
use App\Models\DocumentVersion;
use App\Models\SecureDocument;
use App\Models\User;
use App\Services\EmailOtpService;
use App\Support\SecureDocument as Flow;
use Illuminate\Support\Str;

/**
 * Who may open a document, and what happened while they did.
 *
 * The rules that matter:
 *   A link identifies a recipient. It never authorises one — the code sent to
 *   that recipient's own address does.
 *   A person holding a forwarded link is a stranger, and is offered the
 *   stakeholder route rather than the document.
 *   Revoking the document, or that one recipient, stops the next request.
 */
class DocumentAccessService
{
    /** How long a viewing session stays valid without re-verifying. */
    public const SESSION_HOURS = 12;

    /** A viewer idle longer than this is not counted as reading (spec 27). */
    public const IDLE_SECONDS = 90;

    public function __construct(private EmailOtpService $otp) {}

    // ── Links ───────────────────────────────────────────────────────────────

    /**
     * Issue a recipient's own link, replacing any earlier one.
     *
     * @return array{token: DocumentAccessToken, plain: string, url: string}
     */
    public function issueLink(SecureDocument $document, DocumentRecipient $recipient): array
    {
        $recipient->tokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);

        $plain = Str::random(48);

        $token = $recipient->tokens()->create([
            'document_id' => $document->id,
            'token_hash' => $this->hash($plain),
            'expires_at' => $document->expires_at,
        ]);

        return [
            'token' => $token,
            'plain' => $plain,
            'url' => route('secure-documents.open', ['token' => $plain]),
        ];
    }

    /**
     * Resolve a link.
     *
     * @return array{ok: bool, token?: DocumentAccessToken, document?: SecureDocument, recipient?: DocumentRecipient, reason?: string}
     */
    public function resolve(string $plain): array
    {
        $token = DocumentAccessToken::query()
            ->with(['document.currentVersion', 'recipient'])
            ->where('token_hash', $this->hash($plain))
            ->first();

        if (! $token || ! $token->document || ! $token->recipient) {
            return ['ok' => false, 'reason' => 'not_found'];
        }

        $document = $token->document;
        $recipient = $token->recipient;

        if ($document->isRevoked()) {
            return ['ok' => false, 'reason' => 'revoked'];
        }
        // Checked before the token, because revoking a person also revokes
        // their token and they deserve the accurate reason.
        if ($recipient->isRevoked()) {
            return ['ok' => false, 'reason' => 'recipient_revoked'];
        }
        if ($document->isExpired() || ! $token->isUsable()) {
            return ['ok' => false, 'reason' => 'expired'];
        }
        if ($document->status !== Flow::SENT) {
            return ['ok' => false, 'reason' => 'not_sent'];
        }

        $token->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => request()?->ip(),
        ])->save();

        return ['ok' => true, 'token' => $token, 'document' => $document, 'recipient' => $recipient];
    }

    // ── Identity ────────────────────────────────────────────────────────────

    /**
     * Send a code to the recipient's own address. The address is never taken
     * from the request, so a forwarded link cannot redirect the code.
     *
     * @return array{ok: bool, code?: string, message?: string}
     */
    public function sendCode(SecureDocument $document, DocumentRecipient $recipient): array
    {
        $result = $this->otp->issue($this->context($document, $recipient), $recipient->email, $recipient->user_id);

        if ($result['ok']) {
            $this->log($document, Flow::EVENT_VERIFICATION_STARTED, [], recipient: $recipient);
        }

        return $result;
    }

    /** @return array{ok: bool, message?: string} */
    public function verifyCode(SecureDocument $document, DocumentRecipient $recipient, string $code): array
    {
        $result = $this->otp->check($this->context($document, $recipient), $recipient->email, $code);

        if (! $result['ok']) {
            $this->log($document, Flow::EVENT_VERIFICATION_FAILED, [], recipient: $recipient);

            return $result;
        }

        $recipient->forceFill([
            'access_status' => DocumentRecipient::VERIFIED,
            'verified_at' => $recipient->verified_at ?? now(),
        ])->save();

        $this->log($document, Flow::EVENT_VERIFIED, [], recipient: $recipient);

        return $result;
    }

    /** Whether a document password, when one is set, matches. */
    public function checkPassword(SecureDocument $document, DocumentRecipient $recipient, string $password): bool
    {
        $ok = $document->password_hash !== null && password_verify($password, (string) $document->password_hash);

        $this->log(
            $document,
            $ok ? Flow::EVENT_PASSWORD_SUCCESSFUL : Flow::EVENT_PASSWORD_FAILED,
            [],
            recipient: $recipient,
        );

        return $ok;
    }

    // ── Sessions ────────────────────────────────────────────────────────────

    /**
     * Start a viewing session, and record whether this is a first view or a
     * return visit. A revisit is a new session after an earlier one, which is
     * what makes "viewed six times" mean something.
     */
    public function startSession(SecureDocument $document, DocumentRecipient $recipient, ?DocumentVersion $version = null): DocumentSession
    {
        $returning = $recipient->session_count > 0;

        $session = DocumentSession::create([
            'public_id' => 'SES-'.strtoupper(Str::random(10)),
            'document_id' => $document->id,
            'document_version_id' => ($version ?? $document->currentVersion)?->id,
            'document_recipient_id' => $recipient->id,
            'started_at' => now(),
            'last_seen_at' => now(),
            'ip_address' => request()?->ip(),
            'device_type' => $this->deviceType(),
        ]);

        $recipient->forceFill([
            'session_count' => (int) $recipient->session_count + 1,
            'first_viewed_at' => $recipient->first_viewed_at ?? now(),
            'last_viewed_at' => now(),
        ])->save();

        $this->log(
            $document,
            $returning ? Flow::EVENT_REVISITED : Flow::EVENT_OPENED,
            ['session' => $session->public_id],
            version: $version ?? $document->currentVersion,
            recipient: $recipient,
            session: $session,
        );

        return $session;
    }

    /**
     * A heartbeat from the open viewer. Only counts time since the last beat,
     * and only when that gap is short enough to be someone actually reading.
     */
    public function heartbeat(DocumentSession $session, int $secondsSinceLast): DocumentSession
    {
        $counted = max(0, min($secondsSinceLast, self::IDLE_SECONDS));

        $session->forceFill([
            'active_seconds' => (int) $session->active_seconds + $counted,
            'last_seen_at' => now(),
        ])->save();

        return $session;
    }

    public function endSession(DocumentSession $session): DocumentSession
    {
        if ($session->ended_at === null) {
            $session->forceFill(['ended_at' => now()])->save();

            $this->log(
                $session->document,
                Flow::EVENT_CLOSED,
                ['session' => $session->public_id, 'active_seconds' => $session->active_seconds],
                recipient: $session->recipient,
                session: $session,
            );
        }

        return $session;
    }

    // ── Strangers ───────────────────────────────────────────────────────────

    /**
     * Someone who is not the recipient asked for access. What happens next is
     * the document's policy: same-domain colleagues can be admitted after
     * verifying, everyone else waits for a decision.
     *
     * @return array{outcome: string, request?: DocumentAccessRequest, recipient?: DocumentRecipient}
     *                                                                                                outcome is 'granted', 'pending' or 'closed'.
     */
    public function requestStakeholderAccess(SecureDocument $document, string $email, ?string $name = null, ?string $company = null): array
    {
        $email = mb_strtolower(trim($email));

        // Already invited? Send them through their own access instead.
        if ($existing = $document->recipientFor($email)) {
            return ['outcome' => $existing->isRevoked() ? 'closed' : 'granted', 'recipient' => $existing];
        }

        if ($document->stakeholder_policy === Flow::POLICY_CLOSED) {
            $this->log($document, Flow::EVENT_STAKEHOLDER_DENIED, ['email' => $email, 'policy' => 'closed']);

            return ['outcome' => 'closed'];
        }

        if ($document->stakeholder_policy === Flow::POLICY_SAME_DOMAIN && $this->sameDomainAsPrimary($document, $email)) {
            $recipient = $document->recipients()->create([
                'email' => $email,
                'name' => $name,
                'company' => $company,
                'recipient_type' => Flow::RECIPIENT_STAKEHOLDER,
                'access_status' => DocumentRecipient::INVITED,
            ]);

            $this->log($document, Flow::EVENT_STAKEHOLDER_APPROVED, [
                'email' => $email,
                'policy' => 'same_domain',
            ], recipient: $recipient);

            return ['outcome' => 'granted', 'recipient' => $recipient];
        }

        $request = DocumentAccessRequest::create([
            'document_id' => $document->id,
            'requester_email' => $email,
            'requester_name' => $name,
            'requester_company' => $company,
            'request_type' => 'access',
            'status' => DocumentAccessRequest::PENDING,
            'ip_address' => request()?->ip(),
        ]);

        $this->log($document, Flow::EVENT_STAKEHOLDER_REQUESTED, ['email' => $email]);

        return ['outcome' => 'pending', 'request' => $request];
    }

    /** GASQ lets a requester in. They still verify their own address. */
    public function approveStakeholder(DocumentAccessRequest $request, User $actor): DocumentRecipient
    {
        $document = $request->document;

        $recipient = $document->recipients()->create([
            'email' => $request->requester_email,
            'name' => $request->requester_name,
            'company' => $request->requester_company,
            'recipient_type' => Flow::RECIPIENT_STAKEHOLDER,
            'access_status' => DocumentRecipient::INVITED,
            'added_by' => $actor->id,
        ]);

        $request->forceFill([
            'status' => DocumentAccessRequest::APPROVED,
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ])->save();

        $this->log($document, Flow::EVENT_STAKEHOLDER_APPROVED, [
            'email' => $request->requester_email,
        ], recipient: $recipient, actor: $actor);

        return $recipient;
    }

    public function denyStakeholder(DocumentAccessRequest $request, User $actor, ?string $note = null): DocumentAccessRequest
    {
        $request->forceFill([
            'status' => DocumentAccessRequest::DENIED,
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ])->save();

        $this->log($request->document, Flow::EVENT_STAKEHOLDER_DENIED, [
            'email' => $request->requester_email,
            'note' => $note,
        ], actor: $actor);

        return $request;
    }

    /** Stop one person without touching the others. */
    public function revokeRecipient(DocumentRecipient $recipient, User $actor, ?string $reason = null): DocumentRecipient
    {
        $recipient->forceFill([
            'access_status' => DocumentRecipient::REVOKED,
            'revoked_at' => now(),
        ])->save();
        $recipient->tokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);

        $this->log($recipient->document, Flow::EVENT_ACCESS_REVOKED, [
            'email' => $recipient->email,
            'reason' => $reason,
        ], recipient: $recipient, actor: $actor);

        return $recipient;
    }

    // ── Trail ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        SecureDocument $document,
        string $event,
        array $metadata = [],
        ?DocumentVersion $version = null,
        ?DocumentRecipient $recipient = null,
        ?DocumentSession $session = null,
        ?User $actor = null,
        ?int $page = null,
    ): ?DocumentEvent {
        try {
            $request = request();

            return DocumentEvent::create([
                'document_id' => $document->id,
                'document_version_id' => $version?->id ?? $document->current_version_id,
                'document_recipient_id' => $recipient?->id,
                'document_session_id' => $session?->id,
                'actor_user_id' => $actor?->id,
                'event_type' => $event,
                'page_number' => $page,
                'metadata' => $metadata ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => substr((string) $request?->userAgent(), 0, 500) ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sameDomainAsPrimary(SecureDocument $document, string $email): bool
    {
        $domain = Str::after($email, '@');
        if ($domain === '' || ! str_contains($email, '@')) {
            return false;
        }

        return $document->recipients()
            ->where('recipient_type', Flow::RECIPIENT_PRIMARY)
            ->get()
            ->contains(fn (DocumentRecipient $r) => Str::after(mb_strtolower($r->email), '@') === $domain);
    }

    private function context(SecureDocument $document, DocumentRecipient $recipient): string
    {
        return 'document:'.$document->id.':'.$recipient->id;
    }

    private function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    private function deviceType(): string
    {
        $agent = (string) request()?->userAgent();

        return match (true) {
            (bool) preg_match('/iPad|Tablet/i', $agent) => 'tablet',
            (bool) preg_match('/Mobile|iPhone|Android/i', $agent) => 'mobile',
            default => 'desktop',
        };
    }
}
