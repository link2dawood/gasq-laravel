<?php

namespace App\Http\Controllers;

use App\Models\EstimateInvitation;
use App\Models\PrivateEstimate;
use App\Services\PrivateEstimate\BuyerEmailOtpService;
use App\Services\PrivateEstimate\EstimateComparisonReport;
use App\Services\PrivateEstimate\EstimateInvitationService;
use App\Services\PrivateEstimate\PrivateEstimateService;
use App\Support\PrivateEstimate as Flow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The buyer's side of a private estimate. No account, no password: the buyer
 * arrives on an invitation link and proves the email with a six-digit code.
 *
 * Two credentials, never mixed (spec 41): the link identifies the invitation,
 * the code authorises the session. Holding the link alone shows nothing beyond
 * who invited them.
 */
class PrivateEstimateBuyerController extends Controller
{
    public function __construct(
        private EstimateInvitationService $invitations,
        private BuyerEmailOtpService $otp,
        private PrivateEstimateService $estimates,
        private EstimateComparisonReport $comparison,
    ) {}

    /** Landing page for an invitation link (spec 4). */
    public function show(Request $request, string $token): View
    {
        ['invitation' => $invitation, 'reason' => $reason] = $this->invitations->resolve($token);

        if (! $invitation) {
            return view('private-estimates.buyer.unavailable', ['reason' => $reason]);
        }

        $this->invitations->markOpened($invitation);
        $estimate = $invitation->privateEstimate;

        // Already verified in this browser? Go straight to the portal.
        if ($this->sessionVerified($request, $estimate)) {
            return view('private-estimates.buyer.portal', [
                'estimate' => $estimate,
                'token' => $token,
                'comparison' => $this->comparisonFor($estimate),
            ]);
        }

        return view('private-estimates.buyer.invitation', [
            'estimate' => $estimate,
            'invitation' => $invitation,
            'token' => $token,
            'vendorName' => $this->vendorName($estimate),
        ]);
    }

    /** Email a six-digit code to the invited address (spec 5). */
    public function sendCode(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->usableInvitation($token);
        if (! $invitation) {
            return redirect()->route('private-estimates.buyer.show', $token);
        }

        $result = $this->otp->send($invitation->privateEstimate);

        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        return back()->with('status', 'We emailed a six-digit code to '.$this->maskEmail($invitation->invited_email).'.');
    }

    /** Check the code and open the portal for this browser session. */
    public function verifyCode(Request $request, string $token): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:10']]);

        $invitation = $this->usableInvitation($token);
        if (! $invitation) {
            return redirect()->route('private-estimates.buyer.show', $token);
        }

        $estimate = $invitation->privateEstimate;
        $result = $this->otp->verify($estimate, (string) $request->input('code'));

        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        $invitation->forceFill(['verified_at' => now()])->save();
        if ($estimate->buyer_email_verified_at === null) {
            $estimate->forceFill(['buyer_email_verified_at' => now()])->save();
        }

        $request->session()->put($this->sessionKey($estimate), now()->addHours(12)->timestamp);

        if ($this->estimates->can($estimate, Flow::BUYER_VERIFIED) && $estimate->status !== Flow::BUYER_VERIFIED) {
            $this->estimates->transition($estimate, Flow::BUYER_VERIFIED, null, 'buyer', Flow::EVENT_EMAIL_VERIFIED);
        }

        return redirect()->route('private-estimates.buyer.show', $token);
    }

    /**
     * The comparison report, once the estimate carries a calculated rate. The
     * first time the buyer sees it, the estimate is marked viewed: the vendor's
     * timeline needs to show that the figures have landed.
     *
     * @return array<string, mixed>|null
     */
    private function comparisonFor(PrivateEstimate $estimate): ?array
    {
        if (! $this->comparisonVisible($estimate)) {
            return null;
        }

        $report = $this->comparison->build($estimate);

        if ($estimate->first_viewed_at === null) {
            $estimate->forceFill(['first_viewed_at' => now()])->save();
        }

        if ($this->estimates->can($estimate, Flow::ESTIMATE_VIEWED) && $estimate->status !== Flow::ESTIMATE_VIEWED) {
            $this->estimates->transition($estimate, Flow::ESTIMATE_VIEWED, null, 'buyer', Flow::EVENT_ESTIMATE_VIEWED);
        }

        return $report;
    }

    /**
     * The buyer sees the comparison once it is priced and the estimate has
     * reached a status that releases it. Withdrawn and expired estimates show
     * nothing, whatever pricing they still carry.
     */
    private function comparisonVisible(PrivateEstimate $estimate): bool
    {
        $released = [
            Flow::ESTIMATE_READY, Flow::ESTIMATE_VIEWED, Flow::PDF_LOCKED,
            Flow::PASSWORD_REQUESTED, Flow::PASSWORD_RELEASED, Flow::ESTIMATE_REVIEWED,
            Flow::BUYER_MEETING_REQUESTED, Flow::BUYER_PROCEEDING,
            Flow::AWARD_PENDING, Flow::AWARDED, Flow::IMPLEMENTATION,
        ];

        return in_array($estimate->status, $released, true)
            && ! $estimate->isExpired()
            && $this->comparison->ready($estimate);
    }

    /** The invitation for a token, or null when it is missing, expired or revoked. */
    private function usableInvitation(string $token): ?EstimateInvitation
    {
        return $this->invitations->resolve($token)['invitation'];
    }

    private function sessionVerified(Request $request, PrivateEstimate $estimate): bool
    {
        $expiry = $request->session()->get($this->sessionKey($estimate));

        return is_numeric($expiry) && (int) $expiry > now()->timestamp;
    }

    private function sessionKey(PrivateEstimate $estimate): string
    {
        return 'private_estimate_verified.'.$estimate->id;
    }

    private function vendorName(PrivateEstimate $estimate): string
    {
        return $estimate->vendor?->company
            ?: $estimate->vendor?->vendorProfile?->company_name
            ?: ($estimate->vendor?->name ?? 'Your security vendor');
    }

    /** j***@example.com — enough to recognise, not enough to harvest. */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, 1);

        return $visible.str_repeat('*', max(1, mb_strlen($local) - 1)).'@'.$domain;
    }
}
