<?php

namespace App\Services;

use App\Models\Setting;

/** Optional internal copies for explicitly selected business processes, never global mail. */
class ProcessEmailCopies
{
    public const ADDRESS_KEY = 'process_email_bcc_address';

    public const DEFAULT_ADDRESS = 'info@getasecurityquote.com';

    public const PROCESSES = [
        'vendor_weighted' => ['key' => 'process_email_bcc_vendor_weighted', 'label' => 'Vendor-weighted report emails'],
        'estimates' => ['key' => 'process_email_bcc_estimates', 'label' => 'Estimate delivery emails'],
    ];

    public function settings(): array
    {
        return [
            'address' => (string) Setting::get(self::ADDRESS_KEY, self::DEFAULT_ADDRESS),
            'enabled' => collect(self::PROCESSES)->mapWithKeys(fn ($process, $name) => [$name => Setting::get($process['key'], '1') === '1'])->all(),
        ];
    }

    public static function processForReport(string $type): ?string
    {
        return match ($type) {
            'vendor-weighted-rates' => 'vendor_weighted',
            'budget-calculator', 'budget-calculator-preview', 'budget-calculator-allocation', 'buyer-appraisal' => 'estimates',
            default => null,
        };
    }

    /** @param array<int, string> $primaryRecipients */
    public function recipients(?string $process, array $primaryRecipients = [], array $existing = []): array
    {
        $addresses = $existing;
        if (isset(self::PROCESSES[$process ?? ''])) {
            $settings = $this->settings();
            if ($settings['enabled'][$process]) {
                $addresses[] = $settings['address'];
            }
        }
        $primary = array_map(fn ($address) => strtolower(trim($address)), $primaryRecipients);

        return collect($addresses)->filter(fn ($address) => is_string($address))
            ->map(fn ($address) => trim($address))
            ->filter(fn ($address) => filter_var($address, FILTER_VALIDATE_EMAIL) && ! in_array(strtolower($address), $primary, true))
            ->unique(fn ($address) => strtolower($address))->values()->all();
    }
}
