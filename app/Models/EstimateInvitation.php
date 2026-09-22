<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One invitation link. Only the SHA-256 of the token is stored, so the live
 * link cannot be rebuilt from the database.
 */
class EstimateInvitation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function privateEstimate(): BelongsTo
    {
        return $this->belongsTo(PrivateEstimate::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired() && ! $this->isRevoked();
    }
}
