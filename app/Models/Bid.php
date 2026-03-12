<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    protected $fillable = [
        'job_posting_id',
        'user_id',
        'amount',
        'status',
        'message',
        'proposal',
        'responded_at',
        'counter_offer_amount',
        'counter_offer_message',
        'counter_offer_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'responded_at' => 'datetime',
        'counter_offer_amount' => 'decimal:2',
        'counter_offer_at' => 'datetime',
    ];

    public function hasCounterOffer(): bool
    {
        return $this->counter_offer_amount !== null;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
