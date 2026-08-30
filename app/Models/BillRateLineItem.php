<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One cost line inside a bill-rate breakdown (spec §34, §35).
 *
 * The resource classification is what makes a "shared" claim auditable: a vendor cannot
 * simply reduce a supervision cost, it must say what is shared and how it was allocated.
 */
class BillRateLineItem extends Model
{
    public const CLASS_DIRECT_LABOR = 'direct_labor';
    public const CLASS_DEDICATED = 'dedicated';
    public const CLASS_SHARED = 'shared';
    public const CLASS_ACCOUNT_SPECIFIC = 'account_specific';

    public const CLASSIFICATIONS = [
        self::CLASS_DIRECT_LABOR => 'Direct labour',
        self::CLASS_DEDICATED => 'Dedicated resource',
        self::CLASS_SHARED => 'Shared resource',
        self::CLASS_ACCOUNT_SPECIFIC => 'Account-specific / pass-through',
    ];

    /** Cost categories from spec §34 A–I. */
    public const CATEGORIES = [
        'direct_labor' => 'Direct labour',
        'employer_costs' => 'Employer-related costs',
        'workforce_maintenance' => 'Workforce maintenance / relief',
        'operating' => 'Operating costs',
        'supervision' => 'Supervision',
        'administrative' => 'Administrative / company support',
        'insurance' => 'Insurance',
        'vehicle' => 'Vehicle / fleet',
        'profit' => 'Profit',
    ];

    protected $fillable = [
        'bill_rate_breakdown_id', 'category', 'label', 'amount',
        'resource_classification', 'allocation_method', 'shared_across_accounts',
        'unallocated_amount', 'notes', 'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'unallocated_amount' => 'decimal:4',
        'shared_across_accounts' => 'integer',
        'sort_order' => 'integer',
    ];

    public function breakdown(): BelongsTo
    {
        return $this->belongsTo(BillRateBreakdown::class, 'bill_rate_breakdown_id');
    }

    public function isShared(): bool
    {
        return $this->resource_classification === self::CLASS_SHARED;
    }

    /**
     * A shared line must show what is being shared and across how much volume
     * (spec §36) — otherwise the allocation cannot be validated.
     */
    public function sharedAllocationIsSupported(): bool
    {
        if (! $this->isShared()) {
            return true;
        }

        return filled($this->allocation_method)
            && (int) $this->shared_across_accounts > 0;
    }
}
