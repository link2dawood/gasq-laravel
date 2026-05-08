<?php

namespace App\Support;

/**
 * Shared formatters for buyer-redacted invite emails and admin alerts.
 */
class LeadFormatting
{
    /** "Livi Smith" -> "Livi *****" */
    public static function redactName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '*****';
        }
        $parts = preg_split('/\s+/', $name) ?: [$name];
        $first = array_shift($parts);
        return $first . ' *****';
    }

    /** "graham@globe.com" -> "g***m@gl.com" */
    public static function redactEmail(?string $email): string
    {
        $email = trim((string) $email);
        if ($email === '' || ! str_contains($email, '@')) {
            return '*****';
        }
        [$local, $domain] = explode('@', $email, 2);
        $localMasked = strlen($local) <= 2
            ? substr($local, 0, 1) . '*'
            : substr($local, 0, 1) . str_repeat('*', max(strlen($local) - 2, 1)) . substr($local, -1);

        $domainParts = explode('.', $domain);
        $tld = array_pop($domainParts);
        $rootMasked = implode('.', array_map(static fn ($p) => substr($p, 0, 2), $domainParts));

        return $localMasked . '@' . $rootMasked . '.' . $tld;
    }

    /** "(404) 555-1212" -> "(404) -*" */
    public static function redactPhone(?string $phone): string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return '*****';
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) >= 10) {
            $area = substr($digits, -10, 3);
            return "({$area}) -*";
        }
        return '*****';
    }

    /** 195350.40 -> "$195K" (subject), or "$195,350.40" (body) */
    public static function moneyShort(float|int|string|null $value): string
    {
        $n = is_numeric($value) ? (float) $value : 0.0;
        if ($n <= 0) return '$0';
        if ($n >= 1_000_000) return '$' . round($n / 1_000_000, 1) . 'M';
        if ($n >= 1_000) return '$' . (int) round($n / 1_000) . 'K';
        return '$' . number_format($n, 0);
    }

    public static function moneyFull(float|int|string|null $value): string
    {
        return is_numeric($value) ? '$' . number_format((float) $value, 2) : 'Not provided';
    }

    /** "City, ST 12345" pulled from a job location string. */
    public static function locationShort(?string $location): string
    {
        $location = trim((string) $location);
        return $location === '' ? 'Location TBD' : $location;
    }
}
