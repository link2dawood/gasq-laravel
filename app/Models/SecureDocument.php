<?php

namespace App\Models;

use App\Support\SecureDocument as Flow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer-facing PDF delivered by secure link rather than as an attachment.
 * `status` is lifecycle only; how the buyer behaved comes from the events.
 */
class SecureDocument extends Model
{
    protected $table = 'documents';

    protected $guarded = ['id'];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'allow_download' => 'boolean',
        'allow_print' => 'boolean',
        'watermark_enabled' => 'boolean',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'revoked_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id')->orderByDesc('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DocumentRecipient::class, 'document_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DocumentEvent::class, 'document_id')->latest('created_at');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(DocumentSession::class, 'document_id');
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(DocumentAccessRequest::class, 'document_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /** Readable by anyone at all: sent, not revoked, not expired. */
    public function isOpenable(): bool
    {
        return $this->status === Flow::SENT && ! $this->isRevoked() && ! $this->isExpired();
    }

    public function requiresPassword(): bool
    {
        return Flow::requiresPassword((string) $this->security_level) && $this->password_hash !== null;
    }

    public function typeLabel(): string
    {
        return Flow::typeLabel((string) $this->document_type);
    }

    public function recipientFor(string $email): ?DocumentRecipient
    {
        return $this->recipients()->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first();
    }
}
