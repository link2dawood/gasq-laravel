<?php

namespace App\Http\Controllers;

use App\Models\VendorOpportunity;
use App\Models\VendorOpportunityInvitation;
use App\Services\VendorOpportunityManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminVendorOpportunityController extends Controller
{
    public function __construct(
        private readonly VendorOpportunityManager $manager,
    ) {}

    public function index(): View
    {
        $opportunities = VendorOpportunity::query()
            ->with(['jobPosting.user', 'invitations.vendor', 'invitations.bid'])
            ->latest()
            ->paginate(20);

        return view('admin.vendor-opportunities.index', compact('opportunities'));
    }

    public function show(VendorOpportunity $opportunity): View
    {
        $opportunity->load([
            'jobPosting.user',
            'invitations.vendor',
            'invitations.bid',
        ]);

        return view('admin.vendor-opportunities.show', compact('opportunity'));
    }

    public function approve(VendorOpportunity $opportunity): RedirectResponse
    {
        $this->manager->approveAndSend($opportunity);

        return back()->with('success', 'B-tier opportunity approved and invitations sent.');
    }

    public function close(VendorOpportunity $opportunity): RedirectResponse
    {
        $this->manager->closeOpportunity($opportunity);

        return back()->with('success', 'Opportunity closed.');
    }

    public function award(VendorOpportunityInvitation $invitation): RedirectResponse
    {
        $this->manager->awardInvitation($invitation);

        return redirect()
            ->route('admin.vendor-opportunities.show', $invitation->vendor_opportunity_id)
            ->with('success', 'Winning vendor recorded and other invited vendors were updated.');
    }
}
