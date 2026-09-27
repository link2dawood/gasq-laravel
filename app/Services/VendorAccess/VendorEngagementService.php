<?php

namespace App\Services\VendorAccess;

use App\Models\User;
use App\Models\VendorEngagement;
use App\Models\VendorEngagementActivity;
use App\Models\VendorEngagementStage;
use App\Models\VendorOpportunityInvitation;
use App\Support\VendorEngagement as Flow;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Runs the vendor side of an opportunity: starts the engagement, opens stages
 * in order, records what was saved, and writes the audit trail.
 *
 * The ordering rule lives here and nowhere else. A stage opens only when every
 * stage before it is complete, so "price last" is enforced by the server rather
 * than by the order of links in a page.
 */
class VendorEngagementService
{
    /**
     * Find or create the engagement behind an invitation, and record the use.
     * Safe to call on every visit; only the first call creates anything.
     */
    public function startFromInvitation(VendorOpportunityInvitation $invitation, User $actor): VendorEngagement
    {
        return DB::transaction(function () use ($invitation, $actor) {
            $engagement = VendorEngagement::query()
                ->where('vendor_opportunity_id', $invitation->vendor_opportunity_id)
                ->where('vendor_id', $invitation->vendor_id)
                ->first();

            if (! $engagement) {
                $engagement = VendorEngagement::create([
                    'vendor_opportunity_invitation_id' => $invitation->id,
                    'vendor_opportunity_id' => $invitation->vendor_opportunity_id,
                    'vendor_id' => $invitation->vendor_id,
                    'stage' => Flow::OPPORTUNITY,
                    'status' => Flow::STATUS_ACTIVE,
                    'started_at' => now(),
                ]);

                $this->seedStages($engagement);
                $this->log($engagement, Flow::EVENT_ENGAGEMENT_STARTED, $actor, 'vendor');
            }

            $this->recordInvitationUse($invitation, $engagement, $actor);

            return $engagement->refresh();
        });
    }

    /** Every stage exists from the start; the first is open, the rest are locked. */
    private function seedStages(VendorEngagement $engagement): void
    {
        foreach (Flow::keys() as $index => $key) {
            $engagement->stages()->create([
                'stage_key' => $key,
                'position' => $index + 1,
                'status' => $index === 0 ? VendorEngagementStage::AVAILABLE : VendorEngagementStage::LOCKED,
                'started_at' => $index === 0 ? now() : null,
            ]);
        }
    }

    /**
     * Count the visit against the invitation's use limit and remember where it
     * came from, so one credential opened from several places is visible.
     */
    private function recordInvitationUse(VendorOpportunityInvitation $invitation, VendorEngagement $engagement, User $actor): void
    {
        if ($actor->isAdmin() && (int) $actor->id !== (int) $invitation->vendor_id) {
            return; // Support looking in does not consume the vendor's uses.
        }

        $previousIp = $invitation->last_used_ip;
        $ip = request()?->ip();

        $invitation->forceFill([
            'use_count' => (int) $invitation->use_count + 1,
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ])->save();

        $this->log($engagement, Flow::EVENT_INVITATION_USED, $actor, 'vendor', [
            'use_count' => (int) $invitation->use_count,
            'max_uses' => $invitation->max_uses,
            'new_location' => $previousIp !== null && $previousIp !== $ip,
        ]);
    }

    /** True when the vendor may open this stage now. */
    public function canEnter(VendorEngagement $engagement, string $stage): bool
    {
        if (! Flow::exists($stage)) {
            return false;
        }

        $record = $engagement->stageRecord($stage);
        if ($record && $record->isComplete()) {
            return true; // A finished stage stays readable.
        }

        foreach (Flow::prerequisites($stage) as $earlier) {
            if (! ($engagement->stageRecord($earlier)?->isComplete())) {
                return false;
            }
        }

        return true;
    }

    /** The furthest stage the vendor may work on right now. */
    public function currentStage(VendorEngagement $engagement): string
    {
        foreach (Flow::keys() as $key) {
            if (! ($engagement->stageRecord($key)?->isComplete())) {
                return $key;
            }
        }

        return Flow::SUCCESS_FEE;
    }

