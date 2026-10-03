<?php

namespace App\Mail;

use App\Models\DocumentAccessRequest;
use App\Models\SecureDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Someone holding a forwarded link has asked to be let in (spec 40).
 *
 * The viewer is told GASQ has been notified, so this is what makes that true.
 * It carries no document, only who asked and where to decide.
 */
class SecureDocumentAccessRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SecureDocument $document,
        public DocumentAccessRequest $accessRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[GASQ Admin] Access requested — '.$this->document->public_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.secure-document-access-request',
            with: [
                'adminUrl' => route('admin.secure-documents.show', $this->document),
            ],
        );
    }
}
