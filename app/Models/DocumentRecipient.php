<?php

namespace App\Models;

use App\Support\SecureDocument as Flow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One person authorised to open one document. Access is held here rather than
 * on the document, so revoking one viewer leaves the others working.
 */
class DocumentRecipient extends Model
{
    public const INVITED = 'invited';

    public const VERIFIED = 'verified';

    public const REVOKED = 'revoked';

    protected $guarded = ['id'];

    protected $casts = [
        'verified_at' => 'datetime',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'document_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(DocumentAccessToken::class, 'document_recipient_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(DocumentSession::class, 'document_recipient_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null || $this->access_status === self::REVOKED;
    }

    public function isPrimary(): bool
    {
        return $this->recipient_type === Flow::RECIPIENT_PRIMARY;
    }

    public function displayName(): string
    {
        return $this->name ?: $this->email;
    }
}
