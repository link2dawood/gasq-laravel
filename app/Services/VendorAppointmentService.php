<?php

namespace App\Services;

use App\Jobs\SendVendorAppointmentMessage;
use App\Models\User;
use App\Models\VendorAppointmentMessage;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorLeadEstimate;
use App\Models\VendorReviewAppointment;
use App\Models\VendorReviewSlot;
use App\Models\VendorSchedulingProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendorAppointmentService
{
    public function publish(User $vendor, array $data): int
    {
        return DB::transaction(function () use ($vendor, $data) {
            User::whereKey($vendor->id)->lockForUpdate()->firstOrFail();
            $profile = VendorSchedulingProfile::where('vendor_id', $vendor->id)->where('enabled', true)->firstOrFail();
            $this->rejectAmbiguousTime($data['date'].' '.$data['from'], $profile->timezone);
            $this->rejectAmbiguousTime($data['date'].' '.$data['until'], $profile->timezone);
            $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', $data['date'].' '.$data['from'], $profile->timezone);
            $end = CarbonImmutable::createFromFormat('!Y-m-d H:i', $data['date'].' '.$data['until'], $profile->timezone);
            if ($start->format('Y-m-d H:i') !== $data['date'].' '.$data['from'] || $end->format('Y-m-d H:i') !== $data['date'].' '.$data['until'] || $start->lte(now()) || $end->lte($start)) {
                throw ValidationException::withMessages(['date' => 'Choose a valid future time range in your calendar time zone.']);
            }
            if ($start->offset !== $end->offset) {
                throw ValidationException::withMessages(['date' => 'Publish availability on one side of the daylight-saving time change at a time.']);
            }
            $count = 0;
            for ($at = $start; $at->addMinutes(30)->lte($end); $at = $at->addMinutes(30)) {
                $until = $at->addMinutes(30);
                $conflict = VendorReviewSlot::where('vendor_id', $vendor->id)->where('starts_at', '<', $until->utc())->where('ends_at', '>', $at->utc())
                    ->where(fn ($q) => $q->where('active', true)->orWhereHas('reservation'))->exists();
                if ($conflict) {
                    throw ValidationException::withMessages(['from' => 'This availability overlaps an existing slot or appointment.']);
                }
                VendorReviewSlot::updateOrCreate(['vendor_id' => $vendor->id, 'starts_at' => $at->utc()], ['ends_at' => $until->utc(), 'timezone' => $profile->timezone, 'meeting_details' => $profile->meeting_details, 'active' => true]);
                $count++;
            }
            if (! $count) {
                throw ValidationException::withMessages(['until' => 'Allow at least 30 minutes for an appointment.']);
            }

            return $count;
        });
    }

    public function book(VendorEstimateDelivery $delivery, int $slotId, ?string $buyerTimezone = null): VendorReviewAppointment
    {
        return DB::transaction(function () use ($delivery, $slotId, $buyerTimezone) {
            abort_if($delivery->fresh()->scheduling_expires_at->isPast(), 410);
            $estimate = $delivery->estimate;
            User::whereKey($estimate->vendor_id)->lockForUpdate()->firstOrFail();
            // Lock the estimate so a status change cannot race the booking.
            $estimate = VendorLeadEstimate::whereKey($estimate->id)->lockForUpdate()->firstOrFail();
            abort_unless($estimate->status === 'ready', 422, 'This estimate is no longer available for booking.');
            abort_unless(VendorSchedulingProfile::where('vendor_id', $estimate->vendor_id)->where('enabled', true)->exists(), 422, 'Booking is currently unavailable. Please contact your vendor.');
            VendorReviewAppointment::where('active_estimate_id', $estimate->id)->where('ends_at', '<=', now())->update(['active_slot_id' => null, 'active_estimate_id' => null]);
            $existing = VendorReviewAppointment::where('active_estimate_id', $estimate->id)->first();
            if ($existing) {
                return $existing;
            }
            $slot = VendorReviewSlot::whereKey($slotId)->where('vendor_id', $estimate->vendor_id)->where('active', true)->where('starts_at', '>', now())->whereDoesntHave('reservation')->first();
            if (! $slot) {
                throw ValidationException::withMessages(['slot_id' => 'That time is no longer available. Please select another slot.']);
            }
            $token = Str::random(64);
            $appointment = VendorReviewAppointment::create([
                'vendor_id' => $estimate->vendor_id, 'vendor_lead_estimate_id' => $estimate->id,
                'vendor_estimate_delivery_id' => $delivery->id, 'vendor_review_slot_id' => $slot->id,
                'active_slot_id' => $slot->id, 'active_estimate_id' => $estimate->id,
                'starts_at' => $slot->starts_at, 'ends_at' => $slot->ends_at, 'timezone' => $slot->timezone,
                'meeting_details' => $slot->meeting_details, 'buyer_name' => $delivery->snapshot['buyer']['contact_name'],
                'buyer_email' => $delivery->recipient, 'buyer_timezone' => $buyerTimezone ?? $slot->timezone, 'vendor_email' => $delivery->snapshot['reply_to'], 'vendor_name' => $delivery->snapshot['vendor_name'],
                'manage_token' => $token, 'manage_token_hash' => hash('sha256', $token), 'status' => 'scheduled',
            ]);
            $this->queueMessages($appointment, 'confirmation');

            return $appointment;
        });
    }

    public function cancel(VendorReviewAppointment $appointment): void
    {
        DB::transaction(function () use ($appointment) {
            User::whereKey($appointment->vendor_id)->lockForUpdate()->firstOrFail();
            $appointment = VendorReviewAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            if ($appointment->status !== 'scheduled') {
                return;
            }
            abort_if($appointment->starts_at->lte(now()), 422, 'Past or started appointments cannot be canceled.');
            $appointment->update(['status' => 'canceled', 'active_slot_id' => null, 'active_estimate_id' => null, 'canceled_at' => now()]);
            VendorAppointmentMessage::where('vendor_review_appointment_id', $appointment->id)->where('status', 'queued')->update(['status' => 'suppressed']);
            $this->queueMessages($appointment, 'canceled');
        });
    }

    public function queueMessages(VendorReviewAppointment $appointment, string $kind): void
    {
        foreach (['buyer' => $appointment->buyer_email, 'vendor' => $appointment->vendor_email] as $audience => $recipient) {
            $message = VendorAppointmentMessage::firstOrCreate(['vendor_review_appointment_id' => $appointment->id, 'kind' => $kind, 'audience' => $audience], ['recipient' => $recipient, 'status' => 'queued']);
            if ($message->status === 'queued') {
                SendVendorAppointmentMessage::dispatch($message->id)->afterCommit();
            }
        }
    }

    private function rejectAmbiguousTime(string $local, string $timezone): void
    {
        $wall = CarbonImmutable::createFromFormat('!Y-m-d H:i', $local, 'UTC')->timestamp;
        $changes = (new \DateTimeZone($timezone))->getTransitions($wall - 86400, $wall + 86400) ?: [];
        $previous = null;
        foreach ($changes as $change) {
            if ($previous !== null && $previous > $change['offset'] && $wall >= $change['ts'] + $change['offset'] && $wall < $change['ts'] + $previous) {
                throw ValidationException::withMessages(['date' => 'This local time occurs twice during a daylight-saving change. Choose a time outside the repeated hour.']);
            }
            $previous = $change['offset'];
        }
    }
}
