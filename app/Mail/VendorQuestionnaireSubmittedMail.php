<?php

namespace App\Mail;

use App\Models\VendorQuestionnaire;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorQuestionnaireSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{data:string, name:string, mime:string}>  $documentAttachments
     */
    public function __construct(
        public VendorQuestionnaire $questionnaire,
        public string $pdfBinary,
        public string $pdfFilename,
        public array $documentAttachments = [],
    ) {}

    public function envelope(): Envelope
    {
        $vendorName = $this->questionnaire->vendor?->name ?? 'A vendor';
        $jobTitle = $this->questionnaire->jobPosting?->title ?? 'your job';

        return new Envelope(
            subject: "{$vendorName} submitted vendor qualification for: {$jobTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor-questionnaire-submitted',
            with: [
                'questionnaire' => $this->questionnaire,
                'reviewUrl' => $this->questionnaire->buyerReviewUrl(),
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [
            Attachment::fromData(fn () => $this->pdfBinary, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];

        foreach ($this->documentAttachments as $doc) {
            $attachments[] = Attachment::fromData(fn () => $doc['data'], $doc['name'])
                ->withMime($doc['mime']);
        }

        return $attachments;
    }
}
