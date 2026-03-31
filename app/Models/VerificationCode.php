<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationCode extends Model
{
    protected $table = 'verification_codes';

    protected $fillable = [
        'user_id',
        'code',
        'code_hash',
        'type',
        'phone_number',
        'email',
        'status',
        'expires_at',
        'attempts',
        'verified_at',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

