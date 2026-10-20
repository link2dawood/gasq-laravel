<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalDelivery extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['appointment_starts_at' => 'immutable_datetime'];
    }
}
