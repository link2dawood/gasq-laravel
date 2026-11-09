<?php

namespace App\Http\Controllers;

use App\Models\VendorEstimateDelivery;
use App\Models\VendorReviewAppointment;
use App\Services\VendorAppointmentService;
use Illuminate\Http\Request;

class VendorEstimateBookingController extends Controller
{
    public function book(Request $request, string $token, VendorAppointmentService $service)
    {
        $delivery = $this->delivery($token);
        abort_unless($delivery->native_scheduling, 404);
        $data = $request->validate(['slot_id' => ['required', 'integer', 'min:1'], 'timezone' => ['required', 'timezone:all']]);
        $appointment = $service->book($delivery, $data['slot_id'], $data['timezone']);

        return redirect()->to($appointment->manageUrl())->with('status', 'Appointment booked. Confirmation emails have been queued for you and your vendor.');
    }

    public function manage(string $token)
    {
        $appointment = $this->appointment($token);

        return response()->view('vendor-calendar.appointment', compact('appointment'))->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function cancel(string $token, VendorAppointmentService $service)
    {
        $appointment = $this->appointment($token);
        $service->cancel($appointment);

        return redirect()->to($appointment->manageUrl())->with('status', 'Appointment canceled. Cancellation emails have been queued for both parties.');
    }

    public function delivery(string $token): VendorEstimateDelivery
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/D', $token), 404);
        $delivery = VendorEstimateDelivery::where('scheduling_token_hash', hash('sha256', $token))->whereIn('status', ['sending', 'sent', 'uncertain'])->firstOrFail();
        abort_if($delivery->scheduling_expires_at->isPast(), 410, 'This estimate review link has expired. Please contact your vendor for an updated estimate.');
        abort_if($delivery->access_revoked_at, 410, 'This delivery has been revoked.');

        return $delivery;
    }

    private function appointment(string $token): VendorReviewAppointment
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/D', $token), 404);

        return VendorReviewAppointment::where('manage_token_hash', hash('sha256', $token))->firstOrFail();
    }
}
