<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One viewing session. active_seconds counts time the viewer was actually in
 * front of the document, not the time a tab sat open.
 */
class DocumentSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'document_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(DocumentRecipient::class, 'document_recipient_id');
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }
}
