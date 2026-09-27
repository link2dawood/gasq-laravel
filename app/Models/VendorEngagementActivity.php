<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line of the engagement audit trail. Append-only: never updated or deleted. */
class VendorEngagementActivity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'vendor_engagement_activity';

    protected $guarded = ['id'];

    protected $casts = [
        'event_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(VendorEngagement::class, 'vendor_engagement_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
