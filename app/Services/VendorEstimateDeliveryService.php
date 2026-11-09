<?php

namespace App\Services;

use App\Jobs\SendVendorEstimateDelivery;
use App\Models\User;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorLeadEstimate;
use App\Models\VendorPdfAccessEvent;
use App\Models\VendorSchedulingProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendorEstimateDeliveryService
{
    public function schedulingBaseUrl(?User $vendor = null): ?string
    {
        if ($vendor && VendorSchedulingProfile::where('vendor_id', $vendor->id)->where('enabled', true)->exists()) {
            return 'native';
        }

        return app(CustomerBookingLink::class)->url();
    }

    public function prepare(VendorLeadEstimate $estimate, string $password, string $requestKey): VendorEstimateDelivery
    {
        $password = trim($password);
        $native = $estimate->vendor && VendorSchedulingProfile::where('vendor_id', $estimate->vendor_id)->where('enabled', true)->exists();
        $booking = $native ? ['provider' => 'native', 'url' => 'native'] : app(CustomerBookingLink::class)->configured();
        $base = $booking['url'] ?? null;
        $provider = $booking['provider'] ?? null;
        if (! $base) {
            throw ValidationException::withMessages(['delivery' => 'Configure the Bookafy or Calendly appraisal booking URL before sending estimates.']);
        }
        if (! app()->environment('testing') && in_array(config('mail.default'), ['log', 'array'], true)) {
            throw ValidationException::withMessages(['delivery' => 'Email delivery is not configured.']);
        }
        $path = null;
        try {
            $delivery = DB::transaction(function () use ($estimate, $password, $requestKey, $base, $provider, &$path) {
                $estimate = VendorLeadEstimate::whereKey($estimate->id)->lockForUpdate()->firstOrFail()->load('vendor');
                $existing = VendorEstimateDelivery::where('request_key', $requestKey)->first();
                if ($existing) {
                    abort_unless($existing->vendor_lead_estimate_id === $estimate->id, 422);

                    return $existing;
                }
                if ($estimate->status !== 'ready' || $estimate->valid_until->isBefore(today())) {
                    throw ValidationException::withMessages(['delivery' => 'Only a Ready estimate with a current validity date can be emailed.']);
                }
                validator(['email' => $estimate->buyer['email'] ?? null, 'pdf_password' => $password], [
                    'email' => ['required', 'email', 'max:255'],
                    'pdf_password' => ['required', 'string', 'min:8', 'max:32', 'regex:/^[\x20-\x7E]+$/'],
                ])->validate();
                $bytes = app(ReportService::class)->calculatorPdf('vendor-lead-estimate', ['estimate' => $estimate, 'user' => $estimate->vendor], $password)->output();
                if (! str_starts_with($bytes, '%PDF-') || ! str_contains($bytes, '/Encrypt')) {
                    throw new \RuntimeException('The estimate PDF must be password protected.');
                }
                $path = 'vendor-estimate-deliveries/'.Str::uuid().'.pdf';
                if (! Storage::disk('local')->put($path, $bytes)) {
                    throw new \RuntimeException('The protected estimate could not be saved.');
                }
                $token = Str::random(64);
                $reference = 'gasq_estimate_'.$requestKey;
                $query = [$provider === 'bookafy' ? config('appraisal_reviews.bookafy.reference_field', 'Reference') : 'utm_content' => $reference];

                $delivery = VendorEstimateDelivery::create([
                    'vendor_lead_estimate_id' => $estimate->id, 'request_key' => $requestKey,
                    'recipient' => $estimate->buyer['email'],
                    'snapshot' => ['number' => $estimate->number, 'title' => $estimate->title, 'buyer' => $estimate->buyer, 'currency' => $estimate->currency, 'totals' => $estimate->totals(), 'valid_until' => $estimate->valid_until->toDateString(), 'vendor_name' => $estimate->vendor->company ?: $estimate->vendor->name, 'reply_to' => $estimate->vendor->email],
                    'scheduling_token' => $token, 'scheduling_token_hash' => hash('sha256', $token),
                    'scheduling_target' => $base === 'native' ? 'native' : $base.(str_contains($base, '?') ? '&' : '?').http_build_query($query),
                    'native_scheduling' => $base === 'native',
                    'scheduling_expires_at' => $estimate->valid_until->copy()->endOfDay(),
                    'pdf_path' => $path, 'pdf_sha256' => hash('sha256', $bytes), 'status' => 'queued', 'password_hash' => Hash::make($password),
                ]);
                VendorPdfAccessEvent::record($delivery, 'protected_pdf_prepared', $estimate->vendor_id);

                return $delivery;
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        // Dispatching an existing queued attempt is harmless: the job claims it once.
        if ($delivery->status === 'queued') {
            SendVendorEstimateDelivery::dispatch($delivery->id)->afterCommit();
        }

        return $delivery;
    }
}
