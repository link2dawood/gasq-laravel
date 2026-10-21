<?php

namespace App\Support;

use App\Models\Setting;
use App\Services\ExchangeRateService;

/**
 * Platform currency resolver + formatter.
 *
 * The active currency comes from the 'currency' admin Setting, falling back to
 * config('currency.default') (USD). Calculator storage uses USD. Monetary
 * presentation uses dated ECB cross rates; quantities and ratios stay unchanged.
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

        // 1) Per-user preference wins among the supported currency profiles.
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
    public static function profile(?string $code = null): array
    {
        $code ??= self::code();

        return (array) config("currency.profiles.{$code}", config('currency.profiles.USD', [
            'code' => 'USD', 'label' => 'United States (USD)', 'symbol' => '$', 'locale' => 'en-US',
        ]));
    }

    public static function symbol(?string $code = null): string
    {
        return (string) (self::profile($code)['symbol'] ?? '$');
    }

    /**
     * Exchange rate applied to the USD labor model (USD × rate = local amount).
     * One verified snapshot supplies every cross rate and its publication date.
     */
    public static function rate(?string $code = null): float
    {
        $code = self::profile($code)['code'];

        return (float) app(ExchangeRateService::class)->current()['rates'][$code];
    }

    /**
     * Format a USD amount in the active currency, applying the exchange rate.
     * Presentation rounds after applying the dated rate, without changing the model.
     */
    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        return self::symbol().number_format(round($value * self::rate(), 10), $decimals);
    }

    /**
     * Config for browser-side formatters (Intl.NumberFormat + rate multiplier).
     *
     * @return array{code:string,symbol:string,locale:string,rate:float,asOf:string,source:string,sourceUrl:string,checkedAt:?string,stale:bool}
     */
    public static function jsConfig(?string $code = null): array
    {
        $p = self::profile($code);
        $sheet = app(ExchangeRateService::class)->current();

        return [
            'code' => $p['code'] ?? 'USD',
            'symbol' => $p['symbol'] ?? '$',
            'locale' => $p['locale'] ?? 'en-US',
            'rate' => (float) $sheet['rates'][$p['code']],
            'asOf' => $sheet['asOf'],
            'source' => $sheet['source'],
            'sourceUrl' => $sheet['sourceUrl'],
            'checkedAt' => $sheet['checkedAt'],
            'stale' => $sheet['asOf'] < now('UTC')->subDays(4)->toDateString(),
        ];
    }
}
