<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\JobPosting;
use App\Models\VendorOpportunity;
use App\Models\VendorOpportunityInvitation;
use App\Models\VendorQuestionnaire;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index(WalletService $walletService): View
    {
        $user = auth()->user();
        $isVendorDashboard = $user?->isVendor() ?? false;

        $dashboardData = [
            'isVendorDashboard' => $isVendorDashboard,
        ];

        if ($isVendorDashboard) {
            $leadCount = 0;
            $unreadLeadCount = 0;
            $responseCount = 0;
            $activeLeadCount = 0;
            $acceptedResponseCount = 0;
            $bidSubmittedCount = 0;
            $declinedResponseCount = 0;

            if (Schema::hasTable('vendor_opportunity_invitations')) {
                $invitationQuery = VendorOpportunityInvitation::query()->where('vendor_id', $user->id);
                $leadCount += (int) $invitationQuery->count();
                $unreadLeadCount += (int) (clone $invitationQuery)->whereNull('opened_at')->count();
                $activeLeadCount += (int) (clone $invitationQuery)
                    ->whereIn('status', ['new', 'viewed', 'accepted'])
                    ->count();
                $responseCount += (int) (clone $invitationQuery)
                    ->whereNotIn('status', ['new', 'viewed'])
                    ->count();
                $acceptedResponseCount += (int) (clone $invitationQuery)
                    ->where('status', VendorOpportunityInvitation::STATUS_ACCEPTED)
                    ->count();
                $bidSubmittedCount += (int) (clone $invitationQuery)
                    ->where('status', VendorOpportunityInvitation::STATUS_BID_SUBMITTED)
                    ->count();
                $declinedResponseCount += (int) (clone $invitationQuery)
                    ->where('status', VendorOpportunityInvitation::STATUS_DECLINED)
                    ->count();
            }

            $legacyBidQuery = Bid::query()->where('user_id', $user->id);
            if (Schema::hasColumn('bids', 'vendor_opportunity_invitation_id')) {
                $legacyBidQuery->whereNull('vendor_opportunity_invitation_id');
            }

            $leadCount += (int) $legacyBidQuery->count();
            $activeLeadCount += (int) (clone $legacyBidQuery)->where('status', 'pending')->count();
            $responseCount += (int) (clone $legacyBidQuery)
                ->where(function ($query) {
                    $query->where('status', '!=', 'pending');

                    if (Schema::hasColumn('bids', 'vendor_response_status')) {
                        $query->orWhere('vendor_response_status', '!=', 'pending');
                    }
                })
                ->count();
            $acceptedResponseCount += (int) (clone $legacyBidQuery)
                ->where(function (Builder $query) {
                    $query->where('status', 'accepted');

                    if (Schema::hasColumn('bids', 'vendor_response_status')) {
                        $query->orWhere('vendor_response_status', 'accepted');
                    }
                })
                ->count();
            $declinedResponseCount += (int) (clone $legacyBidQuery)
                ->where(function (Builder $query) {
                    $query->where('status', 'declined');

                    if (Schema::hasColumn('bids', 'vendor_response_status')) {
                        $query->orWhere('vendor_response_status', 'declined');
                    }
                })
                ->count();

            $profileSummary = $this->vendorProfileSummary($user);

            $questionnaireDraft = null;
            $questionnaireDraftCount = 0;
            $questionnaireSubmittedCount = 0;
            if (Schema::hasTable('vendor_questionnaires')) {
                $questionnaireDraftCount = (int) VendorQuestionnaire::where('vendor_id', $user->id)
                    ->where('status', 'draft')->count();
                $questionnaireSubmittedCount = (int) VendorQuestionnaire::where('vendor_id', $user->id)
                    ->where('status', 'submitted')->count();
                $questionnaireDraft = VendorQuestionnaire::with('jobPosting')
                    ->where('vendor_id', $user->id)
                    ->where('status', 'draft')
                    ->latest('updated_at')
                    ->first();
            }

            // Tier-A vs Tier-B breakdown so vendors can see what's premium in their pipeline.
            $tierACount = 0;
            $tierBCount = 0;
            if (Schema::hasTable('vendor_opportunity_invitations') && Schema::hasTable('vendor_opportunities')) {
                $tierACount = (int) VendorOpportunityInvitation::query()
                    ->where('vendor_id', $user->id)
                    ->whereIn('status', ['new', 'viewed', 'accepted'])
                    ->whereHas('opportunity', fn ($q) => $q->where('lead_tier', 'a'))
                    ->count();
                $tierBCount = (int) VendorOpportunityInvitation::query()
                    ->where('vendor_id', $user->id)
                    ->whereIn('status', ['new', 'viewed', 'accepted'])
                    ->whereHas('opportunity', fn ($q) => $q->where('lead_tier', 'b'))
                    ->count();
            }

            // Won / lost outcomes — drives the new outcome tracker tile.
            $wonCount = (int) Bid::query()
                ->where('user_id', $user->id)
                ->whereNotNull('hired_at')
                ->count();
            $lostCount = (int) Bid::query()
                ->where('user_id', $user->id)
                ->where('status', 'rejected')
                ->whereNull('hired_at')
                ->count();
            $totalDecidedBids = $wonCount + $lostCount;
            $winRatePct = $totalDecidedBids > 0
                ? (int) round(100 * $wonCount / $totalDecidedBids)
                : 0;

            // "Action Required" rollup — pulls items needing immediate vendor attention.
            $acceptedNoBidCount = 0;
            if (Schema::hasTable('vendor_opportunity_invitations')) {
                $acceptedNoBidCount = (int) VendorOpportunityInvitation::query()
                    ->where('vendor_id', $user->id)
                    ->where('status', VendorOpportunityInvitation::STATUS_ACCEPTED)
                    ->whereNull('bid_submitted_at')
                    ->count();
            }
            $walletBalance = $walletService->getBalance($user);
            $lowCreditsThreshold = 500;

            $dashboardData += [
                'vendorProfileCompletion' => $profileSummary['completion'],
                'vendorProfileMissingCount' => $profileSummary['missing_count'],
                'vendorProfileMessage' => $profileSummary['message'],
                'vendorProfileCta' => $profileSummary['cta'],
                'vendorLeadCount' => $leadCount,
                'vendorUnreadLeadCount' => $unreadLeadCount,
                'vendorActiveLeadCount' => $activeLeadCount,
                'vendorLeadMessage' => $leadCount > 0
                    ? ($unreadLeadCount > 0
                        ? $unreadLeadCount . ' new lead' . ($unreadLeadCount === 1 ? '' : 's') . ' waiting for review'
                        : $activeLeadCount . ' active lead' . ($activeLeadCount === 1 ? '' : 's') . ' in your pipeline')
                    : 'Your invited leads will appear here as soon as GASQ routes matching projects.',
                'vendorTierACount' => $tierACount,
                'vendorTierBCount' => $tierBCount,
                'vendorResponseCount' => $responseCount,
                'vendorAcceptedResponseCount' => $acceptedResponseCount,
                'vendorBidSubmittedCount' => $bidSubmittedCount,
                'vendorDeclinedResponseCount' => $declinedResponseCount,
                'vendorResponseMessage' => $responseCount > 0
                    ? 'Accepted: ' . $acceptedResponseCount . ' · Submitted bids: ' . $bidSubmittedCount . ' · Declined: ' . $declinedResponseCount
                    : 'No vendor responses recorded yet. New acceptances and bid activity will show here.',
                'vendorWalletBalance' => $walletBalance,
                'vendorQuestionnaireDraftCount' => $questionnaireDraftCount,
                'vendorQuestionnaireSubmittedCount' => $questionnaireSubmittedCount,
                'vendorQuestionnaireDraft' => $questionnaireDraft,
                // New for beta:
                'vendorWonCount' => $wonCount,
                'vendorLostCount' => $lostCount,
                'vendorWinRatePct' => $winRatePct,
                'vendorAcceptedNoBidCount' => $acceptedNoBidCount,
                'vendorLowCredits' => $walletBalance < $lowCreditsThreshold,
                'vendorActionRequiredCount' => $acceptedNoBidCount + $questionnaireDraftCount + (($walletBalance < $lowCreditsThreshold) ? 1 : 0),
            ];
        } else {
            $dashboardData += $this->buyerDashboardData($user, $walletService);
        }

        return view($isVendorDashboard ? 'home-vendor' : 'home', $dashboardData);
    }

    /**
     * Buyer-side dashboard data: active jobs, vendor activity, qualification status,
     * and next-action prompts pulled from the jobs the buyer owns.
     *
     * @return array<string, mixed>
     */
    private function buyerDashboardData($user, WalletService $walletService): array
    {
        $jobs = JobPosting::query()
            ->where('user_id', $user->id)
            ->with([
                'vendorOpportunity.invitations' => fn ($q) => $q->whereIn('status', ['accepted', 'bid_submitted'])->with('vendor'),
            ])
            ->latest('created_at')
            ->take(10)
            ->get();

        $activeJobs = $jobs->filter(fn ($j) => in_array((string) $j->status, ['open', 'pending', 'active'], true));
        $hiredJobs = $jobs->filter(fn ($j) => $j->status === 'hired' || $j->hired_bid_id !== null);

        $jobIds = $jobs->pluck('id')->all();

        // Aggregate vendor activity across the buyer's recent jobs.
        $bidsReceived = 0;
        $vendorsAccepted = 0;
        $pendingInterviews = 0;
        if (! empty($jobIds)) {
            $bidsReceived = (int) Bid::query()
                ->whereIn('job_posting_id', $jobIds)
                ->where('amount', '>', 0)
                ->count();

            if (Schema::hasTable('vendor_opportunities') && Schema::hasTable('vendor_opportunity_invitations')) {
                $opportunityIds = VendorOpportunity::query()
                    ->whereIn('job_posting_id', $jobIds)
                    ->pluck('id')
                    ->all();
                if (! empty($opportunityIds)) {
                    $vendorsAccepted = (int) VendorOpportunityInvitation::query()
                        ->whereIn('vendor_opportunity_id', $opportunityIds)
                        ->whereIn('status', ['accepted', 'bid_submitted'])
                        ->count();
                    $pendingInterviews = (int) VendorOpportunityInvitation::query()
                        ->whereIn('vendor_opportunity_id', $opportunityIds)
                        ->where('status', 'bid_submitted')
                        ->count();
                }
            }
        }

        // Best (worst) lead tier across all open jobs so we can surface qualification status.
        $latestTier = null;
        $heldJobCount = 0;
        if (Schema::hasTable('vendor_opportunities') && ! empty($jobIds)) {
            $opps = VendorOpportunity::query()
                ->whereIn('job_posting_id', $jobIds)
                ->get();
            $latestTier = $opps->sortBy(fn ($o) => $o->created_at)->last()?->lead_tier;
            $heldJobCount = (int) $opps->where('lead_tier', 'c')->count();
        }

        // Compose a single "next action" message based on what's most important right now.
        if ($heldJobCount > 0) {
            $nextAction = [
                'tone' => 'warn',
                'message' => $heldJobCount . ' job' . ($heldJobCount === 1 ? ' is' : 's are') . ' Pending Qualification — '
                    . ($heldJobCount === 1 ? 'it is' : 'they are') . ' not yet visible to vendors. '
                    . 'Update your questionnaire to qualify and release ' . ($heldJobCount === 1 ? 'it' : 'them') . '.',
                'cta' => 'Update Questionnaire',
                'href' => $activeJobs->isNotEmpty() ? route('jobs.edit', $activeJobs->first()) : route('jobs.index'),
            ];
        } elseif ($pendingInterviews > 0) {
            $nextAction = [
                'tone' => 'success',
                'message' => $pendingInterviews . ' vendor' . ($pendingInterviews === 1 ? ' has' : 's have') . ' submitted bids — schedule interviews.',
                'cta' => 'Schedule Interviews',
                'href' => $activeJobs->isNotEmpty() ? route('jobs.show', $activeJobs->first()) : route('jobs.index'),
            ];
        } elseif ($vendorsAccepted > 0) {
            $nextAction = [
                'tone' => 'info',
                'message' => $vendorsAccepted . ' vendor' . ($vendorsAccepted === 1 ? '' : 's') . ' accepted your offer — bids will arrive shortly.',
                'cta' => 'View Active Jobs',
                'href' => route('jobs.index'),
            ];
        } elseif ($activeJobs->isEmpty()) {
            $nextAction = [
                'tone' => 'neutral',
                'message' => 'No active jobs yet. Post a job to start receiving qualified vendor responses.',
                'cta' => 'Post a Job',
                'href' => route('post-job.index'),
            ];
        } else {
            $nextAction = [
                'tone' => 'neutral',
                'message' => 'Your jobs are live — vendors are reviewing.',
                'cta' => 'View Active Jobs',
                'href' => route('jobs.index'),
            ];
        }

        return [
            'buyerActiveJobs' => $activeJobs,
            'buyerHiredJobsCount' => $hiredJobs->count(),
            'buyerBidsReceived' => $bidsReceived,
            'buyerVendorsAccepted' => $vendorsAccepted,
            'buyerPendingInterviews' => $pendingInterviews,
            'buyerLatestTier' => $latestTier,
            'buyerHeldJobCount' => $heldJobCount,
            'buyerNextAction' => $nextAction,
            'buyerWalletBalance' => $walletService->getBalance($user),
        ];
    }

    private function vendorProfileCompletion($user): int
    {
        $profile = $user->vendorProfile;
        $capability = $user->vendorCapability;

        $checks = [
            filled($user->name),
            filled($user->email),
            filled($user->phone),
            filled($user->company),
            filled($profile?->company_name),
            filled($profile?->description),
            filled($profile?->address),
            is_array($profile?->capabilities) && count($profile->capabilities) > 0,
            is_array($capability?->service_areas) && count($capability->service_areas) > 0,
            is_array($capability?->core_competencies) && count($capability->core_competencies) > 0,
            filled($capability?->business_license_number) || (bool) $capability?->license_verified,
            (bool) $capability?->insurance_verified,
        ];

        $completed = count(array_filter($checks));

        return (int) round(($completed / max(count($checks), 1)) * 100);
    }

    /**
     * @return array{completion: int, missing_count: int, message: string, cta: string}
     */
    private function vendorProfileSummary($user): array
    {
        $completion = $this->vendorProfileCompletion($user);

        $profile = $user->vendorProfile;
        $capability = $user->vendorCapability;

        $missingItems = array_filter([
            filled($user->phone) ? null : 'phone number',
            filled($user->company) ? null : 'company name',
            filled($profile?->description) ? null : 'company description',
            filled($profile?->address) ? null : 'business address',
            (is_array($capability?->service_areas) && count($capability->service_areas) > 0) ? null : 'service areas',
            (is_array($capability?->core_competencies) && count($capability->core_competencies) > 0) ? null : 'core competencies',
            ((bool) $capability?->license_verified || filled($capability?->business_license_number)) ? null : 'license details',
            (bool) $capability?->insurance_verified ? null : 'insurance verification',
        ]);

        $missingCount = count($missingItems);

        if ($completion >= 100) {
            return [
                'completion' => $completion,
                'missing_count' => 0,
                'message' => 'Your profile is fully complete and ready to support lead matching.',
                'cta' => 'View profile',
            ];
        }

        return [
            'completion' => $completion,
            'missing_count' => $missingCount,
            'message' => $missingCount > 0
                ? 'Complete the remaining ' . $missingCount . ' profile item' . ($missingCount === 1 ? '' : 's') . ' to improve your matching quality.'
                : 'Add a few more profile details to strengthen your lead visibility.',
            'cta' => $completion >= 70 ? 'Update profile' : 'Complete profile',
        ];
    }
}
