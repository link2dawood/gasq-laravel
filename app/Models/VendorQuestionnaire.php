<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorQuestionnaire extends Model
{
    public const TOTAL_STEPS = 6;

    public const DOCUMENT_TYPES = [
        'state_security_license' => 'State Security License',
        'coi' => 'Certificate of Insurance',
        'w9' => 'W-9',
        'capability_statement' => 'Capability Statement',
        'workers_comp' => "Workers' Compensation Certificate",
        'general_liability' => 'General Liability Certificate',
        'business_license' => 'Business License',
    ];

    protected $fillable = [
        'bid_id',
        'vendor_id',
        'job_posting_id',
        'status',
        'current_step',
        'responses',
        'is_responsive',
        'responsive_failures',
        'is_responsible',
        'responsible_failures',
        'buyer_review_token',
        'buyer_review_expires_at',
        'submitted_at',
    ];

    protected $casts = [
        'responses' => 'array',
        'responsive_failures' => 'array',
        'responsible_failures' => 'array',
        'is_responsive' => 'boolean',
        'is_responsible' => 'boolean',
        'buyer_review_expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorQuestionnaireDocument::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function answer(string $key, mixed $default = null): mixed
    {
        return data_get($this->responses ?? [], $key, $default);
    }

    public function buyerReviewUrl(): ?string
    {
        if (! $this->buyer_review_token) {
            return null;
        }
        return route('vendor-questionnaires.buyer-review', ['token' => $this->buyer_review_token]);
    }
}
