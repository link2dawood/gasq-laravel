<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterInputProfile extends Model
{
    protected $fillable = [
        'user_id',
        'inputs',
        'is_complete',
    ];

    protected $casts = [
        'inputs' => 'array',
        'is_complete' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

