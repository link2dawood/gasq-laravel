<?php

namespace App\Support;

/**
 * Vocabulary for the GASQ Secure Document Center.
 *
 * Lifecycle and engagement are deliberately separate. The statuses below are
 * lifecycle only — a document sits in exactly one, and moves through a
 * controlled map. Whether a buyer opened, revisited or engaged heavily is
 * derived from document_events, so reading a document never rewrites its state.
 */
class SecureDocument
{
    // ── Lifecycle status ────────────────────────────────────────────────────
    public const DRAFT = 'draft';

    public const READY = 'ready';

    public const SENT = 'sent';

    public const EXPIRED = 'expired';

    public const REVOKED = 'revoked';

    public const SUPERSEDED = 'superseded';

    public const ARCHIVED = 'archived';

    /** @return array<string, array<int, string>> */
    public static function transitions(): array
    {
        return [
            self::DRAFT => [self::READY, self::ARCHIVED],
            self::READY => [self::SENT, self::DRAFT, self::ARCHIVED],
            self::SENT => [self::EXPIRED, self::REVOKED, self::SUPERSEDED, self::ARCHIVED],
            self::EXPIRED => [self::SENT, self::SUPERSEDED, self::ARCHIVED],
            self::REVOKED => [self::SENT, self::ARCHIVED],
            self::SUPERSEDED => [self::ARCHIVED],
            self::ARCHIVED => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitions()[$from] ?? [], true);
    }

    public static function label(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }

    // ── Document types (spec 3) ─────────────────────────────────────────────
    /** @return array<string, array{label: string, prefix: string}> */
    public static function types(): array
    {
        return [
            'procurement_appraisal' => ['label' => 'GASQ Procurement Appraisal', 'prefix' => 'APP'],
            'service_estimate' => ['label' => 'GASQ Service Estimate', 'prefix' => 'EST'],
            'cost_to_protect' => ['label' => 'Cost-to-Protect Analysis', 'prefix' => 'CTP'],
            'cost_to_deliver' => ['label' => 'Cost-to-Deliver Analysis', 'prefix' => 'CTD'],
            'capital_recovery' => ['label' => 'Capital Recovery Analysis', 'prefix' => 'CRO'],
            'inhouse_vs_outsource' => ['label' => 'In-House vs Outsource Comparison', 'prefix' => 'IVO'],
            'rescope' => ['label' => 'Rescope of Service Report', 'prefix' => 'RES'],
            'vendor_estimate' => ['label' => 'Vendor Estimate', 'prefix' => 'VEN'],
            'buyer_pricing' => ['label' => 'Buyer Pricing Report', 'prefix' => 'BPR'],
            'procurement_recommendation' => ['label' => 'Procurement Recommendation', 'prefix' => 'REC'],
            'contract_pricing' => ['label' => 'Contract Pricing Analysis', 'prefix' => 'CPA'],
            'supporting' => ['label' => 'Supporting Documentation', 'prefix' => 'SUP'],
            'other' => ['label' => 'Other GASQ Document', 'prefix' => 'DOC'],
        ];
    }

    public static function typeLabel(string $type): string
    {
        return self::types()[$type]['label'] ?? self::label($type);
    }

    public static function typePrefix(string $type): string
    {
        return self::types()[$type]['prefix'] ?? 'DOC';
    }

    // ── Security levels (spec 12) ───────────────────────────────────────────
    public const LEVEL_OTP = 'otp';

    public const LEVEL_OTP_PASSWORD = 'otp_password';

    public const LEVEL_OTP_PASSWORD_EXPIRY = 'otp_password_expiry';

    public const LEVEL_MAXIMUM = 'maximum';

    /** @return array<string, string> */
    public static function securityLevels(): array
    {
        return [
            self::LEVEL_OTP => 'Email verification',
            self::LEVEL_OTP_PASSWORD => 'Email verification and document password',
            self::LEVEL_OTP_PASSWORD_EXPIRY => 'Email verification, password and expiry',
            self::LEVEL_MAXIMUM => 'Email verification, password, expiry, no download or print, watermark',
        ];
    }

