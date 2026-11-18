<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The clock reports are printed against.
 *
 * The application runs on UTC and stores UTC, which is correct for an audit
 * trail but wrong on a document a buyer reads: a report generated at 3:04 PM in
 * New York printed "8:04 PM". Every date and time on a report goes through here
 * so it reads in the report timezone instead.
 *
 * Nothing here writes to the database. Use it for presentation only.
 */
class ReportClock
{
    public static function timezone(): string
    {
        return (string) config('reports.timezone', 'America/New_York');
    }

    /** Now, in the report timezone. */
    public static function now(): Carbon
    {
        return Carbon::now()->setTimezone(self::timezone());
    }

    /** Any instant, moved into the report timezone. */
    public static function at(CarbonInterface|\DateTimeInterface|string|null $when): ?Carbon
    {
        if ($when === null) {
            return null;
        }

        return Carbon::parse($when)->setTimezone(self::timezone());
    }

    /**
     * A date and time with its zone named, e.g. "Nov 18, 2026 3:04 PM EST".
     * Readers cannot tell which zone a bare time belongs to, and on a document
     * that sets a validity window that ambiguity matters.
     */
    public static function stamp(CarbonInterface|\DateTimeInterface|string|null $when = null, string $format = 'M j, Y g:i A'): string
    {
        $moment = $when === null ? self::now() : self::at($when);

        return $moment->format($format).' '.$moment->format('T');
    }
}
