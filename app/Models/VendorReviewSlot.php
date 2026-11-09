<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VendorReviewSlot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'active' => 'boolean'];
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(VendorReviewAppointment::class, 'active_slot_id');
    }
}
