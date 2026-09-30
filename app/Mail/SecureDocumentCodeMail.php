<?php

namespace App\Mail;

use App\Models\DocumentRecipient;
use App\Models\SecureDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The six-digit code that opens a secure document (spec 11). */
class SecureDocumentCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SecureDocument $document,
        public DocumentRecipient $recipient,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your verification code: {$this->code}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.secure-document-code');
    }
}
