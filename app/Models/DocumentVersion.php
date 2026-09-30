<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One immutable revision of a document. Superseded versions stay readable. */
class DocumentVersion extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'superseded_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'document_id');
    }

    public function isCurrent(): bool
    {
        return $this->superseded_at === null;
    }
}
