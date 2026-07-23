<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    protected $fillable = [
        'job_posting_id', 'vendor_id', 'bid_id', 'slot_id', 'status', 'scheduled_at',
        'duration_minutes', 'format', 'location', 'timezone', 'capability_score',
        'price_status', 'vendor_prep_acknowledged_at', 'reschedule_count',
        'completed_at', 'buyer_notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'vendor_prep_acknowledged_at' => 'datetime',
        'duration_minutes' => 'integer',
        'reschedule_count' => 'integer',
        'capability_score' => 'decimal:2',
    ];

    /** Interview lifecycle statuses (spec §10). */
    public const STATUSES = [
        'invited', 'scheduling_pending', 'scheduled', 'confirmed',
        'reschedule_requested', 'buyer_rescheduled', 'vendor_rescheduled',
        'completed', 'buyer_score_pending', 'buyer_score_completed',
        'vendor_no_show', 'buyer_no_show', 'withdrawn',
        'not_selected_for_price_review', 'advanced_to_price_reveal',
        'finalist', 'selected', 'alternate',
    ];

    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null
            && ! in_array($this->status, ['invited', 'scheduling_pending', 'withdrawn', 'vendor_no_show'], true);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null || $this->status === 'completed'
            || in_array($this->status, ['buyer_score_pending', 'buyer_score_completed', 'finalist', 'selected', 'alternate'], true);
    }

    public function priceSealed(): bool
    {
        return $this->price_status !== 'revealed';
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(InterviewSlot::class, 'slot_id');
    }
}
