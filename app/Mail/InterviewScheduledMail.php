<?php

namespace App\Mail;

use App\Models\Interview;
use App\Support\InterviewCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to both the vendor and the buyer once an interview slot is booked.
 * The .ics calendar file is attached; the body tailors to the recipient.
 */
class InterviewScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param string $audience 'vendor' or 'buyer' */
    public function __construct(public Interview $interview, public string $audience = 'vendor') {}

    public function envelope(): Envelope
    {
        $title = $this->interview->jobPosting?->title ?? 'security interview';
        $when = $this->whenLabel();

        return new Envelope(
            subject: "Interview scheduled — {$title}" . ($when !== '' ? " · {$when}" : ''),
        );
    }

    public function content(): Content
    {
        $job = $this->interview->jobPosting;

        return new Content(
            view: 'emails.interview-scheduled',
            with: [
                'interview' => $this->interview,
                'job' => $job,
                'buyer' => $job?->user,
                'vendor' => $this->interview->vendor,
                'audience' => $this->audience === 'buyer' ? 'buyer' : 'vendor',
                'whenLabel' => $this->whenLabel(),
                'googleUrl' => InterviewCalendar::googleUrl($this->interview),
                'outlookUrl' => InterviewCalendar::outlookUrl($this->interview),
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => InterviewCalendar::ics($this->interview), 'gasq-interview.ics')
                ->withMime('text/calendar'),
        ];
    }

    private function whenLabel(): string
    {
        $at = $this->interview->scheduled_at;

        // Display the stored time as-is, matching the booking screens (which do a
        // plain ->format() with no timezone conversion). The interview timezone is
        // shown as a label in the email body, not applied as a shift.
        return $at ? $at->format('M j, Y · g:i A') : '';
    }
}
