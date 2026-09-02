<?php

namespace App\Mail;

use App\Models\EstimateFollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class EstimateFollowUpMail extends Mailable
{
    use Queueable;
    public function __construct(public EstimateFollowUp $followUp) {}
    public function build(): self
    {
        return $this->subject('Your GASQ Estimate Is Ready - Here\'s What Happens Next')
            ->view('emails.estimate-follow-up');
    }
}
