<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The priced result for one scope version, with the raw engine payloads. */
class EstimatePricing extends Model
{
    protected $table = 'estimate_pricing';

    protected $guarded = ['id'];

    protected $casts = [
        'inputs' => 'array',
        'outputs' => 'array',
        'base_wage' => 'float',
        'employer_burden' => 'float',
        'workforce_maintenance_hours' => 'float',
        'workforce_maintenance_cost' => 'float',
        'profit_margin' => 'float',
        'cost_to_deliver' => 'float',
        'final_bill_rate' => 'float',
        'weekly_cost' => 'float',
        'monthly_cost' => 'float',
        'annual_cost' => 'float',
        'capital_recovery' => 'float',
    ];

    public function privateEstimate(): BelongsTo
    {
        return $this->belongsTo(PrivateEstimate::class);
    }
}
