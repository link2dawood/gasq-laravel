<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A private invitation sent to one buyer, and how far they got with it.
 *
 * Status is derived rather than stored, so it can never drift from the timestamps
 * that produced it.
 */
class VendorLeadInvitation extends Model
{
    protected $fillable = [
        'vendor_id', 'vendor_buyer_lead_id', 'invite_key', 'buyer_name', 'buyer_email',
        'link_token', 'sent_at', 'opened_at', 'open_count', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'completed_at' => 'datetime',
            'open_count' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(VendorBuyerLead::class, 'vendor_buyer_lead_id');
    }

    /** The buyer's own link: the vendor's shared token plus the key that identifies them. */
    public function invitationUrl(): string
    {
        return route('buyer-enquiries.show', ['token' => $this->link_token, 'i' => $this->invite_key]);
    }

    public function status(): string
    {
        return match (true) {
            $this->completed_at !== null => 'completed',
            $this->opened_at !== null => 'opened',
            default => 'sent',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'completed' => 'Form completed',
            'opened' => 'Link opened',
            default => 'Invitation sent',
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status()) {
            'completed' => 'success',
            'opened' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Record that the buyer's link was loaded.
     *
     * The first load is the one that matters, so `opened_at` is never overwritten;
     * `open_count` keeps the repeat visits. Loads after the form is completed are
     * ignored, since the buyer is only being shown the confirmation at that point.
     */
    public function recordOpen(): void
    {
        if ($this->completed_at !== null) {
            return;
        }

        $this->forceFill([
            'opened_at' => $this->opened_at ?? now(),
            'open_count' => $this->open_count + 1,
        ])->save();
    }
}
