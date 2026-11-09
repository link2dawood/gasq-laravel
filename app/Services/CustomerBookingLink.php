<?php

namespace App\Services;

class CustomerBookingLink
{
    /** Choose the preferred usable public booking URL, then a configured fallback. */
    public function configured(): ?array
    {
        foreach (array_unique([config('appraisal_reviews.provider'), 'bookafy', 'calendly']) as $provider) {
            if (! in_array($provider, ['bookafy', 'calendly'], true)) {
                continue;
            }
            $url = $provider === 'bookafy' ? config('appraisal_reviews.bookafy.booking_url') : config('appraisal_reviews.booking_url');
            if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null || parse_url($url, PHP_URL_FRAGMENT)) {
                continue;
            }
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $domain = $provider.'.com';
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return ['provider' => $provider, 'url' => $url];
            }
        }

        return null;
    }

    public function url(): ?string
    {
        return $this->configured()['url'] ?? null;
    }
}
