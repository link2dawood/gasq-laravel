<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEstimateDelivery extends Model
{
    public const STATUSES = ['queued' => 'Queued', 'sending' => 'Sending', 'sent' => 'Sent (transport accepted)', 'failed' => 'Failed before send', 'uncertain' => 'Needs delivery review'];

    protected $guarded = ['id'];

    protected $hidden = ['scheduling_token', 'scheduling_token_hash', 'scheduling_target', 'pdf_path', 'password_hash', 'released_password'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'native_scheduling' => 'boolean', 'released_password' => 'encrypted', 'password_released_at' => 'datetime', 'access_expires_at' => 'datetime', 'access_revoked_at' => 'datetime', 'scheduling_token' => 'encrypted', 'scheduling_expires_at' => 'datetime', 'scheduling_opened_at' => 'datetime', 'claimed_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(VendorLeadEstimate::class, 'vendor_lead_estimate_id');
    }

    public function schedulingUrl(): string
    {
        return route('vendor-estimate-scheduling', ['token' => $this->scheduling_token]);
    }

    public function accessUrl(): string
    {
        return route('vendor-pdf.show', ['token' => $this->scheduling_token]);
    }
}
