<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenarioPost extends Model
{
    protected $fillable = [
        'scenario_id',
        'scenario_site_id',
        'sort_order',
        'post_name',
        'position_title',
        'location_text',
        'qty_required',
        'weekly_hours',
        'pay_rate_mode',
        'wage_mode',
        'manual_pay_wage',
        'manual_bill_rate',
    ];

    protected function casts(): array
    {
        return [
            'weekly_hours' => 'decimal:2',
            'manual_pay_wage' => 'decimal:4',
            'manual_bill_rate' => 'decimal:4',
        ];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(ScenarioSite::class, 'scenario_site_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(ScenarioShift::class);
    }
}
