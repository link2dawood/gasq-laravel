<?php

namespace App\Mail;

use App\Models\JobPosting;
use App\Models\VendorOpportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BuyerQualificationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public JobPosting $job,
        public VendorOpportunity $opportunity,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Qualification Approved – Your Security Request Is Now Live',
        );
    }

    public function content(): Content
    {
        $target = (int) ($this->opportunity->vendor_target_count ?? 5);
        $accepted = (int) ($this->opportunity->invitations()
            ->whereIn('status', ['accepted', 'bid_submitted'])->count() ?? 0);

        return new Content(
            view: 'emails.buyer-qualification-approved',
            with: [
                'job' => $this->job,
                'opportunity' => $this->opportunity,
                'dashboardUrl' => route('jobs.show', $this->job),
                'vendorTargetCount' => $target,
                'vendorsAccepted' => $accepted,
                'openSlots' => max($target - $accepted, 0),
                'responseActivity' => $accepted > 0 ? 'Active' : 'Just released',
                'interviewPhase' => $accepted > 0 ? 'Scheduling' : 'Pending',
            ],
        );
    }
}
