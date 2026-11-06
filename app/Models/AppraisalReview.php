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

    /**
     * The booking link for this buyer, carrying the review reference so the
     * booking comes back attached to this appraisal, or null when the
     * scheduling account is not configured yet.
     */
    public function bookingUrl(): ?string
    {
        $reference = 'gasq_appraisal_'.$this->id;
        $bookafy = config('appraisal_reviews.provider') === 'bookafy';
        $url = $bookafy ? config('appraisal_reviews.bookafy.booking_url') : config('appraisal_reviews.booking_url');
        if (! $url) {
            return null;
        }
        $query = ['name' => $this->buyer_name, 'email' => $this->buyer_email];
        // The embedded Bookafy link already carries query parameters of its own.
        $query[$bookafy ? config('appraisal_reviews.bookafy.reference_field') : 'utm_content'] = $reference;

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query);
    }
}
