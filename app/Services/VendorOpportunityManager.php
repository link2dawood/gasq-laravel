<?php

namespace App\Services;

use App\Models\Bid;
use App\Models\JobPosting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VendorOpportunity;
use App\Models\VendorOpportunityInvitation;
use App\Notifications\VendorOpportunityNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendorOpportunityManager
{
    public function __construct(
        private readonly VendorOpportunityQualificationService $qualificationService,
        private readonly VendorOpportunityMatchingService $matchingService,
        private readonly VendorOpportunityCreditPricingService $creditPricingService,
        private readonly VendorOpportunityTracker $tracker,
        private readonly WalletService $walletService,
        private readonly VendorOpportunityBidScoringService $bidScoringService,
        private readonly BuyerVendorMatchNotifier $buyerVendorMatchNotifier,
    ) {}

    public function createForPublishedJob(JobPosting $job): VendorOpportunity
    {
        $qualification = $this->qualificationService->qualify($job);

        $opportunity = VendorOpportunity::query()->updateOrCreate(
            ['job_posting_id' => $job->id],
            [
                'lead_tier' => $qualification['lead_tier'],
                'status' => $qualification['lead_tier'] === 'a'
                    ? VendorOpportunity::STATUS_READY
                    : ($qualification['lead_tier'] === 'b'
                        ? VendorOpportunity::STATUS_PENDING_REVIEW
                        : VendorOpportunity::STATUS_HELD),
                'decision_maker_verified' => $qualification['decision_maker_verified'],
                'budget_confirmed' => $qualification['budget_confirmed'],
                'scope_completed' => $qualification['scope_completed'],
                'timeline_ready' => $qualification['timeline_ready'],
                'move_forward_confirmed' => $qualification['move_forward_confirmed'],
                'estimated_annual_contract_value' => $qualification['estimated_annual_contract_value'],
                'vendor_target_count' => $qualification['vendor_target_count'],
                'max_accepts' => 5,
            ]
        );

        if ($opportunity->lead_tier === 'a') {
            $this->sendInvitations($opportunity);
        } else {
            $this->buyerVendorMatchNotifier->notifyPendingQualification($opportunity);
        }

        return $opportunity->fresh(['jobPosting.user', 'invitations.vendor']);
    }

    public function approveAndSend(VendorOpportunity $opportunity): VendorOpportunity
    {
        $opportunity->forceFill([
            'status' => VendorOpportunity::STATUS_READY,
            'approved_at' => now(),
        ])->save();

        return $this->sendInvitations($opportunity);
    }

    public function sendInvitations(VendorOpportunity $opportunity): VendorOpportunity
    {
        $opportunity->loadMissing('jobPosting.user');
        $wasOpportunityUnsent = $opportunity->sent_at === null;

        if ($opportunity->lead_tier === 'c') {
            return $opportunity;
        }

        $matches = $this->matchingService->match(
            $opportunity->jobPosting,
            $opportunity->lead_tier,
            $opportunity->vendor_target_count
        );

        $credits = $this->creditPricingService->creditsFor(
            (float) $opportunity->estimated_annual_contract_value,
            $opportunity->lead_tier
        );
        $newlySentInvitationIds = [];

        DB::transaction(function () use ($opportunity, $matches, $credits, &$newlySentInvitationIds): void {
            /** @var array{vendor: User, score: float, reasons: list<string>} $match */
            foreach ($matches as $match) {
                $invitation = VendorOpportunityInvitation::query()->firstOrNew([
                    'vendor_opportunity_id' => $opportunity->id,
                    'vendor_id' => $match['vendor']->id,
                ]);
                $wasUnsent = $invitation->sent_at === null;

                if (! $invitation->exists) {
                    $invitation->fill([
                        'invite_key' => (string) Str::uuid(),
                        'status' => VendorOpportunityInvitation::STATUS_NEW,
                        'credits_to_unlock' => $credits,
                        'match_score' => $match['score'],
                        'match_reasons' => $match['reasons'],
                    ]);
                }

                if ($invitation->sent_at === null) {
                    $invitation->sent_at = now();
                }

                $invitation->save();
                if ($wasUnsent) {
                    $newlySentInvitationIds[] = $invitation->id;
                }
            }

            $opportunity->forceFill([
                'status' => VendorOpportunity::STATUS_SENT,
                'sent_at' => now(),
            ])->save();
        });

        $opportunity->load('invitations.vendor', 'jobPosting.user');

        foreach ($opportunity->invitations as $invitation) {
            if (in_array($invitation->id, $newlySentInvitationIds, true)) {
                $invitation->vendor->notify(new VendorOpportunityNotification($invitation, 'new'));
                $this->tracker->track('vendor_opportunity_email_sent', $invitation, $invitation->vendor, [
                    'notification_type' => 'new',
                ]);
            }
        }

        if ($wasOpportunityUnsent && $newlySentInvitationIds !== []) {
            $this->buyerVendorMatchNotifier->notifyOpportunityLive($opportunity);
        }

        return $opportunity;
    }

    public function markInvitationOpened(VendorOpportunityInvitation $invitation): void
    {
        if ($invitation->opened_at !== null) {
            return;
        }

        $invitation->forceFill([
            'opened_at' => now(),
        ])->save();

        $this->tracker->track('vendor_opportunity_opened', $invitation, $invitation->vendor);
    }

    public function markInvitationViewed(VendorOpportunityInvitation $invitation, User $vendor): void
    {
        $updates = [];

        if ($invitation->viewed_at === null) {
            $updates['viewed_at'] = now();
        }

        if ($invitation->status === VendorOpportunityInvitation::STATUS_NEW) {
            $updates['status'] = VendorOpportunityInvitation::STATUS_VIEWED;
        }

        if ($updates !== []) {
            $invitation->forceFill($updates)->save();
        }

        $this->expireAcceptedWindowIfNeeded($invitation);
        $this->tracker->track('vendor_opportunity_viewed', $invitation, $vendor);
    }

    public function acceptInvitation(VendorOpportunityInvitation $invitation, User $vendor): VendorOpportunityInvitation
    {
        $invitation->loadMissing('opportunity.jobPosting.user', 'vendor');
        $opportunity = $invitation->opportunity;

        if (! $opportunity || $opportunity->isClosed()) {
            throw ValidationException::withMessages([
                'opportunity' => 'This opportunity is closed.',
            ]);
        }

        if (! $opportunity->hasOpenAcceptSlots() && $invitation->accepted_at === null) {
            throw ValidationException::withMessages([
                'opportunity' => 'This opportunity already reached the maximum number of accepted vendors.',
            ]);
        }

        $firstAccept = $invitation->accepted_at === null;

        DB::transaction(function () use ($invitation, $vendor, $firstAccept): void {
            if ($firstAccept) {
                $spent = $this->walletService->spendTokens(
                    $vendor,
                    $invitation->credits_to_unlock,
                    'vendor_opportunity_accept',
                    'Unlocked buyer details for vendor opportunity.',
                    (string) $invitation->id
                );

                if (! $spent) {
                    throw ValidationException::withMessages([
                        'opportunity' => 'You do not have enough credits to unlock this opportunity.',
                    ]);
                }

                $transaction = Transaction::query()
                    ->where('user_id', $vendor->id)
                    ->where('reference_type', 'vendor_opportunity_accept')
                    ->where('reference_id', (string) $invitation->id)
                    ->latest('id')
                    ->first();

                $invitation->credits_transaction_id = $transaction?->id;
            }

            $invitation->status = VendorOpportunityInvitation::STATUS_ACCEPTED;
            $invitation->accepted_at ??= now();
            $invitation->expires_at = now()->addDay();
            $invitation->save();
        });

        $invitation->refresh();

        if ($firstAccept) {
            $vendor->notify(new VendorOpportunityNotification($invitation, 'unlocked'));
            $this->tracker->track('vendor_opportunity_accepted', $invitation, $vendor);
            $this->tracker->track('vendor_opportunity_credits_charged', $invitation, $vendor, [
                'credits' => $invitation->credits_to_unlock,
            ]);
            $this->buyerVendorMatchNotifier->notifyAcceptedProgress($invitation->opportunity->fresh());
        }

        return $invitation;
    }

    public function declineInvitation(VendorOpportunityInvitation $invitation, User $vendor, string $reason, ?string $otherReason = null): VendorOpportunityInvitation
    {
        $invitation->forceFill([
            'status' => VendorOpportunityInvitation::STATUS_DECLINED,
            'decline_reason' => $reason,
            'decline_reason_other' => $reason === 'other' ? $otherReason : null,
            'declined_at' => now(),
            'expires_at' => null,
        ])->save();

        $this->tracker->track('vendor_opportunity_declined', $invitation, $vendor, [
            'reason' => $reason,
        ]);

        return $invitation;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submitBid(VendorOpportunityInvitation $invitation, User $vendor, array $payload): Bid
    {
        $invitation->loadMissing('opportunity.jobPosting.user', 'vendor');
        $this->expireAcceptedWindowIfNeeded($invitation);

        if (! $invitation->buyerDetailsUnlocked() || $invitation->status === VendorOpportunityInvitation::STATUS_DECLINED) {
            throw ValidationException::withMessages([
                'bid' => 'Accept and unlock the opportunity before submitting your bid.',
            ]);
        }

        if ($invitation->bidWindowExpired()) {
            throw ValidationException::withMessages([
                'bid' => 'The bid submission window has expired for this opportunity.',
            ]);
        }

        $job = $invitation->opportunity->jobPosting;
        $score = $this->bidScoringService->score(
            $job,
            (float) $payload['hourly_bill_rate'],
            (float) $payload['annual_price']
        );

        $bid = Bid::query()->updateOrCreate(
            [
                'job_posting_id' => $job->id,
                'user_id' => $vendor->id,
            ],
            [
                'vendor_opportunity_invitation_id' => $invitation->id,
                'amount' => $payload['annual_price'] ?: $payload['monthly_price'] ?: $payload['weekly_price'] ?: $payload['hourly_bill_rate'],
                'hourly_bill_rate' => $payload['hourly_bill_rate'],
                'weekly_price' => $payload['weekly_price'],
                'monthly_price' => $payload['monthly_price'],
                'annual_price' => $payload['annual_price'],
                'message' => $payload['vendor_notes'] ?? null,
                'proposal' => $payload['staffing_plan'] ?? null,
                'staffing_plan' => $payload['staffing_plan'] ?? null,
                'start_availability' => $payload['start_availability'],
                'vendor_notes' => $payload['vendor_notes'] ?? null,
                'status' => 'pending',
                'submitted_at' => now(),
                'realism_score' => $score['score'],
                'realism_label' => $score['label'],
                'realism_flagged' => $score['flagged'],
            ]
        );

        $invitation->forceFill([
            'status' => VendorOpportunityInvitation::STATUS_BID_SUBMITTED,
            'bid_submitted_at' => now(),
        ])->save();

        $job->user->notify(new VendorOpportunityNotification($invitation->fresh(), 'bid_received_buyer'));
        $vendor->notify(new VendorOpportunityNotification($invitation->fresh(), 'bid_received_vendor'));
        $this->tracker->track('vendor_opportunity_bid_submitted', $invitation, $vendor, [
            'bid_id' => $bid->id,
            'realism_score' => $score['score'],
            'realism_label' => $score['label'],
            'realism_flagged' => $score['flagged'],
        ]);

        return $bid;
    }

    public function closeOpportunity(VendorOpportunity $opportunity): VendorOpportunity
    {
        $opportunity->forceFill([
            'status' => VendorOpportunity::STATUS_CLOSED,
            'closed_at' => now(),
        ])->save();

        $opportunity->invitations()
            ->whereIn('status', [
                VendorOpportunityInvitation::STATUS_NEW,
                VendorOpportunityInvitation::STATUS_VIEWED,
                VendorOpportunityInvitation::STATUS_ACCEPTED,
            ])
            ->update([
                'status' => VendorOpportunityInvitation::STATUS_EXPIRED,
                'expires_at' => now(),
            ]);

        return $opportunity->fresh('invitations.vendor');
    }

    public function awardInvitation(VendorOpportunityInvitation $winner): VendorOpportunityInvitation
    {
        $winner->loadMissing('opportunity.invitations.vendor', 'vendor', 'bid');
        $opportunity = $winner->opportunity;

        DB::transaction(function () use ($winner, $opportunity): void {
            $winner->forceFill([
                'status' => VendorOpportunityInvitation::STATUS_AWARDED,
            ])->save();

            if ($winner->bid) {
                $winner->bid->forceFill([
                    'status' => 'accepted',
                    'responded_at' => now(),
                ])->save();
            }

            foreach ($opportunity->invitations as $invitation) {
                if ($invitation->id === $winner->id) {
                    continue;
                }

                if ($invitation->status !== VendorOpportunityInvitation::STATUS_AWARDED) {
                    $invitation->forceFill([
                        'status' => VendorOpportunityInvitation::STATUS_NOT_SELECTED,
                    ])->save();
                }

                if ($invitation->bid && $invitation->bid->status === 'pending') {
                    $invitation->bid->forceFill([
                        'status' => 'rejected',
                        'responded_at' => now(),
                    ])->save();
                }
            }

            $opportunity->forceFill([
                'status' => VendorOpportunity::STATUS_CLOSED,
                'closed_at' => now(),
            ])->save();

            $job = $opportunity->jobPosting;
            $job->forceFill([
                'status' => 'awarded',
            ])->save();
        });

        $winner->refresh()->loadMissing('opportunity.invitations.vendor', 'vendor');

        foreach ($winner->opportunity->invitations as $invitation) {
            $type = $invitation->id === $winner->id ? 'awarded' : 'not_selected';
            $invitation->vendor->notify(new VendorOpportunityNotification($invitation, $type));
            $this->tracker->track('vendor_opportunity_outcome_sent', $invitation, $invitation->vendor, [
                'outcome' => $type,
            ]);
        }

        return $winner;
    }

    public function processAutomation(): void
    {
        VendorOpportunityInvitation::query()
            ->with(['vendor', 'opportunity.jobPosting.user'])
            ->chunkById(100, function (Collection $invitations): void {
                foreach ($invitations as $invitation) {
                    $this->processInvitationAutomation($invitation);
                }
            });
    }

    private function processInvitationAutomation(VendorOpportunityInvitation $invitation): void
    {
        if ($invitation->status === VendorOpportunityInvitation::STATUS_DECLINED) {
            return;
        }

        if ($invitation->status === VendorOpportunityInvitation::STATUS_ACCEPTED && $invitation->bidWindowExpired()) {
            $invitation->forceFill([
                'status' => VendorOpportunityInvitation::STATUS_EXPIRED,
            ])->save();
            return;
        }

        if ($invitation->status === VendorOpportunityInvitation::STATUS_ACCEPTED
            && $invitation->bid_submitted_at === null
            && $invitation->accepted_bid_reminder_sent_at === null
            && $invitation->accepted_at !== null
            && $invitation->accepted_at->diffInHours(now()) >= 18
        ) {
            $invitation->vendor->notify(new VendorOpportunityNotification($invitation, 'accepted_bid_reminder'));
            $invitation->forceFill(['accepted_bid_reminder_sent_at' => now()])->save();
            $this->tracker->track('vendor_opportunity_email_sent', $invitation, $invitation->vendor, [
                'notification_type' => 'accepted_bid_reminder',
            ]);
        }

        if (in_array($invitation->status, [VendorOpportunityInvitation::STATUS_NEW, VendorOpportunityInvitation::STATUS_VIEWED], true)) {
            $hoursSinceSent = $invitation->sent_at?->diffInHours(now()) ?? 0;

            if ($hoursSinceSent >= 48 && $invitation->final_notice_sent_at === null) {
                $invitation->vendor->notify(new VendorOpportunityNotification($invitation, 'final_notice'));
                $invitation->forceFill(['final_notice_sent_at' => now()])->save();
                $this->tracker->track('vendor_opportunity_email_sent', $invitation, $invitation->vendor, [
                    'notification_type' => 'final_notice',
                ]);
                return;
            }

            if ($hoursSinceSent >= 24 && $invitation->first_reminder_sent_at === null) {
                $invitation->vendor->notify(new VendorOpportunityNotification($invitation, 'reminder'));
                $invitation->forceFill(['first_reminder_sent_at' => now()])->save();
                $this->tracker->track('vendor_opportunity_email_sent', $invitation, $invitation->vendor, [
                    'notification_type' => 'reminder',
                ]);
            }
        }
    }

    public function expireAcceptedWindowIfNeeded(VendorOpportunityInvitation $invitation): void
    {
        if ($invitation->status === VendorOpportunityInvitation::STATUS_ACCEPTED && $invitation->bidWindowExpired()) {
            $invitation->forceFill([
                'status' => VendorOpportunityInvitation::STATUS_EXPIRED,
            ])->save();
        }
    }
}
