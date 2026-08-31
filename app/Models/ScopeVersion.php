<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded change to an opportunity's scope (review spec §6, §33).
 *
 * The audit trail that lets a buyer, a vendor or GASQ answer "what did this opportunity
 * actually say when the vendor accepted it?".
 */
class ScopeVersion extends Model
{
    protected $fillable = [
        'job_posting_id', 'version', 'is_material', 'changes', 'changed_by', 'note',
    ];

    protected $casts = [
        'is_material' => 'boolean',
        'changes' => 'array',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
