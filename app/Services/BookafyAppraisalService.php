<?php

namespace App\Services;

use App\Contracts\AppraisalScheduler;
use App\Models\AppraisalAppointment;
use App\Models\AppraisalReview;
use App\Support\RailsTimeZones;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Bookafy counterpart of CalendlyAppraisalService. Bookafy has no endpoint for
 * a single appointment and signs no webhook, so every booking is re-read from
 * the staff listing and nothing in a callback body is trusted.
 */
class BookafyAppraisalService implements AppraisalScheduler
{
    private const BASE = 'https://app.bookafy.com/api/v3/';

    private const PER_PAGE = 100;

    private const MAX_PAGES = 5;

    /** @var array<string, array<string, mixed>> */
    private array $listed = [];

    private bool $loaded = false;

    private bool $syncing = false;

    public static function reference(int|string $id): string
    {
        return 'bookafy:appointment:'.$id;
    }

    public static function appointmentId(string $reference): ?string
    {
        return preg_match('/^bookafy:appointment:([0-9]{1,18})$/D', $reference, $matches) ? $matches[1] : null;
    }

    /** Every appointment on the appraisal staff calendar from yesterday onwards. */
    public function listUpcoming(bool $refresh = false): array
    {
        if ($this->loaded && ! $refresh) {
            return $this->listed;
        }
        $key = config('appraisal_reviews.bookafy.api_key');
        $staff = config('appraisal_reviews.bookafy.staff_email');
        abort_unless($key && $staff, 503, 'Bookafy access is not configured.');
        $this->listed = [];
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = Http::withHeaders(['api-key' => $key])->acceptJson()->timeout(15)
                ->withOptions(['allow_redirects' => false])
                ->get(self::BASE.'appointments', [
                    'email' => $staff,
                    'from_date' => now()->subDay()->utc()->format('Y-m-d\TH:i:s'),
                    'page' => $page,
                    'per_page' => self::PER_PAGE,
                ])->throw();
            $batch = $response->json('response.appointment') ?: [];
            foreach ($batch as $appointment) {
                if (isset($appointment['id'])) {
                    $this->listed[(string) $appointment['id']] = $appointment;
                }
            }
            if (count($batch) < self::PER_PAGE) {
                break;
            }
        }
        $this->loaded = true;

