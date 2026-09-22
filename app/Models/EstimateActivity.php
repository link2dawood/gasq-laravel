<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line of the estimate timeline. Append-only: never updated or deleted. */
class EstimateActivity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'estimate_activity';

    protected $guarded = ['id'];

    protected $casts = [
        'event_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function privateEstimate(): BelongsTo
    {
        return $this->belongsTo(PrivateEstimate::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
