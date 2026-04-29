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
        'vendor_response_status',
        'message',
        'proposal',
        'responded_at',
        'vendor_responded_at',
        'counter_offer_amount',
        'counter_offer_message',
        'counter_offer_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'responded_at' => 'datetime',
        'vendor_responded_at' => 'datetime',
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

    public function vendorResponsePending(): bool
    {
        return ($this->vendor_response_status ?? 'pending') === 'pending';
    }

    public function vendorAccepted(): bool
    {
        return ($this->vendor_response_status ?? 'pending') === 'accepted';
    }

    public function vendorDeclined(): bool
    {
        return in_array($this->vendor_response_status ?? 'pending', ['declined', 'rejected'], true);
    }

    public function hasVendorResponded(): bool
    {
        return $this->vendorAccepted() || $this->vendorDeclined();
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
