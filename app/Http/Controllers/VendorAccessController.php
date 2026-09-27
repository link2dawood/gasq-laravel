<?php

namespace App\Http\Controllers;

use App\Models\VendorEngagement;
use App\Models\VendorOpportunityInvitation;
use App\Services\VendorAccess\VendorAccessGate;
use App\Services\VendorAccess\VendorEngagementService;
use App\Support\VendorEngagement as Flow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vendor Access: one invited vendor working one qualified opportunity through
 * the GASQ sequence.
 *
 * Two separate checks run on every request. The gate decides whether this
 * vendor may hold this invitation at all; the service decides which stage is
 * open. A vendor cannot reach a later stage by typing its URL.
 */
class VendorAccessController extends Controller
{
    public function __construct(
        private VendorAccessGate $gate,
        private VendorEngagementService $engagements,
    ) {}

    /** Entry point from an invitation. Sends the vendor to the open stage. */
    public function enter(Request $request, VendorOpportunityInvitation $invitation): RedirectResponse|View
    {
        $check = $this->gate->check($invitation, $request->user());
        if (! $check['ok']) {
            return view('vendor-access.denied', [
                'reason' => $check['code'],
                'message' => $check['message'],
            ]);
        }

        $engagement = $this->engagements->startFromInvitation($invitation, $request->user());

        return redirect()->route('vendor-access.stage', [
            'invitation' => $invitation,
            'stage' => $this->engagements->currentStage($engagement),
        ]);
    }

    /** One stage of the sequence. */
    public function stage(Request $request, VendorOpportunityInvitation $invitation, string $stage): RedirectResponse|View
    {
        $check = $this->gate->check($invitation, $request->user());
        if (! $check['ok']) {
            return view('vendor-access.denied', ['reason' => $check['code'], 'message' => $check['message']]);
        }

        if (! Flow::exists($stage)) {
            abort(404);
        }

        $engagement = $this->engagements->startFromInvitation($invitation, $request->user());

        // Asking for a stage that is not open yet is not an error; it just
        // means the vendor is ahead of themselves. Send them where they are.
        if (! $this->engagements->canEnter($engagement, $stage)) {
            return redirect()
                ->route('vendor-access.stage', [
                    'invitation' => $invitation,
                    'stage' => $this->engagements->currentStage($engagement),
                ])
                ->with('info', Flow::label($stage).' opens once the earlier stages are complete.');
        }

        $invitation->load(['opportunity.jobPosting.user', 'vendor']);

        return view('vendor-access.stage', [
            'invitation' => $invitation,
            'engagement' => $engagement->load('stages'),
            'stage' => $stage,
            'stageRecord' => $engagement->stageRecord($stage),
            'opportunity' => $invitation->opportunity,
            'job' => $invitation->opportunity?->jobPosting,
            'currentStage' => $this->engagements->currentStage($engagement),
        ]);
    }

    /** Stage 1: the vendor confirms they have reviewed the opportunity. */
    public function reviewed(Request $request, VendorOpportunityInvitation $invitation): RedirectResponse
    {
        [$engagement, $denied] = $this->resolve($request, $invitation);
        if ($denied) {
            return $denied;
        }

        $this->engagements->completeStage($engagement, Flow::OPPORTUNITY, $request->user(), [
            'reviewed_at' => now()->toIso8601String(),
        ]);

        return $this->toCurrentStage($invitation, $engagement, 'Opportunity reviewed. Now accept, decline, or request a scope adjustment.');
    }

    /** Stage 2, accept. */
    public function accept(Request $request, VendorOpportunityInvitation $invitation): RedirectResponse
    {
        [$engagement, $denied] = $this->resolve($request, $invitation);
        if ($denied) {
            return $denied;
        }

        $this->engagements->accept($engagement, $request->user());

        return $this->toCurrentStage($invitation, $engagement, 'Accepted. Vendor qualification is next.');
    }

    /** Stage 2, decline. A reason is required. */
    public function decline(Request $request, VendorOpportunityInvitation $invitation): RedirectResponse
    {
        [$engagement, $denied] = $this->resolve($request, $invitation);
        if ($denied) {
            return $denied;
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', array_keys(Flow::declineReasons()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->engagements->decline($engagement, $request->user(), $validated['reason'], $validated['note'] ?? null);

        return redirect()
            ->route('vendor-access.stage', ['invitation' => $invitation, 'stage' => Flow::RESPONSE])
            ->with('status', 'You have declined this opportunity. GASQ has been notified.');
    }

    /** Stage 2, request a scope adjustment. Pauses the engagement. */
    public function requestAdjustment(Request $request, VendorOpportunityInvitation $invitation): RedirectResponse
    {
        [$engagement, $denied] = $this->resolve($request, $invitation);
        if ($denied) {
            return $denied;
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $this->engagements->requestAdjustment($engagement, $request->user(), $validated['note']);

        return redirect()
            ->route('vendor-access.stage', ['invitation' => $invitation, 'stage' => Flow::RESPONSE])
            ->with('status', 'Scope adjustment requested. GASQ will come back to you before pricing.');
    }

    /** Stage 3: qualification is complete when the vendor profile is in order. */
    public function qualify(Request $request, VendorOpportunityInvitation $invitation): RedirectResponse
    {
        [$engagement, $denied] = $this->resolve($request, $invitation);
        if ($denied) {
            return $denied;
        }

        $request->validate([
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Confirm your qualification documents are current before continuing.',
        ]);

        $this->engagements->completeStage($engagement, Flow::QUALIFY, $request->user(), [
            'confirmed_current' => true,
            'confirmed_at' => now()->toIso8601String(),
        ]);

        return $this->toCurrentStage($invitation, $engagement, 'Qualification confirmed.');
    }

    /**
     * Gate the request and load the engagement.
     *
     * @return array{0: ?VendorEngagement, 1: ?RedirectResponse}
     */
    private function resolve(Request $request, VendorOpportunityInvitation $invitation): array
    {
        $check = $this->gate->check($invitation, $request->user());
        if (! $check['ok']) {
            return [null, redirect()->route('vendor-access.enter', $invitation)];
        }

        return [$this->engagements->startFromInvitation($invitation, $request->user()), null];
    }

    private function toCurrentStage(VendorOpportunityInvitation $invitation, VendorEngagement $engagement, string $message): RedirectResponse
    {
        return redirect()
            ->route('vendor-access.stage', [
                'invitation' => $invitation,
                'stage' => $this->engagements->currentStage($engagement->refresh()),
            ])
            ->with('status', $message);
    }
}
