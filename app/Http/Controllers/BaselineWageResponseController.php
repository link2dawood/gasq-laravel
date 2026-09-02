<?php

namespace App\Http\Controllers;

use App\Models\BaselineWageResponse;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BaselineWageResponseController extends Controller
{
    public const REASONS = ['recruiting_difficulty','employee_retention','local_wage_competition','armed_officer_requirements','licensing_requirements','experience_requirements','shift_differential','overnight_coverage','high_risk_environment','supervisor_requirements','specialized_training','clearance_requirements','transportation_requirements','other'];

    public function requestAdjustment(Request $request, JobPosting $job): RedirectResponse
    {
        abort_unless($request->user()?->isVendor(), 403);
        $data = $request->validate(['recommended_baseline_wage' => ['required','numeric','min:0.01','max:500'], 'reasons' => ['required','array','min:1'], 'reasons.*' => ['in:' . implode(',', self::REASONS)], 'explanation' => ['required','string','min:20','max:4000']]);
        BaselineWageResponse::updateOrCreate(['job_posting_id' => $job->id, 'vendor_id' => $request->user()->id], ['buyer_baseline_wage' => $job->baseline_wage, 'recommended_baseline_wage' => $data['recommended_baseline_wage'], 'status' => 'adjustment_requested', 'reasons' => $data['reasons'], 'explanation' => $data['explanation'], 'resolution' => null, 'resolved_by' => null, 'resolved_at' => null, 'responded_at' => now()]);
        return back()->with('success', 'Baseline Wage Adjustment Request sent to the buyer. The original buyer baseline remains unchanged until the buyer responds.');
    }

    public function resolve(Request $request, BaselineWageResponse $response): RedirectResponse
    {
        abort_unless($response->jobPosting->user_id === $request->user()?->id, 403);
        $data = $request->validate(['resolution' => ['required','in:approved,rejected'], 'buyer_note' => ['nullable','string','max:4000']]);
        $response->update(['resolution' => $data['resolution'], 'buyer_note' => $data['buyer_note'] ?? null, 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);
        return back()->with('success', 'Vendor baseline wage adjustment request ' . $data['resolution'] . '.');
    }
}
