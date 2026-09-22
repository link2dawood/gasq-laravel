<?php

namespace App\Support;

/**
 * Vocabulary for the Private Estimate flow: statuses (spec 67), activity events
 * (spec 66) and the answer sets the buyer intake offers (spec 7-10).
 *
 * Transitions are controlled — a status only moves to one its predecessor
 * allows — so an estimate cannot, say, reveal pricing before a scope is
 * confirmed or a vendor has accepted.
 */
class PrivateEstimate
{
    // ── Statuses ────────────────────────────────────────────────────────────
    public const DRAFT = 'draft';

    public const INVITATION_CREATED = 'invitation_created';

    public const INVITATION_SENT = 'invitation_sent';

    public const INVITATION_OPENED = 'invitation_opened';

    public const BUYER_VERIFIED = 'buyer_verified';

    public const INTAKE_IN_PROGRESS = 'intake_in_progress';

    public const INTAKE_COMPLETED = 'intake_completed';

    public const SCOPE_PENDING_CONFIRMATION = 'scope_pending_confirmation';

    public const SCOPE_CONFIRMED = 'scope_confirmed';

    public const VENDOR_REVIEW = 'vendor_review';

    public const SCOPE_ADJUSTMENT_REQUESTED = 'scope_adjustment_requested';

    public const BUYER_SCOPE_REVIEW = 'buyer_scope_review';

    public const VENDOR_ACCEPTED = 'vendor_accepted';

    public const VENDOR_DECLINED = 'vendor_declined';

    public const PRICING_IN_PROGRESS = 'pricing_in_progress';

    public const ESTIMATE_READY = 'estimate_ready';

    public const ESTIMATE_VIEWED = 'estimate_viewed';

    public const PDF_LOCKED = 'pdf_locked';

    public const PASSWORD_REQUESTED = 'password_requested';

    public const PASSWORD_RELEASED = 'password_released';

    public const ESTIMATE_REVIEWED = 'estimate_reviewed';

    public const BUYER_MEETING_REQUESTED = 'buyer_meeting_requested';

    public const BUYER_SCOPE_ADJUSTMENT_REQUESTED = 'buyer_scope_adjustment_requested';

    public const BUYER_PROCEEDING = 'buyer_proceeding';

    public const BUYER_DECLINED = 'buyer_declined';

    public const AWARD_PENDING = 'award_pending';

    public const AWARDED = 'awarded';

    public const IMPLEMENTATION = 'implementation';

    public const CLOSED = 'closed';

    public const EXPIRED = 'expired';

    public const WITHDRAWN = 'withdrawn';

