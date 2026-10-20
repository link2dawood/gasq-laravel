<?php

namespace App\Services;

use App\Jobs\SendAppraisalDelivery;
use App\Models\AppraisalAppointment;
use App\Models\AppraisalDelivery;
use App\Models\AppraisalReview;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AppraisalReviewService
{
    public function finalize(AppraisalReview $review, string $password): void
    {
        validator(['pdf_password' => $password], ['pdf_password' => ['required', 'string', 'min:8', 'max:32', 'regex:/^[\x20-\x7E]+$/']])->validate();
        DB::transaction(function () use ($review, $password) {
            $review = AppraisalReview::whereKey($review->id)->lockForUpdate()->firstOrFail();
            if ($review->ready_at) {
                throw ValidationException::withMessages(['report' => 'This final report is immutable. Create a new review for a revised report.']);
            }
            $state = $review->calculatorState;
            abort_unless($state->calculator_type === 'budget-calculator', 422);
            $scenario = $state->scenario;
            $scenario['meta']['contact']['contactName'] = $review->buyer_name;
            $scenario['meta']['contact']['contactEmail'] = $review->buyer_email;
            $prepared = now();
            $data = app(CostToProtectEstimate::class)->build($scenario, $state->user);
            $bytes = app(ReportService::class)->calculatorPdf('buyer-appraisal', [
                'scenario' => $scenario, 'user' => $state->user,
                'reportNumber' => $review->report_number, 'datePrepared' => $prepared->format('F j, Y'),
                'preparedForEmail' => $review->buyer_email,
            ], $password)->output();
            if (! str_starts_with($bytes, '%PDF-') || ! str_contains($bytes, '/Encrypt')) {
                throw new \RuntimeException('The final appraisal must be a password-protected PDF.');
            }
            $path = 'appraisal-reviews/'.$review->id.'/final.pdf';
            if (! Storage::disk('local')->put($path, $bytes)) {
                throw new \RuntimeException('The protected appraisal could not be stored.');
            }
            $review->update([
                'prepared_at' => $prepared, 'ready_at' => $prepared, 'pdf_path' => $path,
                'pdf_sha256' => hash('sha256', $bytes),
                'findings' => ['inHouseCost' => $data['totalAnnualInternal'] * Currency::rate(), 'capitalRecovery' => $data['annualCapitalRecovery'] * Currency::rate(),
                    'paybackMonths' => $data['paybackMonths'], 'currency' => Currency::code()],
            ]);
            // Passwords are used only to encrypt the PDF, never stored or queued.
            $this->queueReady($review);
        });
    }

    public function queueReady(AppraisalReview $review): void
    {
        if (! $review->ready_at || $review->delivered_at) {
            return;
        }
        $appointment = $review->appointments()->where('status', 'scheduled')->where('ends_at', '>', now())->first();
        if ($appointment) {
            $delivery = AppraisalDelivery::firstOrCreate(['delivery_key' => 'report:'.$review->id], [
                'appraisal_review_id' => $review->id, 'appraisal_appointment_id' => $appointment->id, 'kind' => 'report', 'appointment_starts_at' => $appointment->starts_at,
            ]);
            // A canceled appointment may have suppressed a report that was not sent.
            $updated = AppraisalDelivery::whereKey($delivery->id)->whereIn('status', ['pending', 'suppressed'])
                ->update(['status' => 'pending', 'appraisal_appointment_id' => $appointment->id, 'appointment_starts_at' => $appointment->starts_at]);
            if ($updated) {
                SendAppraisalDelivery::dispatch($delivery->id)->afterCommit();
            }
        }
    }

    public function queueMessage(AppraisalAppointment $appointment, string $kind): void
    {
        $key = $kind.':'.$appointment->id;
        if (str_starts_with($kind, 'reminder_')) {
            $key .= ':'.$appointment->starts_at->timestamp;
        } elseif ($kind === 'rescheduled') {
            $key .= ':'.hash('sha256', json_encode([$appointment->starts_at->timestamp, $appointment->ends_at->timestamp, $appointment->meeting_details, $appointment->reschedule_url, $appointment->cancel_url]));
        }
        $delivery = AppraisalDelivery::firstOrCreate(['delivery_key' => $key], [
            'appraisal_review_id' => $appointment->appraisal_review_id, 'appraisal_appointment_id' => $appointment->id, 'kind' => $kind, 'appointment_starts_at' => $appointment->starts_at,
        ]);
        if ($delivery->status === 'pending') {
            SendAppraisalDelivery::dispatch($delivery->id)->afterCommit();
        }
    }
}
