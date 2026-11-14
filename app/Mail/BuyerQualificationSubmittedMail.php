<?php

namespace App\Mail;

use App\Models\VendorBuyerLead;
use App\Support\BuyerQualificationQuestionnaire;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A completed Buyer Intake Form (the GASQ Security Services Opportunity Qualification
 * Form), sent in two versions from one template.
 *
 * The vendor's version is the working copy: it carries the internal review outcome, the
 * list of answers to clarify and a link into the lead. The buyer's version is a receipt
 * of what they submitted and deliberately omits the internal review, which is the
 * vendor's own assessment and not something to put in front of the buyer.
 */
class BuyerQualificationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VendorBuyerLead $lead,
        public bool $forBuyer = false,
    ) {}

    public function envelope(): Envelope
    {
        if ($this->forBuyer) {
            return new Envelope(
                subject: 'Your completed GASQ Buyer Intake Form',
                replyTo: array_filter([$this->vendorAddress()]),
            );
        }

        $who = $this->lead->company ?: $this->lead->contact_name;
        $status = $this->lead->qualificationStatusLabel();

        return new Envelope(
            subject: "GASQ Buyer Intake Form: {$who} ({$status})",
            replyTo: [new Address($this->lead->email, $this->lead->contact_name)],
        );
    }

    public function content(): Content
    {
        $vendor = $this->lead->vendor;

        return new Content(
            view: 'emails.buyer-qualification-submitted',
            with: [
                'lead' => $this->lead,
                'vendor' => $vendor,
                'forBuyer' => $this->forBuyer,
                'vendorContactName' => (string) ($vendor?->name ?: $vendor?->company),
                'vendorCompanyName' => (string) ($vendor?->company ?: $vendor?->name),
                'vendorEmail' => (string) $vendor?->email,
                'vendorPhone' => $vendor?->phone,
                'sections' => BuyerQualificationQuestionnaire::answerSummary($this->lead),
                'clarifications' => $this->forBuyer ? [] : BuyerQualificationQuestionnaire::clarificationItems($this->lead),
                'leadsUrl' => $this->forBuyer ? null : route('vendor-buyer-leads.index', ['lead' => $this->lead->id]),
            ],
        );
    }

    private function vendorAddress(): ?Address
    {
        $vendor = $this->lead->vendor;

        return $vendor?->email ? new Address($vendor->email, (string) ($vendor->name ?: $vendor->company)) : null;
    }
}
