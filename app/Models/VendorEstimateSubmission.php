<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEstimateSubmission extends Model
{
    protected $fillable = [
        'vendor_id',
        'job_posting_id',
        'buyer_id',
        'snapshot',
        'pdf_path',
        'access_token',
        'credits_spent',
        'emailed_at',
        'viewed_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'emailed_at' => 'datetime',
        'viewed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }
}
