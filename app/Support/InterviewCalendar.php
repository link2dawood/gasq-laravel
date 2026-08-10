<?php

namespace App\Support;

use App\Models\Interview;
use Illuminate\Support\Carbon;

/**
 * Builds calendar artifacts for a scheduled interview — a downloadable .ics
 * file and "add to calendar" URLs for Google and Outlook. All are URL/text
 * based, so no external API auth is required.
 */
class InterviewCalendar
{
    public static function title(Interview $interview): string
    {
        $job = $interview->jobPosting;

        return 'GASQ Interview — ' . ($job?->title ?? 'Security Services');
    }

    public static function description(Interview $interview): string
    {
        $parts = ['GASQ vendor interview.'];
        if ($interview->format) {
            $parts[] = 'Format: ' . str_replace('_', ' ', $interview->format) . '.';
        }
        if ($interview->location) {
            $parts[] = 'Location/link: ' . $interview->location;
        }

        return implode(' ', $parts);
    }

    private static function start(Interview $interview): Carbon
    {
        return $interview->scheduled_at->copy();
    }

    private static function end(Interview $interview): Carbon
    {
        return $interview->scheduled_at->copy()->addMinutes((int) ($interview->duration_minutes ?: 30));
    }

    /** UTC timestamp in iCalendar basic format (e.g. 20260731T140000Z). */
    private static function stampUtc(Carbon $dt): string
    {
        return $dt->copy()->utc()->format('Ymd\THis\Z');
    }

    /**
     * Floating (local, no-zone) timestamp — used only when the interview has no
     * timezone, so the event floats to the viewer's zone.
     */
    private static function stampLocal(Carbon $dt): string
    {
        return $dt->format('Ymd\THis');
    }

    /**
     * Reinterpret the stored naive wall-clock (e.g. "2026-08-15 14:00") as being
     * in the interview's timezone. Returns null when no timezone is set.
     */
    private static function inZone(Interview $interview, Carbon $dt): ?Carbon
    {
        if (! $interview->timezone) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $dt->format('Y-m-d H:i:s'), $interview->timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * DTSTART/DTEND stamps for the .ics. With a timezone we anchor the stored
     * wall-clock to that zone and emit UTC (…Z) so the event lands at the right
     * absolute moment for a viewer in ANY zone; without one we emit a floating
     * (viewer-local) time, matching how the platform shows times.
     *
     * @return array{0: string, 1: string}
     */
    private static function eventStamps(Interview $interview): array
    {
        $start = self::start($interview);
        $end = self::end($interview);
        $zStart = self::inZone($interview, $start);
        $zEnd = self::inZone($interview, $end);

        if ($zStart && $zEnd) {
            return [self::stampUtc($zStart), self::stampUtc($zEnd)];
        }

        return [self::stampLocal($start), self::stampLocal($end)];
    }

    public static function ics(Interview $interview): string
    {
        $uid = 'interview-' . $interview->id . '@getasecurityquotenow.com';
        $now = self::stampUtc(Carbon::now());
        [$start, $end] = self::eventStamps($interview);
        $summary = self::escape(self::title($interview));
        $desc = self::escape(self::description($interview));
        $loc = self::escape((string) ($interview->location ?? ''));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//GASQ//Interview Scheduler//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $now,
            'DTSTART:' . $start,
            'DTEND:' . $end,
            'SUMMARY:' . $summary,
            'DESCRIPTION:' . $desc,
            'LOCATION:' . $loc,
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }

    public static function googleUrl(Interview $interview): string
    {
        $query = [
            'action' => 'TEMPLATE',
            'text' => self::title($interview),
            'dates' => self::stampLocal(self::start($interview)) . '/' . self::stampLocal(self::end($interview)),
            'details' => self::description($interview),
            'location' => (string) ($interview->location ?? ''),
        ];
        // If the interview carries a zone, tell Google to read the floating
        // times in that zone; otherwise they float to the viewer's zone.
        if ($interview->timezone) {
            $query['ctz'] = $interview->timezone;
        }

        return 'https://calendar.google.com/calendar/render?' . http_build_query($query);
    }

    public static function outlookUrl(Interview $interview): string
    {
        // With a zone, send an offset-aware timestamp (e.g. 2026-08-15T14:00:00-04:00)
        // so Outlook places it correctly regardless of the account's own zone.
        $startDt = self::inZone($interview, self::start($interview)) ?? self::start($interview);
        $endDt = self::inZone($interview, self::end($interview)) ?? self::end($interview);
        $format = $interview->timezone ? 'Y-m-d\TH:i:sP' : 'Y-m-d\TH:i:s';

        $params = http_build_query([
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => self::title($interview),
            'startdt' => $startDt->format($format),
            'enddt' => $endDt->format($format),
            'body' => self::description($interview),
            'location' => (string) ($interview->location ?? ''),
        ]);

        return 'https://outlook.live.com/calendar/0/deeplink/compose?' . $params;
    }

    private static function escape(string $text): string
    {
        return str_replace(["\\", "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\\;'], $text);
    }
}
