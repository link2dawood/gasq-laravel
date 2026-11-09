<?php

namespace App\Http\Controllers;

use App\Models\VendorEstimateDelivery;
use App\Models\VendorReviewAppointment;
use App\Models\VendorReviewSlot;
use App\Models\VendorSchedulingProfile;
use Illuminate\Http\Request;

class VendorEstimateSchedulingController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/D', $token), 404);
        $delivery = VendorEstimateDelivery::where('scheduling_token_hash', hash('sha256', $token))->whereIn('status', ['sending', 'sent', 'uncertain'])->firstOrFail();
        abort_if($delivery->scheduling_expires_at->isPast(), 410, 'This estimate review link has expired. Please contact your vendor for an updated estimate.');
        abort_if($delivery->access_revoked_at, 410, 'This delivery has been revoked. Please contact your vendor.');
        VendorEstimateDelivery::whereKey($delivery->id)->whereNull('scheduling_opened_at')->update(['scheduling_opened_at' => now()]);

        if ($delivery->native_scheduling) {
            $appointment = VendorReviewAppointment::where('active_estimate_id', $delivery->vendor_lead_estimate_id)->first();
            if ($appointment && $appointment->ends_at->isFuture()) {
                return redirect()->to($appointment->manageUrl());
            }
            $profile = VendorSchedulingProfile::where('vendor_id', $delivery->estimate->vendor_id)->first();
            $data = $request->validate(['timezone' => ['nullable', 'timezone:all']]);
            $timezone = $data['timezone'] ?? $profile?->timezone ?? 'UTC';
            $slots = VendorReviewSlot::where('vendor_id', $delivery->estimate->vendor_id)->where('active', true)->where('starts_at', '>', now())->whereDoesntHave('reservation')
                ->when(! $profile?->enabled || $delivery->estimate->status !== 'ready', fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('starts_at')->paginate(40)->withQueryString();

            return response()->view('vendor-calendar.book', compact('delivery', 'slots', 'timezone'))->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return redirect()->away($delivery->scheduling_target)->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
