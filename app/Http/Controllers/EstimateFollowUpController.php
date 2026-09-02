<?php

namespace App\Http\Controllers;

use App\Models\EstimateFollowUp;
use App\Support\Funnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EstimateFollowUpController extends Controller
{
    public function open(string $token): Response
    {
        $followUp = EstimateFollowUp::where('tracking_token', $token)->firstOrFail();
        if (! $followUp->opened_at) {
            $followUp->update(['opened_at' => now()]);
            Funnel::record(Funnel::ESTIMATE_FOLLOW_UP_OPENED, ['estimate_id' => $followUp->id], $followUp->user_id);
        }
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='), 200, ['Content-Type' => 'image/gif']);
    }

    public function review(string $token): RedirectResponse
    {
        $followUp = EstimateFollowUp::where('tracking_token', $token)->firstOrFail();
        return redirect()->route('instant-estimator.index')->with('report_payload', ['type' => 'instant-estimator', 'scenario' => $followUp->scenario, 'result' => $followUp->result]);
    }

    public function convert(Request $request, string $token): RedirectResponse
    {
        $followUp = EstimateFollowUp::where('tracking_token', $token)->firstOrFail();
        if (! $request->user()) return redirect()->guest(route('estimate-follow-ups.convert', $token));
        if ($followUp->user_id && $followUp->user_id !== $request->user()->id) abort(403);

        $meta = (array) data_get($followUp->scenario, 'meta', []);
        $result = (array) $followUp->result;
        $prefill = [
            'service_type' => $meta['serviceType'] ?? null, 'location' => $meta['location'] ?? null,
            'contact_name' => $meta['requesterName'] ?? null, 'contact_job_title' => $meta['contactJobTitle'] ?? null,
            'organization_name' => $meta['company'] ?? null, 'property_site_name' => $meta['propertySiteName'] ?? null,
            'contact_email' => $meta['requesterEmail'] ?? $followUp->email, 'contact_phone' => $meta['requesterPhone'] ?? null,
            'business_address' => $meta['location'] ?? null, 'property_type' => $meta['propertyType'] ?? null,
            'baseline_wage' => $meta['selectedRate'] ?? null, 'hours_per_day' => $meta['hoursPerDay'] ?? null,
            'days_per_week' => $meta['daysPerWeek'] ?? null, 'weeks_per_year' => $meta['weeks'] ?? null,
            'staff_per_shift' => $meta['staffPerShift'] ?? null, 'annual_budget' => data_get($result, 'outsourcedAnnual'),
            'monthly_budget' => data_get($result, 'outsourcedMonthly'), 'hourly_budget' => data_get($result, 'outsourcedHourly'),
        ];
        $request->session()->put('job_posting_estimator_prefill', array_filter($prefill, fn ($v) => $v !== null && $v !== ''));
        $followUp->update(['cta_clicked_at' => $followUp->cta_clicked_at ?? now(), 'opportunity_started_at' => now(), 'status' => 'opportunity_started']);
        Funnel::record(Funnel::ESTIMATE_FOLLOW_UP_CTA_CLICKED, ['estimate_id' => $followUp->id], $request->user()->id);
        return redirect()->route('jobs.create', ['step' => 'details']);
    }
}
