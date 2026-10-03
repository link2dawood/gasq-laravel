<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How one page of one version held one reader during one session (spec 28).
 *
 * active_seconds is counted the same way as a session's: only while the reader
 * was actually there. view_count is arrivals at this page within the session,
 * so a page returned to three times reads as three.
 */
class DocumentPageView extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DocumentSession::class, 'document_session_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'document_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(DocumentRecipient::class, 'document_recipient_id');
    }
}
