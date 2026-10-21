<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExchangeRateService
{
    public const KEY = 'exchange_rates_snapshot';

    public const FEED = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    public const SOURCE = 'https://www.ecb.europa.eu/stats/policy_and_exchange_rates/euro_reference_exchange_rates/html/index.en.html';

    private bool $checked = false;

    private ?array $snapshot = null;

    public function current(): array
    {
        if (! $this->checked) {
            $this->checked = true;
            if (config('currency.refresh_on_request')) {
                try {
                    if (Cache::add('exchange_rates.refresh_attempt', true, now()->addHour())) {
                        try {
                            $this->refresh();
                        } catch (\Throwable) {
                            Cache::put('exchange_rates.refresh_attempt', true, now()->addMinutes(5));
                        }
                    }
                } catch (\Throwable) {
                    // Cache/storage outages also retain the last verified reference sheet.
                }
            }
        }

        return $this->snapshot ??= $this->stored();
    }

    public function refresh(): array
    {
        $xml = Http::connectTimeout(4)->timeout(10)->withOptions(['allow_redirects' => false])
            ->get(self::FEED)->throw()->body();
        if (strlen($xml) > 100000 || stripos($xml, '<!DOCTYPE') !== false) {
            throw new RuntimeException('Invalid ECB reference sheet.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $sheets = $document ? $document->xpath('//*[local-name()="Cube" and @time]') : [];
        if (count($sheets) !== 1) {
            throw new RuntimeException('Missing ECB reference date.');
        }
        $date = (string) $sheets[0]['time'];
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) {
            throw new RuntimeException('Invalid ECB reference date.');
        }
        $day = Carbon::createFromFormat('!Y-m-d', $date, 'UTC');
        if ($day->format('Y-m-d') !== $date || $day->gt(now('UTC')) || $day->lt(now('UTC')->startOfDay()->subDays(10)) || $date < $this->stored()['asOf']) {
            throw new RuntimeException('The ECB reference date is stale or in the future.');
        }
        $eur = ['EUR' => 1.0];
        foreach ($sheets[0]->children() as $row) {
            $code = (string) $row['currency'];
            $value = (string) $row['rate'];
            if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value <= 0 || isset($eur[$code])) {
                throw new RuntimeException('Invalid or duplicate ECB currency rate.');
            }
            $eur[$code] = (float) $value;
        }
        if (! isset($eur['USD'])) {
            throw new RuntimeException('The ECB sheet has no USD cross rate.');
        }
        $rates = [];
        foreach (array_keys(config('currency.profiles')) as $code) {
            if (! isset($eur[$code])) {
                throw new RuntimeException('The ECB sheet is missing a supported currency.');
            }
            $rates[$code] = $code === 'USD' ? 1.0 : $eur[$code] / $eur['USD'];
        }
        $snapshot = ['base' => 'USD', 'asOf' => $date, 'source' => 'ECB', 'sourceUrl' => self::SOURCE,
            'checkedAt' => now('UTC')->toIso8601String(), 'rates' => $rates];
        DB::transaction(function () use ($snapshot) {
            $existing = Setting::where('key', self::KEY)->lockForUpdate()->first();
            $saved = json_decode((string) $existing?->value, true);
            if (($saved['asOf'] ?? '') > $snapshot['asOf']) {
                throw new RuntimeException('A newer ECB reference sheet is already stored.');
            }
            Setting::set(self::KEY, json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION), 'currency');
        }, 3);
        Cache::forget('setting.'.self::KEY);
        Cache::put('exchange_rates.refresh_attempt', true, now()->addHour());

        return $this->snapshot = $snapshot;
    }

    private function stored(): array
    {
        $fallback = config('currency.reference');
        try {
            $snapshot = json_decode((string) Setting::get(self::KEY, ''), true);
            if (is_array($snapshot) && ($snapshot['base'] ?? null) === 'USD'
                && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $snapshot['asOf'] ?? '') && $snapshot['asOf'] >= $fallback['asOf']
                && $snapshot['asOf'] <= now('UTC')->toDateString() && ($snapshot['source'] ?? '') === 'ECB'
                && ($snapshot['sourceUrl'] ?? '') === self::SOURCE && array_key_exists('checkedAt', $snapshot)) {
                foreach (array_keys(config('currency.profiles')) as $code) {
                    $rate = $snapshot['rates'][$code] ?? null;
                    if (! is_numeric($rate) || ! is_finite((float) $rate) || $rate <= 0 || ($code === 'USD' && (float) $rate !== 1.0)) {
                        return $fallback;
                    }
                }

                return $snapshot;
            }
        } catch (\Throwable) {
            // Rendering remains available without a database or network connection.
        }

        return $fallback;
    }
}
