<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One immutable version of the scope. Later edits create the next version. */
class EstimateScope extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'scope_json' => 'array',
        'changed_fields' => 'array',
        'start_date' => 'date',
        'buyer_approved_at' => 'datetime',
        'vendor_approved_at' => 'datetime',
        'hours_per_day' => 'float',
        'days_per_week' => 'float',
        'weeks_per_year' => 'float',
        'weekly_hours' => 'float',
        'annual_hours' => 'float',
        'guards_required' => 'float',
        'baseline_wage' => 'float',
    ];

    public function privateEstimate(): BelongsTo
    {
        return $this->belongsTo(PrivateEstimate::class);
    }
}
