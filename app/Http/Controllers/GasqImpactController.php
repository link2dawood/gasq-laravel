<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Models\User;
use App\Models\VendorEstimateSubmission;
use Illuminate\View\View;

/**
 * GASQ Impact dashboard — the platform's own financial-impact numbers, in USD,
 * computed live from real appraisals/jobs. Capital-recovery figures use the GASQ
 * Cost to Protect methodology (outsourcing recovers a share of in-house cost).
 */
class GasqImpactController extends Controller
{
    /** Best-available contract/appraisal value per job. */
    private const VALUE_EXPR = 'COALESCE(awarded_value, budget_max, budget_min, 0)';

    public function index(): View
    {
        // Vendor (outsourced) cost is this fraction of in-house cost; the recovered
        // capital is the remainder, expressed per outsourced dollar: (1-f)/f.
        $f = (float) config('budget_calculator.vendor_discount_factor', 0.70);
        $recoveryMult = $f > 0 ? (1 - $f) / $f : 0.0;

        $totalValue = (float) JobPosting::selectRaw('SUM(' . self::VALUE_EXPR . ') as v')->value('v');
        $maxValue = (float) JobPosting::selectRaw('MAX(' . self::VALUE_EXPR . ') as v')->value('v');
        $awardedValue = (float) JobPosting::whereNotNull('hired_at')
            ->selectRaw('SUM(' . self::VALUE_EXPR . ') as v')->value('v');

        $jobsCount = JobPosting::count();
        $estimatesCount = VendorEstimateSubmission::count();
        $appraisals = $jobsCount + $estimatesCount;
        $awardedCount = JobPosting::whereNotNull('hired_at')->count();

        $capitalRecovery = $totalValue * $recoveryMult;

        // Standard annual coverage hours per assigned professional (24×7 ≈ 8,736).
        $annualHours = (int) round(((float) JobPosting::selectRaw('SUM(COALESCE(guards_per_shift, 1)) as g')->value('g')) * 8736);

        $impact = [
            'capital_recovery_total' => $capitalRecovery,
            'capital_recovery_avg' => $appraisals > 0 ? $capitalRecovery / $appraisals : 0.0,
            'capital_recovery_highest' => $maxValue * $recoveryMult,
            'client_savings_awarded' => $awardedValue * $recoveryMult,
            'contract_value_total' => $totalValue,
            'contracts_awarded' => $awardedCount,
        ];

        $operational = [
            'appraisals' => $appraisals,
            'billable_hours_annual' => $annualHours,
            'billable_hours_monthly' => (int) round($annualHours / 12),
            'billable_hours_weekly' => (int) round($annualHours / 52),
            'buyers' => User::where('user_type', 'buyer')->count(),
            'vendors' => User::where('user_type', 'vendor')->count(),
            'states' => User::whereNotNull('state')->where('state', '!=', '')->distinct()->count('state'),
        ];

        return view('impact', compact('impact', 'operational'));
    }
}
