<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\VendorOpportunityInvitation;
use App\Services\WalletService;
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

            if (Schema::hasTable('vendor_opportunity_invitations')) {
                $invitationQuery = VendorOpportunityInvitation::query()->where('vendor_id', $user->id);
                $leadCount += (int) $invitationQuery->count();
                $unreadLeadCount += (int) (clone $invitationQuery)->whereNull('opened_at')->count();
                $responseCount += (int) (clone $invitationQuery)
                    ->whereNotIn('status', ['new', 'viewed'])
                    ->count();
            }

            $legacyBidQuery = Bid::query()->where('user_id', $user->id);
            if (Schema::hasColumn('bids', 'vendor_opportunity_invitation_id')) {
                $legacyBidQuery->whereNull('vendor_opportunity_invitation_id');
            }

            $leadCount += (int) $legacyBidQuery->count();
            $responseCount += (int) (clone $legacyBidQuery)
                ->where(function ($query) {
                    $query->where('status', '!=', 'pending');

                    if (Schema::hasColumn('bids', 'vendor_response_status')) {
                        $query->orWhere('vendor_response_status', '!=', 'pending');
                    }
                })
                ->count();

            $dashboardData += [
                'vendorProfileCompletion' => $this->vendorProfileCompletion($user),
                'vendorLeadCount' => $leadCount,
                'vendorUnreadLeadCount' => $unreadLeadCount,
                'vendorResponseCount' => $responseCount,
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
}
