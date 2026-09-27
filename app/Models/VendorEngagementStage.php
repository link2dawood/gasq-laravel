<?php

namespace App\Models;

use App\Support\VendorEngagement as Flow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One stage of one engagement, with whatever the vendor has saved so far. */
class VendorEngagementStage extends Model
{
    public const LOCKED = 'locked';

    public const AVAILABLE = 'available';

    public const IN_PROGRESS = 'in_progress';

    public const COMPLETE = 'complete';

    public const SKIPPED = 'skipped';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(VendorEngagement::class, 'vendor_engagement_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isComplete(): bool
    {
        return in_array($this->status, [self::COMPLETE, self::SKIPPED], true);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::AVAILABLE, self::IN_PROGRESS], true);
    }

    public function label(): string
    {
        return Flow::label($this->stage_key);
    }
}
