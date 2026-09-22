<?php

namespace App\Mail;

use App\Models\PrivateEstimate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The six-digit code that opens the buyer portal (spec 5). */
class PrivateEstimateOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PrivateEstimate $estimate,
        public string $code,
        public int $expiresInMinutes = 10,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your verification code: {$this->code}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.private-estimate-otp');
    }
}