    /**
     * Allowed next statuses. Terminal states list none.
     *
     * @return array<string, array<int, string>>
     */
    public static function transitions(): array
    {
        return [
            self::DRAFT => [self::INVITATION_CREATED, self::WITHDRAWN],
            // A vendor may hand the link over directly, so a created invitation
            // can be opened without ever being emailed.
            self::INVITATION_CREATED => [self::INVITATION_SENT, self::INVITATION_OPENED, self::WITHDRAWN, self::EXPIRED],
            self::INVITATION_SENT => [self::INVITATION_OPENED, self::INVITATION_SENT, self::EXPIRED, self::WITHDRAWN],
            self::INVITATION_OPENED => [self::BUYER_VERIFIED, self::INVITATION_SENT, self::EXPIRED, self::WITHDRAWN],
            self::BUYER_VERIFIED => [self::INTAKE_IN_PROGRESS, self::EXPIRED, self::WITHDRAWN],
            self::INTAKE_IN_PROGRESS => [self::INTAKE_COMPLETED, self::EXPIRED, self::WITHDRAWN],
            self::INTAKE_COMPLETED => [self::SCOPE_PENDING_CONFIRMATION, self::EXPIRED, self::WITHDRAWN],
            self::SCOPE_PENDING_CONFIRMATION => [self::SCOPE_CONFIRMED, self::INTAKE_IN_PROGRESS, self::EXPIRED, self::WITHDRAWN],
            self::SCOPE_CONFIRMED => [self::VENDOR_REVIEW, self::EXPIRED, self::WITHDRAWN],
            self::VENDOR_REVIEW => [self::VENDOR_ACCEPTED, self::VENDOR_DECLINED, self::SCOPE_ADJUSTMENT_REQUESTED, self::EXPIRED, self::WITHDRAWN],
            self::SCOPE_ADJUSTMENT_REQUESTED => [self::BUYER_SCOPE_REVIEW, self::WITHDRAWN, self::EXPIRED],
            self::BUYER_SCOPE_REVIEW => [self::SCOPE_CONFIRMED, self::BUYER_DECLINED, self::EXPIRED, self::WITHDRAWN],
            self::VENDOR_ACCEPTED => [self::PRICING_IN_PROGRESS, self::WITHDRAWN],
            self::VENDOR_DECLINED => [self::CLOSED],
            self::PRICING_IN_PROGRESS => [self::ESTIMATE_READY, self::WITHDRAWN],
            self::ESTIMATE_READY => [self::ESTIMATE_VIEWED, self::EXPIRED, self::WITHDRAWN],
            self::ESTIMATE_VIEWED => [self::PDF_LOCKED, self::PASSWORD_REQUESTED, self::ESTIMATE_REVIEWED, self::BUYER_MEETING_REQUESTED, self::BUYER_SCOPE_ADJUSTMENT_REQUESTED, self::BUYER_PROCEEDING, self::BUYER_DECLINED, self::EXPIRED, self::WITHDRAWN],
            self::PDF_LOCKED => [self::PASSWORD_REQUESTED, self::ESTIMATE_REVIEWED, self::BUYER_MEETING_REQUESTED, self::BUYER_DECLINED, self::EXPIRED, self::WITHDRAWN],
            self::PASSWORD_REQUESTED => [self::PASSWORD_RELEASED, self::BUYER_MEETING_REQUESTED, self::BUYER_DECLINED, self::EXPIRED, self::WITHDRAWN],
            self::PASSWORD_RELEASED => [self::ESTIMATE_REVIEWED, self::BUYER_MEETING_REQUESTED, self::BUYER_SCOPE_ADJUSTMENT_REQUESTED, self::BUYER_PROCEEDING, self::BUYER_DECLINED, self::EXPIRED, self::WITHDRAWN],
            self::ESTIMATE_REVIEWED => [self::BUYER_PROCEEDING, self::BUYER_MEETING_REQUESTED, self::BUYER_SCOPE_ADJUSTMENT_REQUESTED, self::BUYER_DECLINED, self::EXPIRED, self::WITHDRAWN],
            self::BUYER_MEETING_REQUESTED => [self::ESTIMATE_REVIEWED, self::BUYER_PROCEEDING, self::BUYER_SCOPE_ADJUSTMENT_REQUESTED, self::BUYER_DECLINED, self::EXPIRED, self::WITHDRAWN],
            self::BUYER_SCOPE_ADJUSTMENT_REQUESTED => [self::SCOPE_PENDING_CONFIRMATION, self::BUYER_DECLINED, self::EXPIRED, self::WITHDRAWN],
            self::BUYER_PROCEEDING => [self::AWARD_PENDING, self::AWARDED, self::BUYER_DECLINED, self::CLOSED],
            self::BUYER_DECLINED => [self::CLOSED],
            self::AWARD_PENDING => [self::AWARDED, self::BUYER_DECLINED, self::CLOSED],
            self::AWARDED => [self::IMPLEMENTATION, self::CLOSED],
            self::IMPLEMENTATION => [self::CLOSED],
            self::CLOSED => [],
            self::EXPIRED => [self::INVITATION_SENT, self::CLOSED],
            self::WITHDRAWN => [self::CLOSED],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitions()[$from] ?? [], true);
    }

    /** Human label for a status, e.g. "Password Requested". */
    public static function label(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }

    // ── Activity events (spec 66) ───────────────────────────────────────────
    public const EVENT_INVITATION_CREATED = 'INVITATION_CREATED';

    public const EVENT_INVITATION_SENT = 'INVITATION_SENT';

    public const EVENT_INVITATION_OPENED = 'INVITATION_OPENED';

    public const EVENT_EMAIL_VERIFIED = 'EMAIL_VERIFIED';

    public const EVENT_INTAKE_STARTED = 'INTAKE_STARTED';

    public const EVENT_INTAKE_COMPLETED = 'INTAKE_COMPLETED';

    public const EVENT_SCOPE_CONFIRMED = 'SCOPE_CONFIRMED';

    public const EVENT_SCOPE_CHANGE_REQUESTED = 'SCOPE_CHANGE_REQUESTED';

    public const EVENT_SCOPE_VERSION_CREATED = 'SCOPE_VERSION_CREATED';

    public const EVENT_VENDOR_ACCEPTED = 'VENDOR_ACCEPTED';

