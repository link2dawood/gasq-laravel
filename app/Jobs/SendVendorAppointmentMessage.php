<?php

namespace App\Jobs;

use App\Mail\VendorAppointmentMail;
use App\Models\VendorAppointmentMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendVendorAppointmentMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        if (! VendorAppointmentMessage::whereKey($this->messageId)->where('status', 'queued')->update(['status' => 'sending', 'claimed_at' => now()])) {
            return;
        }
        $message = VendorAppointmentMessage::findOrFail($this->messageId);
        $appointment = $message->appointment;
        if (($message->kind === 'canceled' && $appointment->status !== 'canceled') || ($message->kind !== 'canceled' && ($appointment->status !== 'scheduled' || $appointment->starts_at->lte(now())))) {
            $message->update(['status' => 'suppressed']);

            return;
        }
        if (str_starts_with($message->kind, 'reminder_')) {
            $hours = $message->kind === 'reminder_24h' ? 24 : 1;
            $due = $appointment->starts_at->subHours($hours);
            if ($due->isFuture() || $due->lt(now()->subMinutes(5))) {
                $message->update(['status' => 'suppressed']);

                return;
            }
        }
        if (! app()->environment('testing') && in_array(config('mail.default'), ['log', 'array'], true)) {
            $message->update(['status' => 'failed', 'failure_code' => 'MailNotConfigured']);

            return;
        }
        try {
            $sent = Mail::to($message->recipient)->send(new VendorAppointmentMail($message));
            if ($sent === null && ! app()->environment('testing')) {
                $message->update(['status' => 'uncertain', 'failure_code' => 'TransportAcceptanceUnknown']);

                return;
            }
            $message->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $exception) {
            $message->update(['status' => 'uncertain', 'failure_code' => class_basename($exception)]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        VendorAppointmentMessage::whereKey($this->messageId)->where('status', 'sending')->update(['status' => 'uncertain', 'failure_code' => 'WorkerInterrupted']);
    }
}
