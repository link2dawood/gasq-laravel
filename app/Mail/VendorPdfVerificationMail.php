<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VendorPdfVerificationMail extends Mailable
{
    public function __construct(public string $code, public string $estimateNumber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your GASQ PDF access verification code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-pdf-verification');
    }
}