    public const EVENT_VENDOR_DECLINED = 'VENDOR_DECLINED';

    public const EVENT_PRICING_CALCULATED = 'PRICING_CALCULATED';

    public const EVENT_ESTIMATE_READY = 'ESTIMATE_READY';

    public const EVENT_ESTIMATE_VIEWED = 'ESTIMATE_VIEWED';

    public const EVENT_ESTIMATE_REVIEWED = 'ESTIMATE_REVIEWED';

    public const EVENT_PDF_GENERATED = 'PDF_GENERATED';

    public const EVENT_PDF_DOWNLOADED = 'PDF_DOWNLOADED';

    public const EVENT_PASSWORD_REQUESTED = 'PASSWORD_REQUESTED';

    public const EVENT_PASSWORD_RELEASED = 'PASSWORD_RELEASED';

    public const EVENT_PASSWORD_REGENERATED = 'PASSWORD_REGENERATED';

    public const EVENT_MEETING_REQUESTED = 'MEETING_REQUESTED';

    public const EVENT_BUYER_PROCEEDED = 'BUYER_PROCEEDED';

    public const EVENT_BUYER_DECLINED = 'BUYER_DECLINED';

    public const EVENT_ESTIMATE_WITHDRAWN = 'ESTIMATE_WITHDRAWN';

    public const EVENT_ESTIMATE_EXPIRED = 'ESTIMATE_EXPIRED';

    public const EVENT_AWARD_PENDING = 'AWARD_PENDING';

    public const EVENT_AWARD_CONFIRMED = 'AWARD_CONFIRMED';

    public const EVENT_IMPLEMENTATION_STARTED = 'IMPLEMENTATION_STARTED';

    // ── Intake answer sets (spec 7-10, 48-49) ───────────────────────────────

    /** @return array<string, string> */
    public static function decisionMakerOptions(): array
    {
        return [
            'decision_maker' => 'Yes — I am the decision maker',
            'authorized_representative' => 'Yes — I am an authorized representative',
            'another_approver' => 'No — Another person must approve',
        ];
    }

    /** @return array<string, string> */
    public static function budgetOptions(): array
    {
        return [
            'approved' => 'Yes — Budget Approved',
            'pending' => 'Pending Approval',
            'not_approved' => 'No — Budget Not Approved',
        ];
    }

    /** @return array<string, string> */
    public static function intentOptions(): array
    {
        return [
            'schedule_interview' => 'Schedule interview',
            'schedule_site_visit' => 'Schedule site visit',
            'begin_contract' => 'Begin contract discussions',
            'present_internally' => 'Present internally',
            'compare_current_cost' => 'Compare current cost',
            'request_information' => 'Request information',
            'planning_only' => 'Planning/budgeting only',
        ];
    }

    /** @return array<string, string> */
    public static function serviceTypes(): array
    {
        return [
            'unarmed' => 'Unarmed',
            'armed' => 'Armed',
            'mobile_patrol' => 'Mobile Patrol',
            'off_duty_police' => 'Off-Duty Police',
            'executive_protection' => 'Executive Protection',
            'event_security' => 'Event Security',
            'concierge' => 'Concierge Security',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function declineReasons(): array
    {
        return [
            'price' => 'Price',
            'budget' => 'Budget',
            'timing' => 'Timing',
            'current_vendor_retained' => 'Current vendor retained',
            'different_vendor_selected' => 'Different vendor selected',
            'project_canceled' => 'Project canceled',
            'scope_changed' => 'Scope changed',
            'internal_security' => 'Internal security',
            'other' => 'Other',
        ];
    }

    /** Asked only when the decline reason is price (spec 49). @return array<string, string> */
    public static function priceComparisons(): array
    {
        return [
            'current_vendor' => 'Current vendor',
            'competing_quote' => 'Competing quote',
            'approved_budget' => 'Approved budget',
            'internal_cost' => 'Internal cost',
            'expected_market_rate' => 'Expected market rate',
            'other' => 'Other',
        ];
    }

    /**
     * Price-driving fields. Changing one of these makes a new scope version;
     * anything else is a clarification (spec 16).
     *
     * @return array<int, string>
     */
    public static function priceDrivingFields(): array
    {
        return [
            'service_type', 'posts', 'locations', 'hours_per_day', 'days_per_week',
            'weeks_per_year', 'weekly_hours', 'annual_hours', 'guards_required',
            'baseline_wage', 'start_date', 'contract_term', 'equipment', 'vehicle', 'supervisor',
        ];
    }
}
