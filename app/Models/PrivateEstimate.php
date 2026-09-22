<?php

namespace App\Models;

use App\Support\PrivateEstimate as Flow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A private estimate: one vendor, one invited buyer, versioned scope and pricing.
 * Status changes go through PrivateEstimateService so they stay controlled and
 * logged; do not set `status` directly.
 */
class PrivateEstimate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'estimated_start_date' => 'date',
        'buyer_email_verified_at' => 'datetime',
        'valid_until' => 'datetime',
        'scope_confirmed_at' => 'datetime',
        'vendor_responded_at' => 'datetime',
        'priced_at' => 'datetime',
        'first_viewed_at' => 'datetime',
        'review_confirmed_at' => 'datetime',
        'decided_at' => 'datetime',
        'closed_at' => 'datetime',
        'baseline_wage' => 'float',
        'weekly_hours' => 'float',
        'annual_hours' => 'float',
        'manpower_required' => 'float',
        'current_bill_rate' => 'float',
        'current_annual_cost' => 'float',
        'estimated_bill_rate' => 'float',
        'weekly_cost' => 'float',
        'monthly_cost' => 'float',
        'annual_cost' => 'float',
        'capital_recovery_opportunity' => 'float',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(EstimateInvitation::class);
    }

    public function invitation(): HasOne
    {
        return $this->hasOne(EstimateInvitation::class)->latestOfMany();
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(EstimateScope::class);
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(EstimatePricing::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EstimateDocument::class);
    }

    public function activity(): HasMany
    {
        return $this->hasMany(EstimateActivity::class)->latest('created_at');
    }

    /** The scope the rest of the flow works from. */
    public function currentScope(): ?EstimateScope
    {
        return $this->scopes()->where('version', $this->current_scope_version)->first();
    }

    public function currentPricing(): ?EstimatePricing
    {
        return $this->pricing()->where('scope_version', $this->current_scope_version)->first();
    }

    public function currentDocument(): ?EstimateDocument
    {
        return $this->documents()
            ->where('scope_version', $this->current_scope_version)
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();
    }

    public function buyerVerified(): bool
    {
        return $this->buyer_email_verified_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function statusLabel(): string
    {
        return Flow::label($this->status);
    }

    /** Funding that is not approved is flagged for the vendor (spec 8). */
    public function fundingPending(): bool
    {
        return in_array($this->budget_status, ['pending', 'not_approved'], true);
    }
}
