<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalReview extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['pdf_path', 'pdf_sha256'];

    protected function casts(): array
    {
        return ['findings' => 'array', 'prepared_at' => 'immutable_datetime', 'ready_at' => 'immutable_datetime', 'delivered_at' => 'immutable_datetime'];
    }

    public function calculatorState(): BelongsTo
    {
        return $this->belongsTo(CalculatorState::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(AppraisalAppointment::class);
    }
}
