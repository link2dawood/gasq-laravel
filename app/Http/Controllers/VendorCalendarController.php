<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VendorReviewAppointment;
use App\Models\VendorReviewSlot;
use App\Models\VendorSchedulingProfile;
use App\Services\VendorAppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorCalendarController extends Controller
{
    public function index(Request $request)
    {
        $vendor = $this->vendor($request);
        $profile = VendorSchedulingProfile::firstOrNew(['vendor_id' => $vendor->id], ['enabled' => false, 'timezone' => 'UTC', 'meeting_details' => '']);
        $slots = VendorReviewSlot::where('vendor_id', $vendor->id)->where('active', true)->where('ends_at', '>', now())->with('reservation')->orderBy('starts_at')->paginate(30, ['*'], 'slots_page');
        $appointments = VendorReviewAppointment::where('vendor_id', $vendor->id)->with('messages')->latest('starts_at')->paginate(15, ['*'], 'appointments_page');

        return view('vendor-calendar.index', compact('profile', 'slots', 'appointments'));
    }

    public function settings(Request $request)
    {
        $vendor = $this->vendor($request);
        $data = $request->validate(['enabled' => ['required', 'boolean'], 'timezone' => ['required', 'timezone:all'], 'meeting_details' => ['nullable', 'required_if:enabled,1', 'string', 'max:2000']]);
        $data['meeting_details'] = $data['meeting_details'] ?? '';
        DB::transaction(function () use ($vendor, $data) {
            User::whereKey($vendor->id)->lockForUpdate()->firstOrFail();
            VendorSchedulingProfile::updateOrCreate(['vendor_id' => $vendor->id], $data);
        });

        return back()->with('status', 'Calendar settings saved. Existing slots and appointments keep their original time zone and meeting details.');
    }

    public function publish(Request $request, VendorAppointmentService $service)
    {
        $vendor = $this->vendor($request);
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d'], 'from' => ['required', 'date_format:H:i'], 'until' => ['required', 'date_format:H:i']]);
        $count = $service->publish($vendor, $data);

        return back()->with('status', $count.' available 30-minute slots published.');
    }

    public function remove(Request $request, VendorReviewSlot $slot)
    {
        $vendor = $this->vendor($request);
        abort_unless($slot->vendor_id === $vendor->id, 404);
        DB::transaction(function () use ($vendor, $slot) {
            User::whereKey($vendor->id)->lockForUpdate()->firstOrFail();
            abort_if($slot->reservation()->exists(), 422, 'Cancel the appointment before removing its availability.');
            $slot->update(['active' => false]);
        });

        return back()->with('status', 'Availability removed.');
    }

    public function cancel(Request $request, VendorReviewAppointment $appointment, VendorAppointmentService $service)
    {
        abort_unless($appointment->vendor_id === $this->vendor($request)->id, 404);
        $service->cancel($appointment);

        return back()->with('status', 'Appointment canceled. Confirmation emails have been queued for both parties.');
    }

    private function vendor(Request $request): User
    {
        abort_unless($request->user()->isVendor(), 403);

        return $request->user();
    }
}
