<?php

namespace App\Mail;

use App\Models\PrivateEstimate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "You've been invited to receive a private security service estimate" (spec 4).
 * Carries the one-time invitation link; the code that opens the portal is sent
 * separately, so the link alone is never enough.
 */
class PrivateEstimateInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PrivateEstimate $estimate,
        public string $url,
        public ?\DateTimeInterface $expiresAt = null,
    ) {}

    public function envelope(): Envelope
    {
        $vendor = $this->estimate->vendor?->company
            ?: $this->estimate->vendor?->vendorProfile?->company_name
            ?: ($this->estimate->vendor?->name ?? 'A security vendor');

        return new Envelope(
            subject: "You've Been Invited to Receive a Private Security Service Estimate from {$vendor}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.private-estimate-invitation',
            with: [
                'vendorName' => $this->estimate->vendor?->company
                    ?: $this->estimate->vendor?->vendorProfile?->company_name
                    ?: ($this->estimate->vendor?->name ?? 'A security vendor'),
            ],
        );
    }
}
