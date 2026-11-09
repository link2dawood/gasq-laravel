<?php

namespace App\Console\Commands;

use App\Jobs\SendVendorAppointmentMessage;
use App\Models\VendorAppointmentMessage;
use App\Models\VendorReviewAppointment;
use App\Services\VendorAppointmentService;
use Illuminate\Console\Command;

class ProcessVendorAppointments extends Command
{
    protected $signature = 'vendor-appointments:process';

    protected $description = 'Queue 24-hour and 1-hour reminders for vendor estimate review appointments';

    public function handle(VendorAppointmentService $service): int
    {
        VendorReviewAppointment::where('status', 'scheduled')->where('ends_at', '<=', now())->whereNotNull('active_estimate_id')->update(['active_slot_id' => null, 'active_estimate_id' => null]);
        VendorReviewAppointment::where('status', 'scheduled')->where('starts_at', '>', now())->where('starts_at', '<=', now()->addHours(24))->chunkById(100, function ($appointments) use ($service) {
            foreach ($appointments as $appointment) {
                foreach ([24, 1] as $hours) {
                    $due = $appointment->starts_at->subHours($hours);
                    if ($due->lte(now()) && $due->gte(now()->subMinutes(5)) && $appointment->created_at->lte($due)) {
                        $service->queueMessages($appointment, 'reminder_'.$hours.'h');
                    }
                }
            }
        });
        VendorAppointmentMessage::where('status', 'queued')->chunkById(100, function ($messages) {
            foreach ($messages as $message) {
                SendVendorAppointmentMessage::dispatch($message->id);
            }
        });
        $this->info('Vendor appointment confirmations and reminders processed.');

        return self::SUCCESS;
    }
}
