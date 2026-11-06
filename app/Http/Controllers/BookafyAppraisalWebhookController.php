<?php

namespace App\Http\Controllers;

use App\Services\BookafyAppraisalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bookafy signs no callback, so the secret lives in the URL and the body is
 * only ever read for an appointment id: the booking itself is re-read from the
 * API before anything is written.
 */
class BookafyAppraisalWebhookController extends Controller
{
    public function __invoke(Request $request, string $token, BookafyAppraisalService $bookafy): JsonResponse
    {
        $secret = config('appraisal_reviews.bookafy.webhook_token');
        abort_unless(is_string($secret) && strlen($secret) >= 32, 503);
        abort_unless(hash_equals($secret, $token), 404);
        $id = (string) ($request->input('appointment.id') ?? $request->input('id') ?? $request->input('response.appointment.id') ?? '');
        abort_unless(preg_match('/^[0-9]{1,18}$/D', $id), 422);

        return response()->json(['ok' => true, 'processed' => $bookafy->ingest(BookafyAppraisalService::reference($id))]);
    }
}
