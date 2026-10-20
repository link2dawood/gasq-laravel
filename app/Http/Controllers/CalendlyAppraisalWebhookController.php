<?php

namespace App\Http\Controllers;

use App\Services\CalendlyAppraisalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendlyAppraisalWebhookController extends Controller
{
    public function __invoke(Request $request, CalendlyAppraisalService $calendly): JsonResponse
    {
        $secret = config('appraisal_reviews.webhook_signing_key');
        abort_unless(is_string($secret) && $secret !== '', 503);
        $header = (string) $request->header('Calendly-Webhook-Signature');
        preg_match('/(?:^|,)t=(\d+)(?:,|$)/', $header, $timestamp);
        preg_match_all('/(?:^|,)v1=([a-f0-9]{64})(?=,|$)/', $header, $signatures);
        abort_unless(isset($timestamp[1]) && abs(now()->timestamp - (int) $timestamp[1]) <= 180, 401);
        $expected = hash_hmac('sha256', $timestamp[1].'.'.$request->getContent(), $secret);
        abort_unless(collect($signatures[1])->contains(fn ($signature) => hash_equals($expected, $signature)), 401);
        $data = $request->validate(['event' => 'required|string', 'payload.uri' => 'required|string|max:255']);
        if (! in_array($data['event'], ['invitee.created', 'invitee.canceled'], true)) {
            return response()->json(['ok' => true, 'processed' => false]);
        }

        return response()->json(['ok' => true, 'processed' => $calendly->ingest($data['payload']['uri'])]);
    }
}
