<?php

namespace App\Http\Controllers;

use App\Jobs\ApplyHubSpotContactChange;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Inbound HubSpot webhook (Layer 2). Verifies HubSpot's v3 request signature,
 * then queues an ApplyHubSpotContactChange job per affected contact and returns
 * 200 immediately (HubSpot expects a fast response and retries otherwise).
 *
 * Dormant until HUBSPOT_CLIENT_SECRET is set — without it, every request is
 * rejected as unsigned, so the endpoint is safe to ship before it's configured.
 */
class HubSpotWebhookController extends Controller
{
    /** Contact properties we accept inbound. Email/role/credits are site-owned. */
    private const WATCHED_PROPERTIES = ['firstname', 'lastname', 'phone', 'company'];

    public function handle(Request $request): Response
    {
        if (! $this->verifySignature($request)) {
            Log::warning('HubSpot webhook rejected: invalid or missing signature.');

            return response('Invalid signature', 401);
        }

        $events = $request->json()->all();
        if (! is_array($events)) {
            return response('OK', 200);
        }

        $contactIds = [];
        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }

            $type = (string) ($event['subscriptionType'] ?? '');
            $objectId = $event['objectId'] ?? null;

            if ($objectId === null || ! str_starts_with($type, 'contact.')) {
                continue;
            }

            // Skip changes our own integration made — avoids redundant echo writes.
            if (($event['changeSource'] ?? '') === 'INTEGRATION') {
                continue;
            }

            // For property changes, only react to fields we actually mirror.
            if ($type === 'contact.propertyChange'
                && ! in_array($event['propertyName'] ?? '', self::WATCHED_PROPERTIES, true)) {
                continue;
            }

            $contactIds[(string) $objectId] = true;
        }

        foreach (array_keys($contactIds) as $contactId) {
            ApplyHubSpotContactChange::dispatch($contactId);
        }

        return response('OK', 200);
    }

    /**
     * Validate HubSpot's X-HubSpot-Signature-v3 header (HMAC-SHA256 of
     * method + full URL + raw body + timestamp, keyed by the app client secret).
     */
    private function verifySignature(Request $request): bool
    {
        $secret = config('services.hubspot.client_secret');
        $signature = $request->header('X-HubSpot-Signature-v3');
        $timestamp = $request->header('X-HubSpot-Request-Timestamp');

        if (! $secret || ! $signature || ! $timestamp) {
            return false;
        }

        // Replay protection: reject anything older than 5 minutes.
        if (abs(now()->valueOf() - (int) $timestamp) > 5 * 60 * 1000) {
            return false;
        }

        // Build the URL from APP_URL so a TLS-terminating proxy can't skew the
        // scheme/host away from what HubSpot signed.
        $uri = rtrim((string) config('app.url'), '/') . $request->getRequestUri();
        $base = $request->method() . $uri . $request->getContent() . $timestamp;
        $expected = base64_encode(hash_hmac('sha256', $base, (string) $secret, true));

        return hash_equals($expected, (string) $signature);
    }
}
