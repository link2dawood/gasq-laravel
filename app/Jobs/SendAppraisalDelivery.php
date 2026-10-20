<?php

namespace App\Jobs;

use App\Mail\AppraisalAppointmentMail;
use App\Models\AppraisalAppointment;
use App\Models\AppraisalDelivery;
use App\Models\AppraisalReview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendAppraisalDelivery implements ShouldQueue
{
    use Queueable;

    // SMTP acceptance can be ambiguous. Never automatically resend a claimed message.
    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $deliveryId) {}

    public function handle(): void
    {
        if (! config('appraisal_reviews.enabled') || (! app()->environment('testing') && in_array(config('mail.default'), ['log', 'array'], true))) {
            return;
        }
        $claimed = DB::transaction(function () {
            $delivery = AppraisalDelivery::whereKey($this->deliveryId)->lockForUpdate()->firstOrFail();
            if ($delivery->status !== 'pending') {
                return false;
            }
            $delivery->update(['status' => 'sending', 'claimed_at' => now()]);

            return true;
        });
        if (! $claimed) {
            return;
        }
        try {
            DB::transaction(function () {
                $delivery = AppraisalDelivery::findOrFail($this->deliveryId);
                $review = AppraisalReview::whereKey($delivery->appraisal_review_id)->lockForUpdate()->firstOrFail();
                $appointment = AppraisalAppointment::whereKey($delivery->appraisal_appointment_id)->lockForUpdate()->firstOrFail();
                $canceled = $delivery->kind === 'canceled';
                if (($canceled && $appointment->status !== 'canceled') || (! $canceled && ($appointment->status !== 'scheduled' || $appointment->ends_at->isPast()))
                    || ($delivery->kind === 'report' && (! $review->ready_at || $review->delivered_at))
                    || ($delivery->kind === 'booking' && $review->delivered_at)) {
                    $delivery->update(['status' => 'suppressed']);

                    return;
                }
                if (! $canceled && $appointment->meeting_details === '') {
                    $delivery->update(['status' => 'pending', 'claimed_at' => null]);

                    return;
                }
                if (in_array($delivery->kind, ['reminder_24h', 'reminder_1h'], true)) {
                    $due = $appointment->starts_at->subHours($delivery->kind === 'reminder_24h' ? 24 : 1);
                    if (! $delivery->appointment_starts_at->eq($appointment->starts_at) || $due->isFuture() || $due->lt(now()->subMinutes(5))) {
                        $delivery->update(['status' => 'suppressed']);

                        return;
                    }
                }
                Mail::to($review->buyer_email)->send(new AppraisalAppointmentMail($review, $appointment, $delivery->kind));
                $delivery->update(['status' => 'sent', 'sent_at' => now()]);
                if ($delivery->kind === 'report') {
                    $review->update(['delivered_at' => now()]);
                }
            });
        } catch (\Throwable $exception) {
            // Do not log transport messages, PDF bytes, personal data, or secrets.
            AppraisalDelivery::whereKey($this->deliveryId)->update(['status' => 'uncertain', 'failure_code' => class_basename($exception)]);
            report(new \RuntimeException('Appraisal delivery '.$this->deliveryId.' needs manual delivery review.'));
        }
    }
}
