<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorLeadLink extends Model
{
    protected $fillable = ['vendor_id', 'token'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function invitationUrl(): string
    {
        return route('buyer-enquiries.show', ['token' => $this->token]);
    }
}
