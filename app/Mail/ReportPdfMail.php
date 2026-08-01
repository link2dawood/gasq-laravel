<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<int, array{data: string, name: string, mime: string}> $extraAttachments
     *        Optional survey photos/files to attach alongside the report PDF.
     */
    public function __construct(
        public string $subjectLine,
        public string $pdf,
        public string $filename,
        public string $bodyView = 'emails.report-pdf',
        public array $bodyData = [],
        public array $extraAttachments = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->bodyView,
            with: $this->bodyData,
        );
    }

    public function attachments(): array
    {
        $attachments = [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $this->pdf, $this->filename)
                ->withMime('application/pdf'),
        ];

        // Survey photos/files uploaded by the preparer, attached alongside the report.
        foreach ($this->extraAttachments as $file) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $file['data'],
                $file['name'],
            )->withMime($file['mime'] ?? 'application/octet-stream');
        }

        return $attachments;
    }
}
