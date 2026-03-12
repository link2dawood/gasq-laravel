<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoveryCall extends Model
{
    protected $fillable = [
        'user_id',
        'requested_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
