<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\JobPosting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VendorEstimateSubmission;
use Carbon\Carbon;
use Illuminate\View\View;

/**
 * Internal admin performance dashboard. Every figure is computed live from real
 * platform data. Nothing here is public — it renders behind auth+admin.
 *
 * "Value won" uses awarded_value (frozen at hire); older awards fall back to the
 * job's budget estimate, so historical figures are directional, not accounting.
 */
class AdminDashboardController extends Controller
{
    /** SQL for the best-available contract value of an awarded job. */
    private const VALUE_EXPR = 'COALESCE(awarded_value, budget_max, budget_min, 0)';

    public function index(): View
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();
        $since90 = $now->copy()->subDays(90);

        $wonValue = fn ($query) => (float) $query->selectRaw('SUM(' . self::VALUE_EXPR . ') as v')->value('v');
        $budgetValue = fn ($query) => (float) $query->selectRaw('SUM(COALESCE(budget_max, budget_min, 0)) as v')->value('v');

        // ---- Executive summary ----
        $contractsWonTotal = JobPosting::whereNotNull('hired_at')->count();
        $valueWonTotal = $wonValue(JobPosting::whereNotNull('hired_at'));

        $executive = [
            'value_won_total' => $valueWonTotal,
            'value_won_year' => $wonValue(JobPosting::whereNotNull('hired_at')->where('hired_at', '>=', $startOfYear)),
            'pipeline_value' => $budgetValue(JobPosting::whereNull('hired_at')->whereNull('closed_at')),
            'avg_contract_value' => $contractsWonTotal > 0 ? $valueWonTotal / $contractsWonTotal : 0.0,
            'contracts_won_total' => $contractsWonTotal,
            'contracts_won_month' => JobPosting::whereNotNull('hired_at')->where('hired_at', '>=', $startOfMonth)->count(),
        ];

        // ---- Lead funnel (count + value per stage) ----
        $jobsWithBids = Bid::query()->distinct()->pluck('job_posting_id');
        $acceptedJobIds = Bid::where('vendor_response_status', 'accepted')->distinct()->pluck('job_posting_id');
        $awardPending = JobPosting::whereIn('id', $acceptedJobIds)->whereNull('hired_at')->whereNull('closed_at');

        $funnel = [
            ['label' => 'New Leads Received', 'count' => JobPosting::count(), 'value' => $budgetValue(JobPosting::query())],
            ['label' => 'Vendor-Engaged (Qualified)', 'count' => $jobsWithBids->count(), 'value' => $budgetValue(JobPosting::whereIn('id', $jobsWithBids))],
            ['label' => 'Cost to Protect Submitted', 'count' => VendorEstimateSubmission::count(), 'value' => null],
            ['label' => 'Award Pending', 'count' => (clone $awardPending)->count(), 'value' => $budgetValue(clone $awardPending)],
            ['label' => 'Won', 'count' => $contractsWonTotal, 'value' => $valueWonTotal],
        ];

        // ---- Buyer statistics ----
        $buyers = [
            'total' => User::where('user_type', 'buyer')->count(),
            'new_month' => User::where('user_type', 'buyer')->where('created_at', '>=', $startOfMonth)->count(),
            'avg_cycle_days' => (float) JobPosting::whereNotNull('hired_at')
                ->selectRaw('AVG(DATEDIFF(hired_at, created_at)) as d')->value('d'),
        ];

        // ---- Vendor statistics ----
        $accepted = Bid::where('vendor_response_status', 'accepted')->count();
        $declined = Bid::whereIn('vendor_response_status', ['declined', 'rejected'])->count();
        $responded = $accepted + $declined;

        $vendors = [
            'total' => User::where('user_type', 'vendor')->count(),
            'active_90d' => Bid::where('created_at', '>=', $since90)->distinct()->count('user_id'),
            'selected' => Bid::whereNotNull('hired_at')->distinct()->count('user_id'),
            'accept_rate' => $responded > 0 ? $accepted / $responded * 100 : 0.0,
            'decline_rate' => $responded > 0 ? $declined / $responded * 100 : 0.0,
        ];

        // ---- Procurement statistics ----
        $procurement = [
            'appraisals' => VendorEstimateSubmission::count(),
            'offers_sent' => Bid::count(),
            'offers_accepted' => $accepted,
            'offers_declined' => $declined,
        ];

        // ---- Activity ----
        $activity = [
            'new_leads_today' => JobPosting::where('created_at', '>=', $startOfDay)->count(),
            'ready_for_award' => (clone $awardPending)->count(),
        ];

        // ---- Revenue (credits; dollar revenue needs Stripe amounts persisted) ----
        $revenue = [
            'credits_sold' => (int) Transaction::whereIn('type', ['purchase', 'subscription'])->sum('tokens_change'),
            'purchases' => Transaction::where('type', 'purchase')->count(),
            'subscription_payments' => Transaction::where('type', 'subscription')->count(),
        ];

        // ---- Trend: awards over the last 6 months ----
        $awardsTrend = JobPosting::whereNotNull('hired_at')
            ->where('hired_at', '>=', $now->copy()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(hired_at, '%Y-%m') as ym, COUNT(*) as c, SUM(" . self::VALUE_EXPR . ') as v')
            ->groupBy('ym')->orderBy('ym')->get();

        return view('admin.dashboard', compact(
            'executive', 'funnel', 'buyers', 'vendors', 'procurement', 'activity', 'revenue', 'awardsTrend', 'now'
        ));
    }
}
