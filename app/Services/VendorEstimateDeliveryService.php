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
        // Every file this attempt writes, so a failure half way through leaves
        // nothing orphaned on disk.
        $paths = [];
        try {
            $delivery = DB::transaction(function () use ($estimate, $password, $requestKey, $base, $provider, &$path, &$paths) {
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
                // Buyer edition: masked inside AND password protected. The
                // password gates when the buyer reads the file; the masking gates
                // what is in it, because releasing the password is permanent and
                // the covering email asks the buyer to forward the attachment to
                // whoever approves the budget.
                $bytes = app(ReportService::class)->calculatorPdf('vendor-lead-estimate-buyer', ['estimate' => $estimate, 'user' => $estimate->vendor], $password)->output();
                if (! str_starts_with($bytes, '%PDF-') || ! str_contains($bytes, '/Encrypt')) {
                    throw new \RuntimeException('The estimate PDF must be password protected.');
                }
                $path = 'vendor-estimate-deliveries/'.Str::uuid().'.pdf';
                if (! Storage::disk('local')->put($path, $bytes)) {
                    throw new \RuntimeException('The protected estimate could not be saved.');
                }
                $paths[] = $path;
                $token = Str::random(64);
                $reference = 'gasq_estimate_'.$requestKey;
                $query = [$provider === 'bookafy' ? config('appraisal_reviews.bookafy.reference_field', 'Reference') : 'utm_content' => $reference];

                $delivery = VendorEstimateDelivery::create([
                    'vendor_lead_estimate_id' => $estimate->id, 'request_key' => $requestKey,
                    'recipient' => $estimate->buyer['email'],
                    'snapshot' => ['number' => $estimate->number, 'title' => $estimate->title, 'buyer' => $estimate->buyer, 'currency' => $estimate->currency, 'totals' => $estimate->totals(), 'valid_until' => $estimate->valid_until->toDateString(), 'has_dashboard' => $estimate->hasBudgetDashboard(), 'vendor_name' => $estimate->vendor->company ?: $estimate->vendor->name, 'reply_to' => $estimate->vendor->email],
                    'scheduling_token' => $token, 'scheduling_token_hash' => hash('sha256', $token),
                    'scheduling_target' => $base === 'native' ? 'native' : $base.(str_contains($base, '?') ? '&' : '?').http_build_query($query),
                    'native_scheduling' => $base === 'native',
                    'scheduling_expires_at' => $estimate->valid_until->copy()->endOfDay(),
                    'pdf_path' => $path, 'pdf_sha256' => hash('sha256', $bytes), 'status' => 'queued', 'password_hash' => Hash::make($password),
                ]);
                VendorPdfAccessEvent::record($delivery, 'protected_pdf_prepared', $estimate->vendor_id);
                $this->copySharedDocuments($estimate, $delivery, $paths);

                return $delivery;
            });
        } catch (\Throwable $exception) {
            foreach (array_filter(array_merge($paths, [$path])) as $written) {
                Storage::disk('local')->delete($written);
            }
            throw $exception;
        }
        // Dispatching an existing queued attempt is harmless: the job claims it once.
        if ($delivery->status === 'queued') {
            SendVendorEstimateDelivery::dispatch($delivery->id)->afterCommit();
        }

        return $delivery;
    }

    /**
     * Take this delivery's own copy of every attachment the vendor marked for
     * the buyer.
     *
     * Copied, not referenced: a delivery is a snapshot, so a file the vendor
     * later edits, unshares or deletes must not change what a buyer has
     * already been given. The buyer never receives these by email, where a
     * file cannot be withheld, tracked or withdrawn. They are served from the
     * access page instead, behind the same email verification as the estimate,
     * and they go away with the rest when access is revoked.
     *
     * @param  array<int, string>  $paths  Collects written paths for cleanup.
     */
    private function copySharedDocuments(VendorLeadEstimate $estimate, VendorEstimateDelivery $delivery, array &$paths): void
    {
        $shared = $estimate->attachments()->where('share_with_buyer', true)->get();
        foreach ($shared as $attachment) {
            if (! Storage::disk('local')->exists($attachment->path)) {
                continue;
            }
            $bytes = Storage::disk('local')->get($attachment->path);
            if (! is_string($bytes) || ! hash_equals($attachment->sha256, hash('sha256', $bytes))) {
                throw new \RuntimeException('A supporting document failed its checksum and was not sent.');
            }
            $copy = 'vendor-estimate-delivery-documents/'.$delivery->id.'/'.Str::uuid().'.'.pathinfo($attachment->original_name, PATHINFO_EXTENSION);
            if (! Storage::disk('local')->put($copy, $bytes)) {
                throw new \RuntimeException('A supporting document could not be saved.');
            }
            $paths[] = $copy;
            $delivery->documents()->create([
                'original_name' => $attachment->original_name,
                'path' => $copy,
                'mime_type' => $attachment->mime_type,
                'size_bytes' => $attachment->size_bytes,
                'sha256' => $attachment->sha256,
            ]);
        }
        if ($shared->isNotEmpty()) {
            VendorPdfAccessEvent::record($delivery, 'supporting_documents_attached', $estimate->vendor_id);
        }
    }
}
