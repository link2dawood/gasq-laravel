<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorReviewAppointment extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['manage_token', 'manage_token_hash'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'canceled_at' => 'immutable_datetime', 'manage_token' => 'encrypted'];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(VendorEstimateDelivery::class, 'vendor_estimate_delivery_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(VendorAppointmentMessage::class);
    }

    public function manageUrl(): string
    {
        return route('vendor-review.manage', ['token' => $this->manage_token]);
    }
}
