<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Scenario extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'status',
        'workbook_version',
        'assumptions',
        'vehicle',
        'meta',
        'coverage_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'assumptions' => 'array',
            'vehicle' => 'array',
            'meta' => 'array',
            'coverage_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(ScenarioSite::class)->orderBy('sort_order');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ScenarioPost::class)->orderBy('sort_order');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(ScenarioShift::class)->orderBy('sort_order');
    }

    /**
     * Coverage / staffing scope (spreadsheet Post_Positions X-block / ScopeInputs).
     */
    public function coverageScope(): HasOne
    {
        return $this->hasOne(ScenarioScopeRequirement::class);
    }
}
