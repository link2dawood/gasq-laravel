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
     * Floating (local, no-zone) timestamp — the platform stores/shows times
     * naively, so the calendar entry must show the same wall-clock rather than
     * being shifted into the viewer's zone.
     */
    private static function stampLocal(Carbon $dt): string
    {
        return $dt->format('Ymd\THis');
    }

    public static function ics(Interview $interview): string
    {
        $uid = 'interview-' . $interview->id . '@getasecurityquotenow.com';
        $now = self::stampUtc(Carbon::now());
        $start = self::stampLocal(self::start($interview));
        $end = self::stampLocal(self::end($interview));
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
        $params = http_build_query([
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => self::title($interview),
            'startdt' => self::start($interview)->format('Y-m-d\TH:i:s'),
            'enddt' => self::end($interview)->format('Y-m-d\TH:i:s'),
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
