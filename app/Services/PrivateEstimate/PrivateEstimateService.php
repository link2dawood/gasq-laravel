<?php

namespace App\Services\PrivateEstimate;

use App\Models\EstimateActivity;
use App\Models\PrivateEstimate;
use App\Models\User;
use App\Support\PrivateEstimate as Flow;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates private estimates, moves them through the status model and writes the
 * activity trail. Every status change in the app goes through transition(), so
 * an estimate can never skip a step (spec 67: "use controlled state transitions").
 */
class PrivateEstimateService
{
    /**
     * @param  array<string, mixed>  $attributes  buyer and site details from the create form
     */
    public function create(User $vendor, array $attributes): PrivateEstimate
    {
        return DB::transaction(function () use ($vendor, $attributes) {
            $estimate = PrivateEstimate::create(array_merge($attributes, [
                'vendor_id' => $vendor->id,
                'public_id' => $this->nextPublicId(),
                'status' => Flow::DRAFT,
            ]));

            $this->log($estimate, Flow::EVENT_INVITATION_CREATED, $vendor, 'vendor', [
                'buyer_email' => $estimate->buyer_email,
            ]);
            $this->transition($estimate, Flow::INVITATION_CREATED, $vendor, 'vendor');

            return $estimate;
        });
    }

    /**
     * Public estimate number: PE-YYMMDD-NNNN, sequential within the day.
     * The internal id is never exposed (spec 2).
     */
    public function nextPublicId(?\DateTimeInterface $on = null): string
    {
        $date = $on ? $on->format('ymd') : now()->format('ymd');
        $prefix = 'PE-'.$date.'-';

        $last = PrivateEstimate::query()
            ->where('public_id', 'like', $prefix.'%')
            ->orderByDesc('public_id')
            ->value('public_id');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Move to a new status, or throw if the model does not allow it.
     *
     * @param  array<string, mixed>  $context  extra data for the activity row
     */
    public function transition(
        PrivateEstimate $estimate,
        string $to,
        ?User $actor = null,
        string $actorRole = 'system',
        ?string $event = null,
        array $context = [],
    ): PrivateEstimate {
        $from = $estimate->status;

        if ($from !== $to && ! Flow::canTransition($from, $to)) {
            throw new RuntimeException("A private estimate cannot go from {$from} to {$to}.");
        }

        $estimate->status = $to;
        $estimate->save();

        if ($event) {
            $this->log($estimate, $event, $actor, $actorRole, $context + ['from' => $from, 'to' => $to]);
        }

        return $estimate;
    }

    /** True when the status may move, without throwing. Use to hide actions in the UI. */
    public function can(PrivateEstimate $estimate, string $to): bool
    {
        return $estimate->status === $to || Flow::canTransition($estimate->status, $to);
    }

    /**
     * Append to the estimate timeline. Best effort: a logging failure must never
     * break the flow it is recording.
     *
     * @param  array<string, mixed>  $data
     */
    public function log(
        PrivateEstimate $estimate,
        string $event,
        ?User $actor = null,
        string $actorRole = 'system',
        array $data = [],
    ): ?EstimateActivity {
        try {
            $request = request();

            return EstimateActivity::create([
                'private_estimate_id' => $estimate->id,
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

    /**
     * Mark estimates whose pricing validity has run out. Returns how many moved.
     * Expiry closes future access but keeps the record and its history (spec 44).
     */
    public function expireOverdue(): int
    {
        $expired = 0;

        PrivateEstimate::query()
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now())
            ->whereNotIn('status', [Flow::EXPIRED, Flow::CLOSED, Flow::AWARDED, Flow::IMPLEMENTATION, Flow::WITHDRAWN])
            ->each(function (PrivateEstimate $estimate) use (&$expired) {
                if (! Flow::canTransition($estimate->status, Flow::EXPIRED)) {
                    return;
                }
                $this->transition($estimate, Flow::EXPIRED, null, 'system', Flow::EVENT_ESTIMATE_EXPIRED);
                $expired++;
            });

        return $expired;
    }
}
