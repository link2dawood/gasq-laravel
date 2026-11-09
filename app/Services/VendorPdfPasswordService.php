<?php

namespace App\Services;

use App\Models\User;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorPdfAccessEvent;
use App\Models\VendorReviewAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VendorPdfPasswordService
{
    public function release(VendorEstimateDelivery $delivery, User $vendor, ?int $appointmentId, string $password, array $external = []): void
    {
        abort_unless($vendor->isVendor() && $delivery->estimate->vendor_id === $vendor->id, 404);
        try {
            DB::transaction(function () use ($delivery, $vendor, $appointmentId, $password, $external) {
                User::whereKey($vendor->id)->lockForUpdate()->firstOrFail();
                $delivery = VendorEstimateDelivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
                if ($delivery->access_revoked_at) {
                    throw ValidationException::withMessages(['release' => 'This delivery has been revoked. Send a new protected PDF.']);
                }
                if ($delivery->password_released_at) {
                    return;
                }
                $appointment = $appointmentId ? VendorReviewAppointment::whereKey($appointmentId)->where('vendor_id', $vendor->id)->where('vendor_lead_estimate_id', $delivery->vendor_lead_estimate_id)->where('buyer_email', $delivery->recipient)->lockForUpdate()->first() : null;
                $externalReview = [];
                if (! $appointmentId && ! $delivery->native_scheduling) {
                    $offsetDate = 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/D';
                    validator($external, ['external_review_reference' => ['required', 'string', 'max:255'], 'external_review_started_at' => ['required', 'date', $offsetDate], 'external_review_ended_at' => ['required', 'date', $offsetDate, 'after:external_review_started_at', 'before_or_equal:now']])->validate();
                    $start = CarbonImmutable::parse($external['external_review_started_at'])->utc();
                    $end = CarbonImmutable::parse($external['external_review_ended_at'])->utc();
                    if ($start->lt($delivery->created_at)) {
                        throw ValidationException::withMessages(['release' => 'The external review must take place after this PDF was prepared.']);
                    }
                    $externalReview = ['external_review_reference' => $external['external_review_reference'], 'external_review_started_at' => $start, 'external_review_ended_at' => $end];
                }
                if ((! $externalReview && (! $appointment || $appointment->status !== 'scheduled' || $appointment->ends_at->gt(now()) || $appointment->ends_at->lt($delivery->created_at))) || $delivery->status !== 'sent') {
                    throw ValidationException::withMessages(['release' => 'A completed, uncanceled review for this buyer and estimate is required after the PDF was prepared. The email must have been sent.']);
                }
                if (! $delivery->password_hash || ! Hash::check(trim($password), $delivery->password_hash)) {
                    throw ValidationException::withMessages(['pdf_password' => 'The password must match this PDF. For older deliveries without a verification hash, send a new protected PDF.']);
                }
                if ($appointment) {
                    $appointment->update(['reviewed_at' => $appointment->reviewed_at ?? now(), 'reviewed_by' => $appointment->reviewed_by ?? $vendor->id]);
                }
                $delivery->update(array_merge($externalReview, ['released_password' => trim($password), 'password_released_at' => now(), 'password_released_by' => $vendor->id, 'release_appointment_id' => $appointment?->id, 'access_expires_at' => now()->addDays(30)]));
                if ($externalReview) {
                    VendorPdfAccessEvent::record($delivery, 'external_review_attested', $vendor->id);
                }
                VendorPdfAccessEvent::record($delivery, 'password_released', $vendor->id);
            });
        } catch (ValidationException $exception) {
            VendorPdfAccessEvent::record($delivery, 'release_denied', $vendor->id);
            throw $exception;
        }
    }

    public function revoke(VendorEstimateDelivery $delivery, User $vendor): void
    {
        abort_unless($vendor->isVendor() && $delivery->estimate->vendor_id === $vendor->id, 404);
        DB::transaction(function () use ($delivery, $vendor) {
            $delivery = VendorEstimateDelivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            if ($delivery->access_revoked_at) {
                return;
            }
            $delivery->update(['access_revoked_at' => now(), 'released_password' => null]);
            VendorPdfAccessEvent::record($delivery, 'access_revoked', $vendor->id);
        });
    }

    public function ensureAvailable(VendorEstimateDelivery $delivery): void
    {
        if (! $delivery->password_released_at || ! $delivery->released_password || $delivery->access_revoked_at || ! $delivery->access_expires_at || $delivery->access_expires_at->isPast()) {
            VendorPdfAccessEvent::record($delivery, 'access_denied');
            abort(403, 'Access is restricted, expired, or revoked. Please contact your vendor.');
        }
    }
}
