<?php

namespace App\Mail;

use App\Models\VendorEstimateSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorEstimateSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VendorEstimateSubmission $submission,
        public string $pdfBinary,
        public string $pdfFilename,
        public string $viewUrl,
    ) {}

    public function envelope(): Envelope
    {
        $vendorName = $this->submission->vendor?->name ?? 'A vendor';
        $jobTitle = $this->submission->jobPosting?->title ?? 'your job';

        return new Envelope(
            subject: "{$vendorName} sent you an estimate for: {$jobTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor-estimate-submitted',
            with: [
                'submission' => $this->submission,
                'viewUrl' => $this->viewUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
