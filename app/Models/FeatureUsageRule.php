<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureUsageRule extends Model
{
    protected $fillable = [
        'feature_key',
        'feature_name',
        'tokens_required',
        'description',
        'is_active',
    ];

    protected $casts = [
        'tokens_required' => 'integer',
        'is_active' => 'boolean',
    ];
}
