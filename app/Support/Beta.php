<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Beta enrolment limits: how many accounts the beta admits, and until when.
 *
 * This is how the beta bounds its exposure — by capping who gets in and for how
 * long, rather than by gating the public it is paying to attract. Both limits
 * also give outreach a deadline to point at.
 */
class Beta
{
    /** Registration is open unless a configured limit says otherwise. */
    public static function registrationOpen(): bool
    {
        return self::closedReason() === null;
    }

    /**
     * Why registration is closed, or null when it is open. The string is shown
     * to the user, so it explains rather than just refuses.
     */
    public static function closedReason(): ?string
    {
        if (self::hasClosingDate() && self::closingDate()->isPast()) {
            return 'The GASQ beta closed on ' . self::closingDate()->format('j F Y') . '. Contact us and we will let you know when general access opens.';
        }

        if (self::isFull()) {
            return 'The GASQ beta is full — every place has been taken. Contact us to join the waiting list.';
        }

        return null;
    }

    /** Non-admin accounts currently held against the cap. */
    public static function accountsUsed(): int
    {
        return (int) User::query()->where('user_type', '!=', 'admin')->count();
    }

    public static function hasCap(): bool
    {
        return self::cap() > 0;
    }

    public static function cap(): int
    {
        return max(0, (int) config('beta.max_accounts', 0));
    }

    /**
     * Places left, or null when uncapped.
     *
     * Note this is a soft cap: two people submitting at the same instant could
     * take the last place twice. That is acceptable for an enrolment limit —
     * guarding it with a lock would cost more than being one over is worth.
     */
    public static function spotsRemaining(): ?int
    {
        if (! self::hasCap()) {
            return null;
        }

        return max(0, self::cap() - self::accountsUsed());
    }

    public static function isFull(): bool
    {
        return self::hasCap() && self::spotsRemaining() === 0;
    }

    public static function hasClosingDate(): bool
    {
        return self::closingDate() !== null;
    }

    /** Configured closing date, or null when unset or unparseable. */
    public static function closingDate(): ?CarbonInterface
    {
        $raw = config('beta.closes_at');

        if (! $raw) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            // A malformed date must not close registration by accident.
            return null;
        }
    }
}
