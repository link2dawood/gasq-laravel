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
        $profiles = (array) config('currency.profiles', []);

        // 1) Per-user preference wins (each user picks USD or CAD).
        try {
            $userCode = auth()->check() ? (string) (auth()->user()->currency ?? '') : '';
            if ($userCode !== '' && isset($profiles[$userCode])) {
                return $userCode;
            }
        } catch (\Throwable $e) {
            // fall through to the platform default
        }

        // 2) Platform default (admin Setting), else config default.
        try {
            $code = (string) Setting::get('currency', $default);
        } catch (\Throwable $e) {
            $code = $default;
        }

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
     * Exchange rate applied to the USD labor model (USD × rate = local amount).
     * An admin Setting ("exchange_rate_cad") overrides the config value so the
     * rate can be updated without a deploy.
     */
    public static function rate(): float
    {
        $profile = self::profile();
        $rate = (float) ($profile['rate'] ?? 1.0);

        try {
            $override = Setting::get('exchange_rate_' . strtolower((string) ($profile['code'] ?? 'usd')));
            if (is_numeric($override) && (float) $override > 0) {
                $rate = (float) $override;
            }
        } catch (\Throwable $e) {
            // keep the config rate
        }

        return $rate > 0 ? $rate : 1.0;
    }

    /**
     * Format a USD amount in the active currency, applying the exchange rate.
     * e.g. USD 18 → "$18.00" (US) or "CA$25.38" (Canada at 1.41).
     */
    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        return self::symbol() . number_format($value * self::rate(), $decimals);
    }

    /**
     * Config for browser-side formatters (Intl.NumberFormat + rate multiplier).
     *
     * @return array{code:string,symbol:string,locale:string,rate:float}
     */
    public static function jsConfig(): array
    {
        $p = self::profile();

        return [
            'code' => $p['code'] ?? 'USD',
            'symbol' => $p['symbol'] ?? '$',
            'locale' => $p['locale'] ?? 'en-US',
            'rate' => self::rate(),
        ];
    }
}
