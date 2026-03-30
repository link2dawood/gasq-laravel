<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioShift extends Model
{
    protected $fillable = [
        'scenario_id',
        'scenario_post_id',
        'sort_order',
        'label',
        'hours_per_week',
        'pattern',
    ];

    protected function casts(): array
    {
        return [
            'hours_per_week' => 'decimal:2',
            'pattern' => 'array',
        ];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ScenarioPost::class, 'scenario_post_id');
    }
}
