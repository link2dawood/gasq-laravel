<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Someone who was not invited asking to be let in, or a download request. */
class DocumentAccessRequest extends Model
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const DENIED = 'denied';

    public const EXPIRED = 'expired';

    protected $guarded = ['id'];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'document_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
