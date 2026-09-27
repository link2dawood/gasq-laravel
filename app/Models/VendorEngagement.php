<?php

namespace App\Models;

use App\Support\VendorEngagement as Flow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One vendor working one opportunity through the ten-stage sequence.
 * Stage changes go through VendorEngagementService so they stay ordered
 * and logged; do not set `stage` directly.
 */
class VendorEngagement extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'adjustment_requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(VendorOpportunityInvitation::class, 'vendor_opportunity_invitation_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(VendorOpportunity::class, 'vendor_opportunity_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(VendorEngagementStage::class)->orderBy('position');
    }

    public function activity(): HasMany
    {
        return $this->hasMany(VendorEngagementActivity::class)->latest('created_at');
    }

    public function stageRecord(string $key): ?VendorEngagementStage
    {
        return $this->stages()->where('stage_key', $key)->first();
    }

    public function isActive(): bool
    {
        return in_array($this->status, [Flow::STATUS_ACTIVE, Flow::STATUS_ADJUSTMENT_REQUESTED], true);
    }

    public function currentStageLabel(): string
    {
        return Flow::label($this->stage);
    }

    /** How far along the sequence, for a progress rail. */
    public function progressPercent(): float
    {
        $total = count(Flow::keys());
        $done = $this->stages()->where('status', VendorEngagementStage::COMPLETE)->count();

        return $total > 0 ? round($done / $total * 100, 1) : 0.0;
    }
}
