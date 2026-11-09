<?php

namespace App\Mail;

use App\Models\VendorEstimateDelivery;
use App\Services\ProcessEmailCopies;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VendorEstimateMail extends Mailable
{
    public function __construct(public VendorEstimateDelivery $delivery, private string $pdf) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your GASQ Estimate Is Ready — Schedule Your Review',
            replyTo: [new Address($this->delivery->snapshot['reply_to'], $this->delivery->snapshot['vendor_name'])],
            bcc: app(ProcessEmailCopies::class)->recipients('estimates', [$this->delivery->recipient]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-lead-estimate', with: ['delivery' => $this->delivery]);
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->pdf, $this->delivery->snapshot['number'].'.pdf')->withMime('application/pdf')];
    }
}
