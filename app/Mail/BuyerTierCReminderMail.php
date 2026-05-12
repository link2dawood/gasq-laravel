<?php

namespace App\Mail;

use App\Models\JobPosting;
use App\Models\VendorOpportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BuyerTierCReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public JobPosting $job,
        public VendorOpportunity $opportunity,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Update Your GASQ Qualification to Activate Your Request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.buyer-tier-c-reminder',
            with: [
                'job' => $this->job,
                'opportunity' => $this->opportunity,
                'updateUrl' => route('jobs.edit', $this->job),
            ],
        );
    }
}