        return $this->listed;
    }

    public function lookup(string $id, bool $refresh = false): ?array
    {
        return $this->listUpcoming($refresh)[$id] ?? null;
    }

    /**
     * Read one Bookafy booking into the appraisal flow. A booking the listing no
     * longer carries is treated as cancelled, which is how Bookafy reports a
     * deleted appointment.
     */
    public function ingest(string $reference, ?string $knownReviewId = null): bool
    {
        $id = self::appointmentId($reference);
        if (! $id) {
            return false;
        }
        // Outside a sync pass the cached listing may be stale, so re-read it.
        $booking = $this->lookup($id, ! $this->syncing);
        $existing = AppraisalAppointment::where('invitee_uri', $reference)->first();
        if (! $booking) {
            return $existing ? $this->cancelMissing($existing) : false;
        }
        $serviceId = config('appraisal_reviews.bookafy.service_id');
        abort_unless($serviceId, 503, 'The appraisal service is not configured.');
        if ((string) ($booking['service_id'] ?? '') !== (string) $serviceId) {
            return false;
        }
        $email = (string) data_get($booking, 'appointment_customer_info.email', '');
        abort_if($knownReviewId && $existing && $existing->appraisal_review_id !== $knownReviewId, 409, 'This booking belongs to another appraisal.');
        $reviewId = $existing?->appraisal_review_id ?? $knownReviewId ?? $this->matchReview($booking, $email);
        if (! Str::isUuid((string) $reviewId)) {
            return false;
        }

        return DB::transaction(function () use ($reviewId, $booking, $reference, $email) {
            $review = AppraisalReview::whereKey($reviewId)->lockForUpdate()->first();
            if (! $review || strcasecmp($review->buyer_email, $email) !== 0) {
                return false;
            }
            $timezone = RailsTimeZones::iana((string) ($booking['time_zone'] ?? ''));
            abort_unless($timezone, 422, 'A valid booking time zone is required.');
            $cancel = $this->managementUrl('cancel_url', $booking);
            $reschedule = $this->managementUrl('reschedule_url', $booking);
            $starts = Carbon::parse($booking['appointment_start_time'])->utc();
            $ends = Carbon::parse($booking['appointment_end_time'])->utc();
            abort_unless($ends->greaterThan($starts), 422);
            $created = Carbon::parse($booking['created_at'])->utc();
            $cancelled = ($booking['is_active'] ?? true) === false || ! empty($booking['deleted_at']);
            $appointment = AppraisalAppointment::firstOrNew(['invitee_uri' => $reference]);
            $previousStatus = $appointment->status;
            $newer = $review->appointments()->where('invitee_uri', '!=', $reference)
                ->where('source_created_at', '>=', $created)->exists();
            $status = $cancelled ? 'canceled' : ($newer || in_array($previousStatus, ['canceled', 'superseded'], true) ? 'superseded' : 'scheduled');
            $details = $this->meetingDetails($booking);
            $changed = $appointment->exists && (! $appointment->starts_at->eq($starts) || ! $appointment->ends_at->eq($ends) || $appointment->meeting_details !== $details);
            if ($changed) {
                $appointment->confirmed_at = null;
            }
            $appointment->fill([
                'appraisal_review_id' => $review->id, 'event_uri' => self::BASE.'appointments/'.$booking['id'], 'status' => $status,
                'starts_at' => $starts, 'ends_at' => $ends, 'timezone' => $timezone,
                'meeting_details' => $details, 'cancel_url' => $cancel, 'reschedule_url' => $reschedule,
                'source_created_at' => $created,
            ])->save();
            $service = app(AppraisalReviewService::class);
            if ($status === 'scheduled') {
                $review->appointments()->whereKeyNot($appointment->id)->where('status', 'scheduled')->update(['status' => 'superseded']);
                if ($changed || ($previousStatus === null && $review->delivered_at)) {
                    $service->queueMessage($appointment, 'rescheduled');
                } elseif ($previousStatus === null && ! $review->ready_at) {
                    // Bookafy's own booking confirmation also remains enabled.
                    $service->queueMessage($appointment, 'booking');
                }
                $service->queueReady($review);
            } elseif ($status === 'canceled' && $previousStatus !== 'canceled') {
                $service->queueMessage($appointment, 'canceled');
            }

            return true;
        });
    }

    /** Refresh every appointment the appraisal flow still expects to happen. */
    public function syncUpcoming(): int
    {
        $this->listUpcoming(true);
        $this->syncing = true;
        $synced = 0;

        try {
            AppraisalAppointment::where('status', 'scheduled')->where('ends_at', '>', now())
                ->where('invitee_uri', 'like', 'bookafy:appointment:%')->limit(100)
                ->each(function ($appointment) use (&$synced) {
                    $synced += $this->ingest($appointment->invitee_uri) ? 1 : 0;
                });
        } finally {
            $this->syncing = false;
        }

        return $synced;
    }

    /**
     * A booking Bookafy has dropped is a cancellation, not a missing record,
     * but only inside the window the listing covers: anything already over is
     * absent because it is old, not because it was deleted.
     */
    private function cancelMissing(AppraisalAppointment $appointment): bool
    {
        if ($appointment->status === 'canceled' || $appointment->ends_at->lte(now()->subDay())) {
            return false;
        }

        return (bool) DB::transaction(function () use ($appointment) {
            $appointment = AppraisalAppointment::whereKey($appointment->id)->lockForUpdate()->first();
            if (! $appointment || $appointment->status === 'canceled') {
                return false;
            }
            $appointment->update(['status' => 'canceled']);
            app(AppraisalReviewService::class)->queueMessage($appointment, 'canceled');

            return true;
        });
    }

    /**
     * The review a new booking belongs to: the reference the booking question
     * carries, otherwise the buyer's oldest appraisal still waiting on a date.
     */
    private function matchReview(array $booking, string $email): ?string
    {
        $field = (string) config('appraisal_reviews.bookafy.reference_field');
        $reference = (string) data_get($booking, 'appointment_customer_info.'.$field, '');
        $reference = Str::after(trim($reference), 'gasq_appraisal_');
        if (Str::isUuid($reference)) {
            return $reference;
        }
        if ($email === '') {
            return null;
        }
        $reviews = AppraisalReview::whereRaw('lower(buyer_email) = ?', [Str::lower($email)])->whereNull('delivered_at');

        // An appraisal still waiting on a date first; otherwise the buyer has
        // booked a second time, and ingest supersedes the earlier appointment.
        return (clone $reviews)->whereDoesntHave('appointments', fn ($query) => $query->where('status', 'scheduled'))
            ->oldest()->value('id') ?? $reviews->latest()->value('id');
    }

    private function managementUrl(string $key, array $booking): string
    {
        $template = (string) config('appraisal_reviews.bookafy.'.$key);
        abort_unless($template !== '', 503, 'The Bookafy management links are not configured.');
        $url = str_replace(
            ['{token}', '{cancel_token}', '{update_token}', '{id}'],
            [(string) ($booking['token'] ?? ''), (string) ($booking['cancel_token'] ?? ''),
                (string) ($booking['update_token'] ?? ''), (string) ($booking['id'] ?? '')],
            $template
        );
        abort_unless(filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https'
            && str_ends_with((string) parse_url($url, PHP_URL_HOST), 'bookafy.com')
            && ! str_contains($url, '{'), 422, 'A valid Bookafy management link is required.');

        return $url;
    }

    private function meetingDetails(array $booking): string
    {
        foreach (['zoom_join_url', 'join_url', 'teams_meeting_url', 'webex_meeting_url', 'sessions24_join_url', 'calendar_location'] as $key) {
            $value = $booking[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $value = trim($value);
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    abort_unless(parse_url($value, PHP_URL_SCHEME) === 'https', 422);
                }

                return $value;
            }
        }

        return '';
    }
}
