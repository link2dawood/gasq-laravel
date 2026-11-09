<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Number;

class VendorLeadEstimate extends Model
{
    public const STATUSES = ['draft' => 'Draft', 'ready' => 'Ready', 'archived' => 'Archived'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['buyer' => 'array', 'valid_until' => 'date', 'hourly_rate' => 'decimal:2', 'weekly_hours' => 'decimal:2', 'one_time_cost' => 'decimal:2', 'weeks_per_year' => 'integer'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(VendorBuyerLead::class, 'vendor_buyer_lead_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(VendorEstimateDelivery::class)->latest('id');
    }

    public function latestDelivery(): HasOne
    {
        return $this->hasOne(VendorEstimateDelivery::class)->latestOfMany();
    }

    public function totals(): array
    {
        $weeklyCents = (int) round((float) $this->hourly_rate * (float) $this->weekly_hours * 100);
        $annualCents = $weeklyCents * $this->weeks_per_year;

        return [
            'weekly' => $weeklyCents / 100,
            'monthly' => round($annualCents / 12) / 100,
            'annual' => $annualCents / 100,
            'first_year' => ($annualCents + (int) round((float) $this->one_time_cost * 100)) / 100,
            'annual_hours' => (float) $this->weekly_hours * $this->weeks_per_year,
        ];
    }

    public function money(float|int|string $value): string
    {
        return Number::currency((float) $value, in: $this->currency);
    }
}
