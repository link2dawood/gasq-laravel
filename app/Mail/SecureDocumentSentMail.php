<?php

namespace App\Mail;

use App\Models\DocumentRecipient;
use App\Models\SecureDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The delivery email. Carries a button to the recipient's own secure link and
 * never the document itself (spec 2, 22).
 */
class SecureDocumentSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SecureDocument $document,
        public DocumentRecipient $recipient,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->document->typeLabel().' — '.$this->document->public_id);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.secure-document-sent');
    }
}
