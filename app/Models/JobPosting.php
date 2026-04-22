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
        'zip_code',
        'latitude',
        'longitude',
        'google_place_id',
        'service_start_date',
        'service_end_date',
        'guards_per_shift',
        'budget_min',
        'budget_max',
        'description',
        'status',
        'property_type',
        'special_requirements',
        'questionnaire_data',
        'expires_at',
    ];

    protected $casts = [
        'service_start_date' => 'date',
        'service_end_date' => 'date',
        'expires_at' => 'datetime',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
        'special_requirements' => 'array',
        'questionnaire_data' => 'array',
    ];

    public function questionnaire(string $key, mixed $default = null): mixed
    {
        return data_get($this->questionnaire_data ?? [], $key, $default);
    }

    public function hasGeoPoint(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && is_numeric($this->latitude)
            && is_numeric($this->longitude);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }
}
