<?php

namespace App\Http\Controllers;

use App\Mail\InterviewInviteMail;
use App\Mail\InterviewScheduledMail;
use App\Models\Interview;
use App\Models\InterviewConfig;
use App\Models\InterviewSlot;
use App\Models\JobPosting;
use App\Support\InterviewCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class InterviewController extends Controller
{
    /* ===================================================================
     |  BUYER — control room
     * =================================================================== */

    /**
     * Buyer interview control room for a single job: configure the process,
     * publish open time slots, invite vendors, score them, and (once certified)
     * reveal sealed prices.
     */
    public function manage(JobPosting $job): View
    {
        $this->authorizeOwner($job);

        $config = $job->interviewConfig()->firstOrNew([]);

        $interviews = $job->interviews()
            ->with('vendor:id,name,company', 'bid')
            ->orderByDesc('scheduled_at')
            ->get();

        $slots = $job->interviewSlots()->orderBy('starts_at')->get();

        // Vendors who bid on this job and are NOT yet invited — the invite pool.
        $invitedVendorIds = $interviews->pluck('vendor_id')->all();
        $bidVendors = $job->bids()
            ->with('user:id,name,company')
            ->latest()
            ->get()
            ->unique('user_id')
            ->reject(fn ($bid) => in_array($bid->user_id, $invitedVendorIds, true))
            ->values();

        return view('interviews.manage', [
            'job' => $job,
            'config' => $config,
            'interviews' => $interviews,
            'slots' => $slots,
            'bidVendors' => $bidVendors,
            'timezones' => $this->timezoneOptions(),
            'criteria' => config('interview_scorecard.criteria', []),
        ]);
    }

    /** Create/update the interview configuration for a job. */
    public function saveConfig(Request $request, JobPosting $job): RedirectResponse
    {
        $this->authorizeOwner($job);

        $validated = $request->validate([
            'scheduling_method' => ['required', Rule::in([
                InterviewConfig::METHOD_SELF,
                InterviewConfig::METHOD_ASSIGNED,
                InterviewConfig::METHOD_GASQ,
            ])],
            'default_format' => ['nullable', Rule::in(['virtual', 'phone', 'in_person'])],
            'timezone' => ['nullable', 'string', 'max:64'],
            'interview_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'evaluation_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'min_gap_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'location' => ['nullable', 'string', 'max:1000'],
            'scheduling_deadline' => ['nullable', 'date'],
            'required_attendees' => ['nullable', 'string', 'max:2000'],
            'num_vendors' => ['nullable', 'integer', 'min:1', 'max:50'],
            'disclose_competitor_count' => ['nullable', 'boolean'],
            'reveal_method' => ['nullable', Rule::in(['all', 'finalists', 'selected'])],
        ]);

        $config = $job->interviewConfig()->firstOrNew([]);
        $config->fill($validated);
        $config->disclose_competitor_count = $request->boolean('disclose_competitor_count');

        if (in_array($config->status, [null, '', InterviewConfig::STATUS_SETUP], true)) {
            $config->status = InterviewConfig::STATUS_OPEN;
        }

        $job->interviewConfig()->save($config);

        if (! $job->interviews_scheduled) {
            $job->forceFill(['interviews_scheduled' => true])->save();
        }

        return redirect()->route('interviews.manage', $job)->with('success', 'Interview settings saved.');
    }

    /** Publish an open interview slot vendors can book. */
    public function addSlot(Request $request, JobPosting $job): RedirectResponse
    {
        $this->authorizeOwner($job);

        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
        ]);

        $config = $job->interviewConfig()->firstOrNew([]);
        $minutes = (int) ($config->interview_minutes ?: 30);

        $start = Carbon::parse($data['starts_at']);

        $job->interviewSlots()->create([
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes($minutes),
        ]);

        return redirect()->route('interviews.manage', $job)->with('success', 'Time slot added.');
    }

    /** Remove an open (unbooked) slot. */
    public function deleteSlot(JobPosting $job, InterviewSlot $slot): RedirectResponse
    {
        $this->authorizeOwner($job);
        abort_unless($slot->job_posting_id === $job->id, 404);

        if ($slot->isBooked()) {
            return redirect()->route('interviews.manage', $job)
                ->with('error', 'That slot is already booked — reschedule the vendor first.');
        }

        $slot->delete();

        return redirect()->route('interviews.manage', $job)->with('success', 'Time slot removed.');
    }

    /** Invite a vendor (from the bid pool) to interview. */
    public function invite(Request $request, JobPosting $job): RedirectResponse
    {
        $this->authorizeOwner($job);

        $data = $request->validate([
            'vendor_id' => ['required', 'integer'],
        ]);

        // The vendor must have engaged (bid on) this job.
        $bid = $job->bids()->where('user_id', $data['vendor_id'])->latest()->first();
        abort_unless($bid !== null, 422);

        $config = $job->interviewConfig()->firstOrNew([]);

        $interview = Interview::firstOrCreate(
            ['job_posting_id' => $job->id, 'vendor_id' => (int) $data['vendor_id']],
            [
                'bid_id' => $bid->id,
                'status' => 'invited',
                'duration_minutes' => (int) ($config->interview_minutes ?: 30),
                'format' => $config->default_format,
                'location' => $config->location,
                'timezone' => $config->timezone,
                'price_status' => 'sealed',
            ]
        );

        // Notify the vendor — but only the first time they're invited.
        if ($interview->wasRecentlyCreated) {
            $this->deliver($interview->vendor?->email, new InterviewInviteMail($interview));
        }

        return redirect()->route('interviews.manage', $job)->with('success', 'Vendor invited to interview.');
    }

    /** Save the weighted scorecard for one interview (spec §7). */
    public function score(Request $request, JobPosting $job, Interview $interview): RedirectResponse
    {
        $this->authorizeOwner($job);
        abort_unless($interview->job_posting_id === $job->id, 404);

        $criteria = config('interview_scorecard.criteria', []);
        $rules = ['buyer_notes' => ['nullable', 'string', 'max:4000']];
        foreach ($criteria as $c) {
            $rules['scores.' . $c['key']] = ['required', 'numeric', 'min:0', 'max:10'];
        }
        $validated = $request->validate($rules);

        $breakdown = [];
        $weighted = 0.0;
        foreach ($criteria as $c) {
            $val = (float) ($validated['scores'][$c['key']] ?? 0);
            $breakdown[$c['key']] = $val;
            $weighted += $val * (float) $c['weight'];
        }

        $interview->fill([
            'score_breakdown' => $breakdown,
            'capability_score' => round($weighted * 10, 2), // 0–100
            'scored_at' => now(),
            'buyer_notes' => $validated['buyer_notes'] ?? $interview->buyer_notes,
            'status' => 'buyer_score_completed',
            'completed_at' => $interview->completed_at ?? now(),
        ])->save();

        return redirect()->route('interviews.manage', $job)->with('success', 'Scorecard saved.');
    }

    /**
     * Certify interviews complete — the gate that unlocks the sealed prices
     * (spec §11). Nothing pricing-related is visible until this is done.
     */
    public function certify(Request $request, JobPosting $job): RedirectResponse
    {
        $this->authorizeOwner($job);

        $config = $job->interviewConfig()->firstOrNew([]);
        $config->status = InterviewConfig::STATUS_CERTIFIED;
        $config->certified_at = now();
        $job->interviewConfig()->save($config);

        $job->interviews()->whereIn('status', ['scheduled', 'confirmed', 'completed', 'buyer_score_completed'])
            ->update(['status' => 'buyer_score_completed']);

        return redirect()->route('interviews.manage', $job)
            ->with('success', 'Interviews certified complete. You can now reveal sealed pricing.');
    }

    /** Reveal sealed prices after certification (spec §11–12). */
    public function reveal(Request $request, JobPosting $job): RedirectResponse
    {
        $this->authorizeOwner($job);

        $config = $job->interviewConfig()->firstOrNew([]);
        if (! $config->pricingUnlocked()) {
            return redirect()->route('interviews.manage', $job)
                ->with('error', 'Certify the interviews complete before revealing pricing.');
        }

        $job->interviews()->update(['price_status' => 'revealed']);
        $config->status = InterviewConfig::STATUS_PRICE_REVEALED;
        $job->interviewConfig()->save($config);

        return redirect()->route('interviews.manage', $job)->with('success', 'Sealed pricing revealed.');
    }

    /* ===================================================================
     |  VENDOR — self-scheduling
     * =================================================================== */

    /** A vendor's interview invitations across all jobs. */
    public function myInterviews(Request $request): View
    {
        $interviews = Interview::query()
            ->where('vendor_id', $request->user()->id)
            ->with('jobPosting:id,title,location', 'slot')
            ->orderByRaw('scheduled_at is null desc')
            ->orderByDesc('updated_at')
            ->get();

        return view('interviews.vendor-index', ['interviews' => $interviews]);
    }

    /** Vendor scheduling screen for one invitation: pick an open slot. */
    public function schedule(Request $request, Interview $interview): View
    {
        $this->authorizeVendor($request, $interview);

        $job = $interview->jobPosting;
        $config = $job->interviewConfig()->firstOrNew([]);

        $openSlots = $job->interviewSlots()
            ->whereNull('interview_id')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->get();

        return view('interviews.vendor-schedule', [
            'interview' => $interview,
            'job' => $job,
            'config' => $config,
            'openSlots' => $openSlots,
        ]);
    }

    /** Vendor books an open slot. */
    public function book(Request $request, Interview $interview): RedirectResponse
    {
        $this->authorizeVendor($request, $interview);

        $data = $request->validate(['slot_id' => ['required', 'integer']]);

        $job = $interview->jobPosting;
        $config = $job->interviewConfig()->firstOrNew([]);

        $slotId = (int) $data['slot_id'];

        // Re-booking the exact slot they already hold — nothing to do.
        if ($interview->slot_id && (int) $interview->slot_id === $slotId) {
            return redirect()->route('interviews.vendor.schedule', $interview)
                ->with('success', 'Your interview is already booked for that time.');
        }

        // Atomically claim the slot: the UPDATE succeeds only if it is still open,
        // so two vendors racing for the same time can never both win.
        $booked = DB::transaction(function () use ($interview, $job, $slotId) {
            $claimed = InterviewSlot::where('id', $slotId)
                ->where('job_posting_id', $job->id)
                ->whereNull('interview_id')
                ->update(['interview_id' => $interview->id]);

            if ($claimed === 0) {
                return null;
            }

            // Release the previously held slot (reschedule).
            if ($interview->slot_id) {
                InterviewSlot::where('id', $interview->slot_id)
                    ->where('interview_id', $interview->id)
                    ->update(['interview_id' => null]);
                $interview->increment('reschedule_count');
            }

            return InterviewSlot::find($slotId);
        });

        if (! $booked) {
            return redirect()->route('interviews.vendor.schedule', $interview)
                ->with('error', 'That time was just taken — please pick another.');
        }

        $interview->update([
            'slot_id' => $booked->id,
            'scheduled_at' => $booked->starts_at,
            'status' => 'scheduled',
            'format' => $interview->format ?: $config->default_format,
            'location' => $interview->location ?: $config->location,
            'timezone' => $interview->timezone ?: $config->timezone,
        ]);

        // Confirm to the vendor and notify the buyer, each with an .ics attachment.
        $this->deliver($interview->vendor?->email, new InterviewScheduledMail($interview, 'vendor'));
        $this->deliver($job->user?->email, new InterviewScheduledMail($interview, 'buyer'));

        return redirect()->route('interviews.vendor.schedule', $interview)
            ->with('success', 'Interview scheduled. Add it to your calendar below.');
    }

    /** Vendor acknowledges they have reviewed the interview prep (spec §7). */
    public function acknowledgePrep(Request $request, Interview $interview): RedirectResponse
    {
        $this->authorizeVendor($request, $interview);

        if ($interview->vendor_prep_acknowledged_at === null) {
            $interview->update(['vendor_prep_acknowledged_at' => now()]);
        }

        return redirect()->route('interviews.vendor.schedule', $interview)
            ->with('success', 'Thanks — your prep is acknowledged.');
    }

    /* ===================================================================
     |  Calendar
     * =================================================================== */

    /** Download an .ics file for a scheduled interview (buyer or vendor). */
    public function ics(Request $request, Interview $interview): Response
    {
        $isVendor = $interview->vendor_id === $request->user()->id;
        $isOwner = $interview->jobPosting?->user_id === $request->user()->id;
        abort_unless($isVendor || $isOwner, 403);
        abort_unless($interview->scheduled_at !== null, 404);

        $ics = InterviewCalendar::ics($interview);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="gasq-interview-' . $interview->id . '.ics"',
        ]);
    }

    /* ===================================================================
     |  Authorization helpers
     * =================================================================== */

    /** Send a mailable, swallowing/logging errors so email can't block the action. */
    private function deliver(?string $email, \Illuminate\Mail\Mailable $mailable): void
    {
        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function authorizeOwner(JobPosting $job): void
    {
        abort_unless($job->user_id === auth()->id(), 403);
    }

    private function authorizeVendor(Request $request, Interview $interview): void
    {
        abort_unless($interview->vendor_id === $request->user()->id, 403);
    }

    /** @return array<string, string> */
    private function timezoneOptions(): array
    {
        return [
            'America/New_York' => 'Eastern (America/New_York)',
            'America/Chicago' => 'Central (America/Chicago)',
            'America/Denver' => 'Mountain (America/Denver)',
            'America/Phoenix' => 'Arizona (America/Phoenix)',
            'America/Los_Angeles' => 'Pacific (America/Los_Angeles)',
            'America/Anchorage' => 'Alaska (America/Anchorage)',
            'Pacific/Honolulu' => 'Hawaii (Pacific/Honolulu)',
            'America/Toronto' => 'Canada Eastern (America/Toronto)',
            'America/Vancouver' => 'Canada Pacific (America/Vancouver)',
            'America/Mexico_City' => 'Mexico (America/Mexico_City)',
            'UTC' => 'UTC',
        ];
    }
}
