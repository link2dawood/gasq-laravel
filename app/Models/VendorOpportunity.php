<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class VendorOpportunity extends Model
{
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_READY = 'ready';
    public const STATUS_SENT = 'sent';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_HELD = 'held';

    protected $fillable = [
        'job_posting_id',
        'lead_tier',
        'status',
        'decision_maker_verified',
        'budget_confirmed',
        'scope_completed',
        'timeline_ready',
        'move_forward_confirmed',
        'estimated_annual_contract_value',
        'vendor_target_count',
        'max_accepts',
        'approved_at',
        'sent_at',
        'closed_at',
    ];

    protected $casts = [
        'decision_maker_verified' => 'boolean',
        'budget_confirmed' => 'boolean',
        'scope_completed' => 'boolean',
        'timeline_ready' => 'boolean',
        'move_forward_confirmed' => 'boolean',
        'estimated_annual_contract_value' => 'decimal:2',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(VendorOpportunityInvitation::class);
    }

    public function respondedInvitations(): HasMany
    {
        return $this->invitations()->whereNotIn('status', [
            VendorOpportunityInvitation::STATUS_NEW,
            VendorOpportunityInvitation::STATUS_VIEWED,
        ]);
    }

    public function acceptedInvitations(): HasMany
    {
        return $this->invitations()->whereNotNull('accepted_at');
    }

    public function activeAcceptedInvitations(): HasMany
    {
        return $this->invitations()->whereIn('status', [
            VendorOpportunityInvitation::STATUS_ACCEPTED,
            VendorOpportunityInvitation::STATUS_BID_SUBMITTED,
            VendorOpportunityInvitation::STATUS_UNDER_REVIEW,
            VendorOpportunityInvitation::STATUS_AWARDED,
            VendorOpportunityInvitation::STATUS_NOT_SELECTED,
        ]);
    }

    public function hasOpenAcceptSlots(): bool
    {
        return $this->acceptedInvitations()->count() < $this->max_accepts;
    }

    public function isClosed(): bool
    {
        if (in_array($this->status, [self::STATUS_CLOSED, self::STATUS_EXPIRED, self::STATUS_HELD], true)) {
            return true;
        }

        return $this->closed_at instanceof Carbon;
    }
}
