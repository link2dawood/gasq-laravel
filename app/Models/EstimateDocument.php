<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * The locked PDF for a scope version. The open password is encrypted at rest
 * and only ever revealed to the vendor, never to the buyer portal.
 */
class EstimateDocument extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'password_requested_at' => 'datetime',
        'password_released_at' => 'datetime',
        'password_regenerated_at' => 'datetime',
        'first_downloaded_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function privateEstimate(): BelongsTo
    {
        return $this->belongsTo(PrivateEstimate::class);
    }

    public function setPassword(string $plain): void
    {
        $this->encrypted_password = Crypt::encryptString($plain);
    }

    /** Vendor-side only. Never send the result to a buyer response. */
    public function password(): ?string
    {
        return $this->encrypted_password ? Crypt::decryptString($this->encrypted_password) : null;
    }

    public function passwordReleased(): bool
    {
        return $this->password_released_at !== null;
    }

    public function downloadsExhausted(): bool
    {
        return $this->download_limit !== null && $this->download_count >= $this->download_limit;
    }
}
