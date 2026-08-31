<?php

namespace App\Support;

/**
 * The opportunity lifecycle (review spec §28).
 *
 * Standardised in one place because these statuses will drive buyer dashboards, vendor
 * dashboards, notifications and reporting as the marketplace scales — and a lifecycle
 * spelled differently in four views is a lifecycle nobody can report on.
 *
 * Deliberately separate from `offer_status` (open/hired/closed_no_hire), which belongs to
 * the hire/award path and means something narrower.
 */
class OpportunityStatus
{
    public const DRAFT = 'draft';
    public const VALIDATION_REQUIRED = 'validation_required';
    public const READY_FOR_RELEASE = 'ready_for_release';
    public const OPEN_TO_VENDORS = 'open_to_vendors';
    public const RESPONSES_RECEIVED = 'responses_received';
    public const CLARIFICATION_PENDING = 'clarification_pending';
    public const SITE_VISITS_INTERVIEWS = 'site_visits_interviews';
    public const SELECTION_PENDING = 'selection_pending';
    public const VENDOR_SELECTED = 'vendor_selected';
    public const PRICE_REVEAL = 'price_reveal';
    public const CONTRACT_PENDING = 'contract_pending';
    public const AWARDED = 'awarded';
    public const CLOSED = 'closed';
    public const WITHDRAWN = 'withdrawn';

    /** Ordered, so progress can be rendered without a second lookup table. */
    public const LABELS = [
        self::DRAFT => 'Draft',
        self::VALIDATION_REQUIRED => 'Validation required',
        self::READY_FOR_RELEASE => 'Ready for release',
        self::OPEN_TO_VENDORS => 'Open to vendors',
        self::RESPONSES_RECEIVED => 'Vendor responses received',
        self::CLARIFICATION_PENDING => 'Clarification pending',
        self::SITE_VISITS_INTERVIEWS => 'Site visits / interviews',
        self::SELECTION_PENDING => 'Vendor selection pending',
        self::VENDOR_SELECTED => 'Vendor selected',
        self::PRICE_REVEAL => 'Price reveal',
        self::CONTRACT_PENDING => 'Contract pending',
        self::AWARDED => 'Awarded',
        self::CLOSED => 'Closed',
        self::WITHDRAWN => 'Withdrawn',
    ];

    /** Statuses where the opportunity is live to the vendor network. */
    public const LIVE = [
        self::OPEN_TO_VENDORS,
        self::RESPONSES_RECEIVED,
        self::CLARIFICATION_PENDING,
        self::SITE_VISITS_INTERVIEWS,
        self::SELECTION_PENDING,
    ];

    /** Terminal statuses — no further progression. */
    public const TERMINAL = [
        self::AWARDED,
        self::CLOSED,
        self::WITHDRAWN,
    ];

    public static function label(?string $status): string
    {
        return self::LABELS[$status] ?? 'Unknown';
    }

    public static function isLive(?string $status): bool
    {
        return in_array($status, self::LIVE, true);
    }

    public static function isTerminal(?string $status): bool
    {
        return in_array($status, self::TERMINAL, true);
    }

    /**
     * Has the opportunity been released to vendors yet?
     *
     * Scope changes before release are just edits; after release they may require a new
     * version and vendor re-acknowledgement (§6).
     */
    public static function isReleased(?string $status): bool
    {
        return $status !== null
            && ! in_array($status, [self::DRAFT, self::VALIDATION_REQUIRED, self::READY_FOR_RELEASE], true);
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }
}
