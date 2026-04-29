<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    protected $fillable = [
        'job_posting_id',
        'user_id',
        'vendor_opportunity_invitation_id',
        'amount',
        'hourly_bill_rate',
        'weekly_price',
        'monthly_price',
        'annual_price',
        'status',
        'vendor_response_status',
        'message',
        'proposal',
        'staffing_plan',
        'start_availability',
        'vendor_notes',
        'realism_score',
        'realism_label',
        'realism_flagged',
        'responded_at',
        'vendor_responded_at',
        'counter_offer_amount',
        'counter_offer_message',
        'counter_offer_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'hourly_bill_rate' => 'decimal:2',
        'weekly_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'realism_score' => 'integer',
        'realism_flagged' => 'boolean',
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

    public function vendorOpportunityInvitation(): BelongsTo
    {
        return $this->belongsTo(VendorOpportunityInvitation::class);
    }
}
