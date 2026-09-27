<?php

namespace App\Support;

/**
 * The GASQ Vendor Access sequence.
 *
 * Ten stages in a fixed order. A stage opens only when every stage before it
 * is complete, which is what keeps the promise the product is built on:
 * qualifications first, operating solution next, price last.
 *
 * Stage keys are stable and stored; labels are for display only.
 */
class VendorEngagement
{
    public const OPPORTUNITY = 'opportunity';

    public const RESPONSE = 'response';

    public const QUALIFY = 'qualify';

    public const MEET = 'meet';

    public const ASSESS = 'assess';

    public const INTERVIEW = 'interview';

    public const SOLUTION = 'solution';

    public const SELECTION = 'selection';

    public const PRICE = 'price';

    public const SUCCESS_FEE = 'success_fee';

    // ── Engagement status ───────────────────────────────────────────────────
    public const STATUS_ACTIVE = 'active';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_ADJUSTMENT_REQUESTED = 'adjustment_requested';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_AWARDED = 'awarded';

    public const STATUS_CLOSED = 'closed';

    /**
     * The sequence, in order. `gate` names who completes the stage:
     * vendor, buyer, or gasq (an administrator).
     *
     * @return array<string, array{label: string, blurb: string, gate: string}>
     */
    public static function stages(): array
    {
        return [
            self::OPPORTUNITY => [
                'label' => 'Opportunity',
                'blurb' => 'Review the qualified buyer opportunity.',
                'gate' => 'vendor',
            ],
            self::RESPONSE => [
                'label' => 'Accept / Decline',
                'blurb' => 'Accept, decline, or request a scope adjustment.',
                'gate' => 'vendor',
            ],
            self::QUALIFY => [
                'label' => 'Qualify',
                'blurb' => 'Complete vendor qualification and upload current documents.',
                'gate' => 'vendor',
            ],
            self::MEET => [
                'label' => 'Meet',
                'blurb' => 'Meet the decision maker.',
                'gate' => 'vendor',
            ],
            self::ASSESS => [
                'label' => 'Assess',
                'blurb' => 'Complete the site assessment.',
                'gate' => 'vendor',
            ],
            self::INTERVIEW => [
                'label' => 'Interview',
                'blurb' => 'Take part in the vendor interview.',
                'gate' => 'vendor',
            ],
            self::SOLUTION => [
                'label' => 'Solution',
                'blurb' => 'Present the operating solution.',
                'gate' => 'vendor',
            ],
            self::SELECTION => [
                'label' => 'Selection',
                'blurb' => 'Confirm the buyer’s selection.',
                'gate' => 'buyer',
            ],
            self::PRICE => [
                'label' => 'Price',
                'blurb' => 'Submit the sealed price.',
                'gate' => 'vendor',
            ],
            self::SUCCESS_FEE => [
                'label' => 'Success Fee',
                'blurb' => 'Accept the post-award success-fee agreement.',
                'gate' => 'vendor',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::stages());
    }

    public static function exists(string $stage): bool
    {
        return array_key_exists($stage, self::stages());
    }

    public static function label(string $stage): string
    {
        return self::stages()[$stage]['label'] ?? ucfirst($stage);
    }

    public static function position(string $stage): int
    {
        $index = array_search($stage, self::keys(), true);

        return $index === false ? -1 : $index + 1;
    }

    /** The stage after this one, or null at the end of the sequence. */
    public static function next(string $stage): ?string
    {
        $keys = self::keys();
        $index = array_search($stage, $keys, true);

        return $index === false ? null : ($keys[$index + 1] ?? null);
    }

    /** Every stage that must be complete before this one opens. @return array<int, string> */
    public static function prerequisites(string $stage): array
    {
        $keys = self::keys();
        $index = array_search($stage, $keys, true);

        return $index === false ? [] : array_slice($keys, 0, $index);
    }

    // ── Activity events ─────────────────────────────────────────────────────
    public const EVENT_INVITATION_USED = 'INVITATION_USED';

    public const EVENT_ENGAGEMENT_STARTED = 'ENGAGEMENT_STARTED';

    public const EVENT_STAGE_OPENED = 'STAGE_OPENED';

    public const EVENT_STAGE_COMPLETED = 'STAGE_COMPLETED';

    public const EVENT_OPPORTUNITY_ACCEPTED = 'OPPORTUNITY_ACCEPTED';

    public const EVENT_OPPORTUNITY_DECLINED = 'OPPORTUNITY_DECLINED';

    public const EVENT_ADJUSTMENT_REQUESTED = 'SCOPE_ADJUSTMENT_REQUESTED';

    public const EVENT_ACCESS_DENIED = 'ACCESS_DENIED';

    public const EVENT_INVITATION_REVOKED = 'INVITATION_REVOKED';

    /**
     * Decline reasons offered to a vendor. Free text is captured alongside.
     *
     * @return array<string, string>
     */
    public static function declineReasons(): array
    {
        return [
            'capacity' => 'No capacity for this coverage',
            'geography' => 'Outside our service area',
            'wage' => 'Baseline wage below what we can staff',
            'scope' => 'Scope does not fit our services',
            'timing' => 'Start date does not work',
            'credentials' => 'We do not hold the required credentials',
            'other' => 'Other',
        ];
    }
}
