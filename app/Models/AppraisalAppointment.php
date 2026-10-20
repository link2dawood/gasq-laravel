<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class AppraisalAppointment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'source_created_at' => 'immutable_datetime', 'confirmed_at' => 'immutable_datetime'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(AppraisalReview::class, 'appraisal_review_id');
    }

    public function viewUrl(): string
    {
        return URL::temporarySignedRoute('appraisal-appointments.show', $this->ends_at->addDays(7), ['appointment' => $this->id]);
    }

    public function confirmationUrl(): string
    {
        return $this->viewUrl().'#attendance';
    }
}
