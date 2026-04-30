<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\VendorOpportunityInvitation;
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
                'vendorResponseCount' => $responseCount,
                'vendorAcceptedResponseCount' => $acceptedResponseCount,
                'vendorBidSubmittedCount' => $bidSubmittedCount,
                'vendorDeclinedResponseCount' => $declinedResponseCount,
                'vendorResponseMessage' => $responseCount > 0
                    ? 'Accepted: ' . $acceptedResponseCount . ' · Submitted bids: ' . $bidSubmittedCount . ' · Declined: ' . $declinedResponseCount
                    : 'No vendor responses recorded yet. New acceptances and bid activity will show here.',
                'vendorWalletBalance' => $walletService->getBalance($user),
            ];
        }

        return view($isVendorDashboard ? 'home-vendor' : 'home', $dashboardData);
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
