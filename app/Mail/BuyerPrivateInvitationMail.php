<?php

namespace App\Mail;

use App\Models\VendorLeadInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * The GASQ private invitation a vendor sends a buyer to open the Buyer Intake Form.
 *
 * Every placeholder in the approved copy is filled from the sending vendor's account
 * and the buyer's name, so the vendor never edits the wording. Reply-To is the vendor,
 * and the vendor is copied, so the thread belongs to them rather than to the platform.
 */
class BuyerPrivateInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public VendorLeadInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Private Invitation to Begin Your Security Services Estimate',
            replyTo: [new Address($this->vendor()->email, $this->vendorContactName())],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.buyer-private-invitation',
            with: [
                'buyerFirstName' => $this->buyerFirstName(),
                'vendorContactName' => $this->vendorContactName(),
                'vendorCompanyName' => $this->vendorCompanyName(),
                'vendorPhone' => $this->vendor()->phone,
                'vendorEmail' => $this->vendor()->email,
                'invitationUrl' => $this->invitation->invitationUrl(),
            ],
        );
    }

    public function vendor(): \App\Models\User
    {
        return $this->invitation->vendor;
    }

    /** "Hi Avery," reads better than "Hi Avery Buyer," so greet with the first name only. */
    public function buyerFirstName(): string
    {
        $first = Str::of($this->invitation->buyer_name)->trim()->explode(' ')->first();

        return is_string($first) && $first !== '' ? $first : $this->invitation->buyer_name;
    }

    public function vendorContactName(): string
    {
        return (string) ($this->vendor()->name ?: $this->vendor()->company);
    }

    public function vendorCompanyName(): string
    {
        return (string) ($this->vendor()->company ?: $this->vendor()->name);
    }
}
