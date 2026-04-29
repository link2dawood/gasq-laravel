<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class VendorOpportunityInvitation extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_BID_SUBMITTED = 'bid_submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_AWARDED = 'awarded';
    public const STATUS_NOT_SELECTED = 'not_selected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'vendor_opportunity_id',
        'vendor_id',
        'invite_key',
        'status',
        'credits_to_unlock',
        'credits_transaction_id',
        'match_score',
        'match_reasons',
        'decline_reason',
        'decline_reason_other',
        'sent_at',
        'opened_at',
        'viewed_at',
        'accepted_at',
        'declined_at',
        'bid_submitted_at',
        'expires_at',
        'first_reminder_sent_at',
        'final_notice_sent_at',
        'accepted_bid_reminder_sent_at',
    ];

    protected $casts = [
        'credits_to_unlock' => 'integer',
        'match_score' => 'decimal:2',
        'match_reasons' => 'array',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'bid_submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'first_reminder_sent_at' => 'datetime',
        'final_notice_sent_at' => 'datetime',
        'accepted_bid_reminder_sent_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'invite_key';
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(VendorOpportunity::class, 'vendor_opportunity_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function creditsTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'credits_transaction_id');
    }

    public function bid(): HasOne
    {
        return $this->hasOne(Bid::class, 'vendor_opportunity_invitation_id');
    }

    public function hasResponded(): bool
    {
        return ! in_array($this->status, [self::STATUS_NEW, self::STATUS_VIEWED], true);
    }

    public function buyerDetailsUnlocked(): bool
    {
        return $this->accepted_at !== null;
    }

    public function bidWindowExpired(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }

    public function isActionable(): bool
    {
        return ! $this->opportunity?->isClosed();
    }
}
