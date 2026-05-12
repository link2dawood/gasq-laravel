<?php

namespace App\Mail;

use App\Models\Bid;
use App\Models\VendorOpportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BidEngagementReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Bid $bid,
        public ?VendorOpportunity $opportunity,
        public string $pdfBinary,
        public string $pdfFilename,
    ) {}

    public function envelope(): Envelope
    {
        $vendorName = $this->bid->user?->name ?? 'A vendor';
        $jobTitle = $this->bid->jobPosting?->title ?? 'your project';

        return new Envelope(
            subject: "📊 GASQ Bid Engagement Report — {$vendorName} for {$jobTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bid-engagement-report',
            with: [
                'bid' => $this->bid,
                'opportunity' => $this->opportunity,
                'jobUrl' => $this->bid->jobPosting ? route('jobs.show', $this->bid->jobPosting) : '#',
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
