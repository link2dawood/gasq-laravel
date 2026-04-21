<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'tokens_change',
        'type',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'description',
        'balance_after',
    ];

    protected $casts = [
        'tokens_change' => 'integer',
        'balance_after' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
