<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'category',
        'location',
        'service_start_date',
        'service_end_date',
        'guards_per_shift',
        'budget_min',
        'budget_max',
        'description',
        'property_type',
        'special_requirements',
        'expires_at',
    ];

    protected $casts = [
        'service_start_date' => 'date',
        'service_end_date' => 'date',
        'expires_at' => 'datetime',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'special_requirements' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }
}
