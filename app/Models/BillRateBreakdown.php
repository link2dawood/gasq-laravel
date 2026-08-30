<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A submitted line-item bill-rate structure (spec §32, §34, §38).
 *
 * A single lump-sum hourly rate is never sufficient for Shared Resource pricing: the
 * components must be declared and must reconcile to the proposed rate.
 */
class BillRateBreakdown extends Model
{
    public const MODEL_STANDARD = 'standard';
    public const MODEL_SHARED_RESOURCE = 'shared_resource';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_FINANCIAL_REVIEW_PENDING = 'financial_review_pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Money tolerance for reconciliation. Line items carry 4dp so allocations like
     * $3.50 / 6 accounts do not force the vendor to fudge a rounded figure, but the
     * total must still land on the proposed rate to the cent.
     */
    public const RECONCILE_TOLERANCE = 0.005;

    protected $fillable = [
        'vendor_id', 'job_posting_id', 'version', 'pricing_model',
        'proposed_bill_rate', 'line_items_total', 'reconciles', 'status',
        'additional_charges', 'certified', 'certified_at', 'reviewed_by',
        'submitted_at', 'approved_at', 'review_notes',
    ];

    protected $casts = [
        'version' => 'integer',
        'proposed_bill_rate' => 'decimal:2',
        'line_items_total' => 'decimal:2',
        'reconciles' => 'boolean',
        'certified' => 'boolean',
        'additional_charges' => 'array',
        'certified_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(BillRateLineItem::class)->orderBy('sort_order');
    }

    public function isSharedResource(): bool
    {
        return $this->pricing_model === self::MODEL_SHARED_RESOURCE;
    }

    /** Sum of the declared line items. */
    public function lineItemsSum(): float
    {
        return (float) $this->lineItems->sum(fn ($i) => (float) $i->amount);
    }

    /** Spec §38 / RULE 12 — components must equal the submitted rate. */
    public function reconcilesWithProposedRate(): bool
    {
        return abs($this->lineItemsSum() - (float) $this->proposed_bill_rate)
            < self::RECONCILE_TOLERANCE;
    }

    /** Shared lines that failed to justify their allocation (spec §36). */
    public function unsupportedSharedLines()
    {
        return $this->lineItems->reject(fn ($i) => $i->sharedAllocationIsSupported());
    }
}
