<?php

namespace App\Contracts;

/** The scheduling account the appraisal flow reads bookings from. */
interface AppraisalScheduler
{
    /**
     * Read one booking into the appraisal flow.
     *
     * @param  string  $reference  The provider's identifier for the booking, as stored on the appointment.
     * @param  string|null  $knownReviewId  Set when an administrator links a booking to a named review.
     * @return bool Whether the booking belongs to the appraisal flow and was applied.
     */
    public function ingest(string $reference, ?string $knownReviewId = null): bool;
}
