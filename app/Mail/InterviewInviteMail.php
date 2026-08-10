<?php

namespace App\Mail;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to a vendor when a buyer invites them to interview. */
class InterviewInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Interview $interview) {}

    public function envelope(): Envelope
    {
        $title = $this->interview->jobPosting?->title ?? 'a security opportunity';

        return new Envelope(subject: "You're invited to interview — {$title}");
    }

    public function content(): Content
    {
        $job = $this->interview->jobPosting;

        return new Content(
            view: 'emails.interview-invite',
            with: [
                'interview' => $this->interview,
                'job' => $job,
                'buyer' => $job?->user,
                'vendorName' => $this->interview->vendor?->name ?: 'there',
                'scheduleUrl' => route('interviews.vendor.schedule', $this->interview),
                'listUrl' => route('interviews.vendor.index'),
            ],
        );
    }
}
