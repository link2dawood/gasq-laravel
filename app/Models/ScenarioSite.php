<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenarioSite extends Model
{
    protected $fillable = [
        'scenario_id',
        'sort_order',
        'name',
        'address_line1',
        'city',
        'state',
        'zip',
        'country',
        'latitude',
        'longitude',
        'google_place_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ScenarioPost::class);
    }
}
