<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HubSpot CRM client for outbound contact sync (Layer 1).
 *
 * Every method is a no-op until HUBSPOT_API_TOKEN is set, so this can ship
 * dormant and activate the moment the Private App token is added to .env.
 */
class HubSpotService
{
    private const BASE_URL = 'https://api.hubapi.com';

    public function enabled(): bool
    {
        return filled(config('services.hubspot.token'));
    }

    /**
     * Create or update a contact keyed by email. Returns the HubSpot contact id,
     * or null when disabled / on failure.
     *
     * @param  array<string, mixed>  $standardProperties  HubSpot built-ins (firstname, lastname, company, phone…)
     * @param  array<string, mixed>  $customProperties     gasq_* custom properties
     */
    public function upsertContactByEmail(string $email, array $standardProperties = [], array $customProperties = []): ?string
    {
        if (! $this->enabled() || trim($email) === '') {
            return null;
        }

        $standard = array_merge(['email' => $email], $this->clean($standardProperties));
        $custom = config('services.hubspot.custom_properties') ? $this->clean($customProperties) : [];

        $contactId = $this->findContactIdByEmail($email);

        // Try with custom properties first; if HubSpot 400s (most often an unknown
        // gasq_* property that hasn't been created yet), retry identity-only so the
        // contact still syncs and we log a clear "create these properties" hint.
        $attempts = $custom === [] ? [$standard] : [array_merge($standard, $custom), $standard];
        $lastKey = array_key_last($attempts);

        foreach ($attempts as $i => $properties) {
            $response = $contactId
                ? $this->request('patch', "/crm/v3/objects/contacts/{$contactId}", ['properties' => $properties])
                : $this->request('post', '/crm/v3/objects/contacts', ['properties' => $properties]);

            if ($response !== null && $response->successful()) {
                return $contactId ?? $response->json('id');
            }

            if ($response !== null && $response->status() === 400 && $i !== $lastKey) {
                Log::warning('HubSpot sync: retrying identity-only — a gasq_* custom property is likely missing. Create the custom contact properties in HubSpot to sync role/revenue.', [
                    'email' => $email,
                ]);

                continue;
            }

            $this->logFailure($email, $response);
            break;
        }

        return null;
    }

    /**
     * Fetch selected properties for a contact (used by the inbound webhook sync).
     *
     * @param  array<int, string>  $properties
     * @return array<string, mixed>|null  property => value, or null when disabled / not found
     */
    public function getContact(string $contactId, array $properties): ?array
    {
        if (! $this->enabled() || trim($contactId) === '') {
            return null;
        }

        $response = $this->request('get', '/crm/v3/objects/contacts/' . rawurlencode($contactId), [
            'properties' => implode(',', $properties),
        ]);

        if ($response !== null && $response->successful()) {
            return $response->json('properties');
        }

        return null;
    }

    private function findContactIdByEmail(string $email): ?string
    {
        $response = $this->request('post', '/crm/v3/objects/contacts/search', [
            'filterGroups' => [[
                'filters' => [[
                    'propertyName' => 'email',
                    'operator' => 'EQ',
                    'value' => $email,
                ]],
            ]],
            'properties' => ['email'],
            'limit' => 1,
        ]);

        if ($response !== null && $response->successful()) {
            return $response->json('results.0.id');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function request(string $method, string $path, array $body): ?Response
    {
        try {
            return Http::withToken(config('services.hubspot.token'))
                ->baseUrl(self::BASE_URL)
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->{$method}($path, $body);
        } catch (\Throwable $e) {
            Log::error('HubSpot API request failed: ' . $e->getMessage(), ['path' => $path]);

            return null;
        }
    }

    /**
     * Drop null/blank values and stringify booleans for the HubSpot API.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, scalar>
     */
    private function clean(array $properties): array
    {
        $out = [];
        foreach ($properties as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $out[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }

        return $out;
    }

    private function logFailure(string $email, ?Response $response): void
    {
        Log::error('HubSpot contact sync failed.', [
            'email' => $email,
            'status' => $response?->status(),
            'body' => $response?->json() ?? $response?->body(),
        ]);
    }
}
