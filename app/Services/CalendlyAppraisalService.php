<?php

namespace App\Services;

use App\Models\AppraisalAppointment;
use App\Models\AppraisalReview;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CalendlyAppraisalService
{
    public function resource(string $uri): array
    {
        abort_unless(preg_match('~^https://api\.calendly\.com/scheduled_events/[a-zA-Z0-9-]+(?:/invitees/[a-zA-Z0-9-]+)?$~D', $uri), 422);
        abort_unless(config('appraisal_reviews.calendly_token'), 503, 'Calendly access is not configured.');

        return Http::withToken(config('appraisal_reviews.calendly_token'))->acceptJson()->timeout(15)
            ->withOptions(['allow_redirects' => false])->get($uri)->throw()->json('resource');
    }

    public function ingest(string $inviteeUri, ?string $knownReviewId = null): bool
    {
        $invitee = $this->resource($inviteeUri);
        $event = $this->resource($invitee['event']);
        abort_unless(config('appraisal_reviews.event_type_uri'), 503, 'The appraisal event type is not configured.');
        if (($event['event_type'] ?? null) !== config('appraisal_reviews.event_type_uri')) {
            return false;
        }
        $reviewId = Str::after((string) data_get($invitee, 'tracking.utm_content', ''), 'gasq_appraisal_');
        $existing = AppraisalAppointment::where('invitee_uri', $inviteeUri)->first();
        abort_if($knownReviewId && $existing && $existing->appraisal_review_id !== $knownReviewId, 409, 'This booking belongs to another appraisal.');
        $old = isset($invitee['old_invitee']) ? AppraisalAppointment::where('invitee_uri', $invitee['old_invitee'])->first() : null;
        $reviewId = $existing?->appraisal_review_id ?? $knownReviewId ?? $old?->appraisal_review_id ?? $reviewId;
        if (! Str::isUuid($reviewId)) {
            return false;
        }

        return DB::transaction(function () use ($reviewId, $invitee, $event, $inviteeUri) {
            $review = AppraisalReview::whereKey($reviewId)->lockForUpdate()->first();
            if (! $review || strcasecmp($review->buyer_email, $invitee['email'] ?? '') !== 0) {
                return false;
            }
            $timezone = $invitee['timezone'] ?? '';
            abort_unless(in_array($timezone, timezone_identifiers_list(), true), 422, 'A valid invitee time zone is required.');
            foreach (['cancel_url', 'reschedule_url'] as $key) {
                abort_unless($this->providerUrl($invitee[$key] ?? ''), 422, 'A valid Calendly management link is required.');
            }
            $starts = Carbon::parse($event['start_time'])->utc();
            $ends = Carbon::parse($event['end_time'])->utc();
            abort_unless($ends->greaterThan($starts), 422);
            $created = Carbon::parse($invitee['created_at'])->utc();
            $canceled = ($invitee['status'] ?? '') === 'canceled' || ($event['status'] ?? '') === 'canceled';
            $appointment = AppraisalAppointment::firstOrNew(['invitee_uri' => $inviteeUri]);
            $previousStatus = $appointment->status;
            $newer = $review->appointments()->where('invitee_uri', '!=', $inviteeUri)
                ->where('source_created_at', '>=', $created)->exists();
            $status = $canceled ? 'canceled' : ($newer || in_array($previousStatus, ['canceled', 'superseded'], true) ? 'superseded' : 'scheduled');
            $details = data_get($event, 'location.join_url') ?: data_get($event, 'location.location');
            if (is_string($details) && filter_var($details, FILTER_VALIDATE_URL)) {
                abort_unless(parse_url($details, PHP_URL_SCHEME) === 'https', 422);
            }
            $changed = $appointment->exists && (! $appointment->starts_at->eq($starts) || ! $appointment->ends_at->eq($ends) || $appointment->meeting_details !== ($details ?: ''));
            if ($changed) {
                $appointment->confirmed_at = null;
            }
            $appointment->fill([
                'appraisal_review_id' => $review->id, 'event_uri' => $invitee['event'], 'status' => $status,
                'starts_at' => $starts, 'ends_at' => $ends, 'timezone' => $timezone,
                'meeting_details' => $details ?: '', 'cancel_url' => $invitee['cancel_url'],
                'reschedule_url' => $invitee['reschedule_url'], 'source_created_at' => $created,
            ])->save();
            $service = app(AppraisalReviewService::class);
            if ($status === 'scheduled') {
                $review->appointments()->whereKeyNot($appointment->id)->where('status', 'scheduled')->update(['status' => 'superseded']);
                if ($changed || ($previousStatus === null && $review->delivered_at)) {
                    $service->queueMessage($appointment, 'rescheduled');
                } elseif ($previousStatus === null && ! $review->ready_at) {
                    // Calendly's normal booking/calendar confirmation also remains enabled.
                    $service->queueMessage($appointment, 'booking');
                }
                $service->queueReady($review);
            } elseif ($status === 'canceled' && $previousStatus !== 'canceled' && ! ($invitee['rescheduled'] ?? false)) {
                $service->queueMessage($appointment, 'canceled');
            }

            return true;
        });
    }

    private function providerUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https'
            && parse_url($url, PHP_URL_HOST) === 'calendly.com';
    }
}