    public static function requiresPassword(string $level): bool
    {
        return in_array($level, [self::LEVEL_OTP_PASSWORD, self::LEVEL_OTP_PASSWORD_EXPIRY, self::LEVEL_MAXIMUM], true);
    }

    // ── Stakeholder policy (spec 32) ────────────────────────────────────────
    public const POLICY_SAME_DOMAIN = 'same_domain';

    public const POLICY_GASQ_APPROVAL = 'gasq_approval';

    public const POLICY_RECIPIENT_APPROVAL = 'recipient_approval';

    public const POLICY_CLOSED = 'closed';

    /** @return array<string, string> */
    public static function stakeholderPolicies(): array
    {
        return [
            self::POLICY_SAME_DOMAIN => 'Approve automatically for the same email domain',
            self::POLICY_GASQ_APPROVAL => 'GASQ approves each request',
            self::POLICY_RECIPIENT_APPROVAL => 'The original recipient approves',
            self::POLICY_CLOSED => 'No additional viewers',
        ];
    }

    // ── Recipient types ─────────────────────────────────────────────────────
    public const RECIPIENT_PRIMARY = 'primary';

    public const RECIPIENT_STAKEHOLDER = 'stakeholder';

    public const RECIPIENT_GASQ = 'gasq';

    public const RECIPIENT_VENDOR = 'vendor';

    // ── Events (spec 25) ────────────────────────────────────────────────────
    public const EVENT_CREATED = 'DOCUMENT_CREATED';

    public const EVENT_UPLOADED = 'DOCUMENT_UPLOADED';

    public const EVENT_SENT = 'DOCUMENT_SENT';

    public const EVENT_LINK_CLICKED = 'SECURE_LINK_CLICKED';

    public const EVENT_VERIFICATION_STARTED = 'IDENTITY_VERIFICATION_STARTED';

    public const EVENT_VERIFIED = 'IDENTITY_VERIFIED';

    public const EVENT_VERIFICATION_FAILED = 'IDENTITY_VERIFICATION_FAILED';

    public const EVENT_PASSWORD_FAILED = 'PASSWORD_FAILED';

    public const EVENT_PASSWORD_SUCCESSFUL = 'PASSWORD_SUCCESSFUL';

    public const EVENT_OPENED = 'DOCUMENT_OPENED';

    public const EVENT_REVISITED = 'DOCUMENT_REVISITED';

    public const EVENT_PAGE_VIEWED = 'PAGE_VIEWED';

    public const EVENT_CLOSED = 'DOCUMENT_CLOSED';

    public const EVENT_DOWNLOAD_REQUESTED = 'DOWNLOAD_REQUESTED';

    public const EVENT_DOWNLOAD_APPROVED = 'DOWNLOAD_APPROVED';

    public const EVENT_DOWNLOAD_DECLINED = 'DOWNLOAD_DECLINED';

    public const EVENT_DOWNLOAD_COMPLETED = 'DOWNLOAD_COMPLETED';

    public const EVENT_STAKEHOLDER_REQUESTED = 'STAKEHOLDER_ACCESS_REQUESTED';

    public const EVENT_STAKEHOLDER_APPROVED = 'STAKEHOLDER_APPROVED';

    public const EVENT_STAKEHOLDER_DENIED = 'STAKEHOLDER_DENIED';

    public const EVENT_ACCESS_EXPIRED = 'ACCESS_EXPIRED';

    public const EVENT_ACCESS_REVOKED = 'ACCESS_REVOKED';

    public const EVENT_ACCESS_RESTORED = 'ACCESS_RESTORED';

    public const EVENT_VERSION_CREATED = 'DOCUMENT_VERSION_CREATED';

    /**
     * Why a viewer was turned away. Kept separate from event names because
     * these are shown to the person, not logged as history.
     *
     * @return array<string, string>
     */
    public static function denialMessages(): array
    {
        return [
            'not_found' => 'This link does not match a document we hold.',
            'revoked' => 'This document is no longer available. Please contact GASQ for access.',
            'expired' => 'This document is no longer available. Please contact GASQ for access.',
            'recipient_revoked' => 'Your access to this document has been withdrawn. Please contact GASQ.',
            'not_sent' => 'This document has not been issued yet.',
        ];
    }
}
