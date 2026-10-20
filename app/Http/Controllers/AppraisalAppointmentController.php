<?php

namespace App\Http\Controllers;

use App\Models\AppraisalAppointment;
use App\Models\AppraisalReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AppraisalAppointmentController extends Controller
{
    public function show(AppraisalAppointment $appointment): Response
    {
        return response()->view('appraisals.appointment', ['appointment' => $appointment])
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function confirm(Request $request, AppraisalAppointment $appointment): RedirectResponse
    {
        $request->validate(['attendance' => 'accepted']);
        DB::transaction(function () use ($appointment) {
            AppraisalReview::whereKey($appointment->appraisal_review_id)->lockForUpdate()->firstOrFail();
            $appointment = AppraisalAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless($appointment->status === 'scheduled' && $appointment->starts_at->isFuture(), 409, 'This appointment is no longer available for confirmation.');
            if (! $appointment->confirmed_at) {
                $appointment->update(['confirmed_at' => now()]);
            }
        });

        return redirect($appointment->viewUrl())->with('success', 'Your attendance is confirmed. Your report password will be provided personally during the appointment.');
    }
}
