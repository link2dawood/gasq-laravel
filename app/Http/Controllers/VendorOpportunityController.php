<?php

namespace App\Http\Controllers;

use App\Models\VendorOpportunityInvitation;
use App\Services\VendorOpportunityManager;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorOpportunityController extends Controller
{
    public function __construct(
        private readonly VendorOpportunityManager $manager,
        private readonly WalletService $walletService,
    ) {}

    public function show(Request $request, VendorOpportunityInvitation $invitation): View|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        return $this->renderOpportunityPage($request, $invitation, true);
    }

    public function manage(Request $request, VendorOpportunityInvitation $invitation): View|RedirectResponse
    {
        return $this->renderOpportunityPage($request, $invitation, false);
    }

    private function renderOpportunityPage(Request $request, VendorOpportunityInvitation $invitation, bool $allowGuestRedirect): View|RedirectResponse
    {
        $invitation->load([
            'opportunity.jobPosting.user',
            'opportunity.invitations.vendor',
            'vendor',
            'bid',
        ]);

        $this->manager->markInvitationOpened($invitation);

        if (! $request->user()) {
            if ($allowGuestRedirect) {
                return redirect()->guest(route('login'))->with('info', 'Sign in to review and respond to this GASQ opportunity.');
            }

            abort(403);
        }

        if ($request->user()->isAdmin() === false && (int) $request->user()->id !== (int) $invitation->vendor_id) {
            abort(403);
        }

        $this->manager->markInvitationViewed($invitation, $request->user());

        $opportunity = $invitation->opportunity;
        $job = $opportunity->jobPosting;
        $buyer = $job->user;
        $questionnaire = is_array($job->questionnaire_data) ? $job->questionnaire_data : [];
        $respondedCount = $opportunity->respondedInvitations()->count();
        $acceptedCount = $opportunity->acceptedInvitations()->count();
        $acceptedPathOpen = $invitation->buyerDetailsUnlocked() && ! $invitation->bidWindowExpired() && $invitation->status !== VendorOpportunityInvitation::STATUS_DECLINED;

        return view('vendor-opportunities.show', [
            'invitation' => $invitation->fresh([
                'opportunity.jobPosting.user',
                'opportunity.invitations.vendor',
                'vendor',
                'bid',
            ]),
            'opportunity' => $opportunity,
            'job' => $job,
            'buyer' => $buyer,
            'questionnaire' => $questionnaire,
            'respondedCount' => $respondedCount,
            'acceptedCount' => $acceptedCount,
            'walletBalance' => $this->walletService->getBalance($request->user()),
            'buyerDetailsUnlocked' => $invitation->buyerDetailsUnlocked(),
            'acceptedPathOpen' => $acceptedPathOpen,
            'fullBuyerEmail' => $buyer->email,
            'fullBuyerPhone' => $buyer->phone,
            'maskedBuyerEmail' => $this->maskEmail((string) $buyer->email),
            'maskedBuyerPhone' => $this->maskPhone((string) $buyer->phone),
        ]);
    }

    public function accept(Request $request, VendorOpportunityInvitation $invitation): RedirectResponse
    {
        $this->authorizeVendor($request, $invitation);

        $request->validate([
            'invitation_id' => ['nullable'],
        ]);

        $this->manager->acceptInvitation($invitation, $request->user());

        return back()->with('success', 'Opportunity accepted. Buyer details are now unlocked and your 24-hour bid window has started.');
    }

    public function decline(Request $request, VendorOpportunityInvitation $invitation): RedirectResponse
    {
        $this->authorizeVendor($request, $invitation);

        $validated = $request->validate([
            'decline_reason' => ['required', Rule::in([
                'outside_service_area',
                'staffing_unavailable',
                'pricing_too_low',
                'not_interested',
                'other',
            ])],
            'decline_reason_other' => ['nullable', 'required_if:decline_reason,other', 'string', 'max:1000'],
        ]);

        $this->manager->declineInvitation(
            $invitation,
            $request->user(),
            $validated['decline_reason'],
            $validated['decline_reason_other'] ?? null
        );

        return back()->with('success', 'Opportunity declined.');
    }

    public function submitBid(Request $request, VendorOpportunityInvitation $invitation): RedirectResponse
    {
        $this->authorizeVendor($request, $invitation);

        $validated = $request->validate([
            'hourly_bill_rate' => ['required', 'numeric', 'min:0'],
            'weekly_price' => ['required', 'numeric', 'min:0'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['required', 'numeric', 'min:0'],
            'staffing_plan' => ['required', 'string', 'max:5000'],
            'start_availability' => ['required', 'string', 'max:255'],
            'vendor_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->manager->submitBid($invitation, $request->user(), $validated);

        return back()->with('success', 'Bid submitted successfully.');
    }

    private function authorizeVendor(Request $request, VendorOpportunityInvitation $invitation): void
    {
        if (! $request->user()?->isVendor()) {
            abort(403);
        }

        if ((int) $request->user()->id !== (int) $invitation->vendor_id) {
            abort(403);
        }

        $invitation->loadMissing('opportunity');
        if ($invitation->opportunity?->isClosed()) {
            throw ValidationException::withMessages([
                'opportunity' => 'This opportunity is closed.',
            ]);
        }
    }

    private function maskEmail(string $email): string
    {
        if ($email === '' || ! str_contains($email, '@')) {
            return 'Not provided';
        }

        [$name, $domain] = explode('@', $email, 2);
        $maskedName = substr($name, 0, 1) . str_repeat('*', max(4, strlen($name) - 1));
        $domainParts = explode('.', $domain);
        $tld = count($domainParts) > 1 ? array_pop($domainParts) : null;
        $label = implode('.', $domainParts);
        $maskedDomain = substr($label, 0, 1) . str_repeat('*', max(4, strlen($label) - 1));

        return $maskedName . '@' . $maskedDomain . ($tld ? '.' . $tld : '');
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) >= 10) {
            return sprintf('(%s) ***-****', substr($digits, -10, 3));
        }

        return 'Not provided';
    }
}
