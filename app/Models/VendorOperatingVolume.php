<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evidence of a vendor's operating scale — the 1,000-hour gate (spec §29).
 *
 * Reaching the threshold makes a vendor ELIGIBLE FOR REVIEW. It never approves a
 * Shared Resource rate on its own (RULE 8).
 */
class VendorOperatingVolume extends Model
{
    /** Minimum active weekly billable hours to be eligible for Shared Resource review. */
    public const MINIMUM_WEEKLY_HOURS = 1000;

    public const STATUS_NOT_VERIFIED = 'not_verified';
    public const STATUS_PENDING = 'pending';
    public const STATUS_NOT_ELIGIBLE = 'not_eligible';
    public const STATUS_ELIGIBLE_FOR_REVIEW = 'eligible_for_review';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'vendor_id', 'weekly_billable_hours', 'account_count', 'status',
        'evidence_notes', 'evidence_documents', 'reviewed_by',
        'submitted_at', 'verified_at', 'expires_at', 'review_notes',
    ];

    protected $casts = [
        'weekly_billable_hours' => 'decimal:2',
        'account_count' => 'integer',
        'evidence_documents' => 'array',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function meetsHoursThreshold(): bool
    {
        return (float) $this->weekly_billable_hours >= self::MINIMUM_WEEKLY_HOURS;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Eligible only while verified, above threshold, and not expired (RULE 16). */
    public function isEligibleForReview(): bool
    {
        return $this->status === self::STATUS_ELIGIBLE_FOR_REVIEW
            && $this->meetsHoursThreshold()
            && ! $this->isExpired();
    }
}
