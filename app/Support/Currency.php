<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Platform currency resolver + formatter.
 *
 * The active currency comes from the 'currency' admin Setting, falling back to
 * config('currency.default') (USD). Calculators enter values in the local
 * currency — there is no FX conversion; this only controls labelling/format.
 */
class Currency
{
    /**
     * Active currency code (e.g. USD, CAD). Defensive: never throws if the
     * settings table / DB is unavailable during rendering.
     */
    public static function code(): string
    {
        $default = (string) config('currency.default', 'USD');

        try {
            $code = (string) Setting::get('currency', $default);
        } catch (\Throwable $e) {
            $code = $default;
        }

        $profiles = (array) config('currency.profiles', []);

        if (isset($profiles[$code])) {
            return $code;
        }

        return isset($profiles[$default]) ? $default : (string) (array_key_first($profiles) ?: 'USD');
    }

    /**
     * @return array{code:string,label:string,symbol:string,locale:string}
     */
    public static function profile(): array
    {
        $code = self::code();

        return (array) config("currency.profiles.{$code}", config('currency.profiles.USD', [
            'code' => 'USD', 'label' => 'United States (USD)', 'symbol' => '$', 'locale' => 'en-US',
        ]));
    }

    public static function symbol(): string
    {
        return (string) (self::profile()['symbol'] ?? '$');
    }

    /**
     * Format an amount in the active currency, e.g. "$1,234.56".
     */
    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        return self::symbol() . number_format($value, $decimals);
    }

    /**
     * Config for browser-side formatters (Intl.NumberFormat).
     *
     * @return array{code:string,symbol:string,locale:string}
     */
    public static function jsConfig(): array
    {
        $p = self::profile();

        return [
            'code' => $p['code'] ?? 'USD',
            'symbol' => $p['symbol'] ?? '$',
            'locale' => $p['locale'] ?? 'en-US',
        ];
    }
}
