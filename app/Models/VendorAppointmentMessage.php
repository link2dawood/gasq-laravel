<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAppointmentMessage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['claimed_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(VendorReviewAppointment::class, 'vendor_review_appointment_id');
    }
}
