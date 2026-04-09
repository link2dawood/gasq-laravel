<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculatorState extends Model
{
    protected $fillable = [
        'user_id',
        'calculator_type',
        'scenario',
        'result',
        'last_ran_at',
    ];

    protected function casts(): array
    {
        return [
            'scenario' => 'array',
            'result' => 'array',
            'last_ran_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
