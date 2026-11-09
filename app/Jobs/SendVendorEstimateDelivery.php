<?php

namespace App\Jobs;

use App\Mail\VendorEstimateMail;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorPdfAccessEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendVendorEstimateDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $deliveryId) {}

    public function handle(): void
    {
        $claimed = VendorEstimateDelivery::whereKey($this->deliveryId)->where('status', 'queued')->update(['status' => 'sending', 'claimed_at' => now()]);
        if (! $claimed) {
            return;
        }
        $delivery = VendorEstimateDelivery::findOrFail($this->deliveryId);
        VendorPdfAccessEvent::record($delivery, 'email_send_claimed');
        try {
            if ($delivery->access_revoked_at) {
                $delivery->update(['status' => 'failed', 'failure_code' => 'AccessRevoked']);
                VendorPdfAccessEvent::record($delivery, 'email_suppressed_revoked');

                return;
            }
            if ($delivery->estimate?->status !== 'ready') {
                $delivery->update(['status' => 'failed', 'failure_code' => 'EstimateNotReady']);

                return;
            }
            if (! app()->environment('testing') && in_array(config('mail.default'), ['log', 'array'], true)) {
                $delivery->update(['status' => 'failed', 'failure_code' => 'MailNotConfigured']);

                return;
            }
            if ($delivery->scheduling_expires_at->isPast()) {
                $delivery->update(['status' => 'failed', 'failure_code' => 'EstimateExpired']);

                return;
            }
            $bytes = Storage::disk('local')->get($delivery->pdf_path);
            if (! is_string($bytes) || ! hash_equals($delivery->pdf_sha256, hash('sha256', $bytes))) {
                $delivery->update(['status' => 'failed', 'failure_code' => 'PdfIntegrityCheckFailed']);

                return;
            }
        } catch (\Throwable) {
            $delivery->update(['status' => 'failed', 'failure_code' => 'PdfUnavailable']);

            return;
        }
        try {
            $sent = Mail::to($delivery->recipient)->send(new VendorEstimateMail($delivery, $bytes));
            if ($sent === null && ! app()->environment('testing')) {
                $delivery->update(['status' => 'uncertain', 'failure_code' => 'TransportAcceptanceUnknown']);

                return;
            }
            $delivery->update(['status' => 'sent', 'sent_at' => now()]);
            VendorPdfAccessEvent::record($delivery, 'email_transport_accepted');
        } catch (\Throwable $exception) {
            // Transport exceptions may occur after acceptance; never auto-resend.
            $delivery->update(['status' => 'uncertain', 'failure_code' => class_basename($exception)]);
            VendorPdfAccessEvent::record($delivery, 'email_transport_uncertain');
        }
    }

    public function failed(?\Throwable $exception): void
    {
        VendorEstimateDelivery::whereKey($this->deliveryId)->where('status', 'sending')->update(['status' => 'uncertain', 'failure_code' => 'WorkerInterrupted']);
    }
}