    /**
     * Mark a stage complete, open the next one, and move the engagement on.
     *
     * @param  array<string, mixed>  $payload  what the vendor saved at this stage
     */
    public function completeStage(VendorEngagement $engagement, string $stage, User $actor, array $payload = []): VendorEngagement
    {
        if (! $this->canEnter($engagement, $stage)) {
            throw new RuntimeException("Stage {$stage} is not open yet.");
        }

        return DB::transaction(function () use ($engagement, $stage, $actor, $payload) {
            $record = $engagement->stageRecord($stage);
            if (! $record) {
                throw new RuntimeException("Stage {$stage} does not exist on this engagement.");
            }

            $record->forceFill([
                'status' => VendorEngagementStage::COMPLETE,
                'payload' => $payload ?: $record->payload,
                'completed_at' => now(),
                'completed_by' => $actor->id,
            ])->save();

            $this->log($engagement, Flow::EVENT_STAGE_COMPLETED, $actor, $this->roleOf($actor), [
                'stage' => $stage,
            ]);

            $next = Flow::next($stage);
            if ($next) {
                $nextRecord = $engagement->stageRecord($next);
                if ($nextRecord && $nextRecord->status === VendorEngagementStage::LOCKED) {
                    $nextRecord->forceFill([
                        'status' => VendorEngagementStage::AVAILABLE,
                        'started_at' => now(),
                    ])->save();
                    $this->log($engagement, Flow::EVENT_STAGE_OPENED, null, 'system', ['stage' => $next]);
                }
                $engagement->forceFill(['stage' => $next])->save();
            } else {
                $engagement->forceFill(['completed_at' => now()])->save();
            }

            return $engagement->refresh();
        });
    }

    /** Save a half-finished stage without completing it. */
    public function saveProgress(VendorEngagement $engagement, string $stage, array $payload, ?User $actor = null): void
    {
        $record = $engagement->stageRecord($stage);
        if (! $record || $record->isComplete()) {
            return;
        }

        $record->forceFill([
            'status' => VendorEngagementStage::IN_PROGRESS,
            'payload' => array_merge((array) $record->payload, $payload),
            'started_at' => $record->started_at ?? now(),
        ])->save();
    }

    // ── The response stage: accept, decline, request an adjustment ──────────

    public function accept(VendorEngagement $engagement, User $actor): VendorEngagement
    {
        $engagement->forceFill([
            'accepted_at' => now(),
            'status' => Flow::STATUS_ACTIVE,
            'adjustment_requested_at' => null,
        ])->save();

        $this->log($engagement, Flow::EVENT_OPPORTUNITY_ACCEPTED, $actor, 'vendor');

        return $this->completeStage($engagement, Flow::RESPONSE, $actor, ['response' => 'accepted']);
    }

    public function decline(VendorEngagement $engagement, User $actor, string $reason, ?string $note = null): VendorEngagement
    {
        $engagement->forceFill([
            'declined_at' => now(),
            'decline_reason' => $reason,
            'status' => Flow::STATUS_DECLINED,
        ])->save();

        $record = $engagement->stageRecord(Flow::RESPONSE);
        $record?->forceFill([
            'status' => VendorEngagementStage::COMPLETE,
            'payload' => ['response' => 'declined', 'reason' => $reason, 'note' => $note],
            'completed_at' => now(),
            'completed_by' => $actor->id,
        ])->save();

        $this->log($engagement, Flow::EVENT_OPPORTUNITY_DECLINED, $actor, 'vendor', [
            'reason' => $reason,
            'note' => $note,
        ]);

        return $engagement->refresh();
    }

    /**
     * A scope adjustment is a request to change what is being priced, so the
     * engagement pauses here until GASQ or the buyer answers. A clarification
     * that does not change the scope is a message, not this.
     */
    public function requestAdjustment(VendorEngagement $engagement, User $actor, string $note): VendorEngagement
    {
        $engagement->forceFill([
            'adjustment_requested_at' => now(),
            'adjustment_note' => $note,
            'status' => Flow::STATUS_ADJUSTMENT_REQUESTED,
        ])->save();

        $this->saveProgress($engagement, Flow::RESPONSE, ['response' => 'adjustment_requested', 'note' => $note], $actor);
        $this->log($engagement, Flow::EVENT_ADJUSTMENT_REQUESTED, $actor, 'vendor', ['note' => $note]);

        return $engagement->refresh();
    }

    // ── Audit trail ─────────────────────────────────────────────────────────

    /**
     * Append to the trail. Best effort: a logging failure must never break the
     * action it is recording.
     *
     * @param  array<string, mixed>  $data
     */
    public function log(
        VendorEngagement $engagement,
        string $event,
        ?User $actor = null,
        string $actorRole = 'system',
        array $data = [],
    ): ?VendorEngagementActivity {
        try {
            $request = request();

            return VendorEngagementActivity::create([
                'vendor_engagement_id' => $engagement->id,
                'actor_user_id' => $actor?->id,
                'actor_role' => $actorRole,
                'event_type' => $event,
                'event_data' => $data ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => substr((string) $request?->userAgent(), 0, 500) ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function roleOf(User $actor): string
    {
        return $actor->isAdmin() ? 'gasq' : ($actor->isVendor() ? 'vendor' : 'buyer');
    }
}
