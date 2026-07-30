<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Models\InterviewConfig;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class InterviewController extends Controller
{
    /**
     * Buyer interview control room for a single job: configure the interview
     * process (method, format, duration, window, deadline, sealed-price reveal)
     * and see the status of every invited vendor.
     */
    public function manage(JobPosting $job): View
    {
        $this->authorizeOwner($job);

        $config = $job->interviewConfig()->firstOrNew([]);

        $interviews = $job->interviews()
            ->with('vendor:id,name,company')
            ->orderByDesc('scheduled_at')
            ->get();

        // Vendors who have engaged (bid) on this job — the pool a buyer will
        // invite to interview in the next slice.
        $bidVendors = $job->bids()
            ->with('user:id,name,company')
            ->latest()
            ->get()
            ->unique('user_id')
            ->values();

        return view('interviews.manage', [
            'job' => $job,
            'config' => $config,
            'interviews' => $interviews,
            'bidVendors' => $bidVendors,
            'timezones' => $this->timezoneOptions(),
        ]);
    }

    /**
     * Create/update the interview configuration for a job.
     */
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

        // First save moves the config out of the raw 'setup' state so vendors
        // can begin scheduling; never downgrade a later lifecycle status.
        if (in_array($config->status, [null, '', InterviewConfig::STATUS_SETUP], true)) {
            $config->status = InterviewConfig::STATUS_OPEN;
        }

        $job->interviewConfig()->save($config);

        // Keep the job-level flag in sync so the buyer's job views reflect that
        // interviews are being organized.
        if (! $job->interviews_scheduled) {
            $job->forceFill(['interviews_scheduled' => true])->save();
        }

        return redirect()
            ->route('interviews.manage', $job)
            ->with('success', 'Interview settings saved.');
    }

    private function authorizeOwner(JobPosting $job): void
    {
        if ($job->user_id !== auth()->id()) {
            abort(403);
        }
    }

    /**
     * @return array<string, string>
     */
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
