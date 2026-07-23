<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewConfig extends Model
{
    protected $fillable = [
        'job_posting_id', 'scheduling_method', 'default_format', 'timezone',
        'interview_minutes', 'evaluation_minutes', 'min_gap_minutes', 'location',
        'scheduling_deadline', 'required_attendees', 'num_vendors',
        'disclose_competitor_count', 'status', 'certified_at', 'reveal_method',
    ];

    protected $casts = [
        'scheduling_deadline' => 'datetime',
        'certified_at' => 'datetime',
        'disclose_competitor_count' => 'boolean',
        'interview_minutes' => 'integer',
        'evaluation_minutes' => 'integer',
        'min_gap_minutes' => 'integer',
        'num_vendors' => 'integer',
    ];

    /** Buyer scheduling methods (spec §3). */
    public const METHOD_SELF = 'self';       // vendor self-scheduling (recommended default)
    public const METHOD_ASSIGNED = 'assigned'; // buyer assigns each vendor a time
    public const METHOD_GASQ = 'gasq';       // GASQ-managed scheduling

    /** Config lifecycle — gates the sealed-price reveal (spec §11). */
    public const STATUS_SETUP = 'setup';
    public const STATUS_OPEN = 'open';
    public const STATUS_INTERVIEWS_COMPLETE = 'interviews_complete';
    public const STATUS_CERTIFIED = 'certified';
    public const STATUS_PRICE_REVEALED = 'price_revealed';
    public const STATUS_CLOSED = 'closed';

    public function pricingUnlocked(): bool
    {
        return in_array($this->status, [self::STATUS_PRICE_REVEALED, self::STATUS_CLOSED], true)
            || $this->certified_at !== null;
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }
}
