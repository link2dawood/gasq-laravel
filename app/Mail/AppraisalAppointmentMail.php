<?php

namespace App\Mail;

use App\Models\AppraisalAppointment;
use App\Models\AppraisalReview;
use App\Services\ProcessEmailCopies;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;

class AppraisalAppointmentMail extends Mailable
{
    public const PASSWORD_NOTICE = 'Your report password will be provided at the time of your appointment. Please save the attached PDF and have it ready so we can open it together and review your results.';

    public function __construct(public AppraisalReview $review, public AppraisalAppointment $appointment, public string $kind, public bool $testDelivery = false) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->kind) {
            'report' => 'Your GASQ Certified™ Cost to Protect™ Appraisal Report & Review Appointment',
            'booking' => 'Your GASQ Report Review Appointment Confirmation',
            'rescheduled' => 'Your GASQ Report Review Appointment Has Changed',
            'canceled' => 'Your GASQ Report Review Appointment Is Canceled',
            'reminder_24h' => 'Reminder: Your GASQ Report Review Is in 24 Hours',
            'reminder_1h' => 'Reminder: Your GASQ Report Review Is in 1 Hour',
            default => throw new \InvalidArgumentException('Unknown appraisal message.'),
        };

        return new Envelope(subject: ($this->testDelivery ? '[TEST] ' : '').$subject,
            bcc: $this->kind === 'report' ? app(ProcessEmailCopies::class)->recipients('estimates', array_column($this->to, 'address')) : [],
            replyTo: [new Address(config('appraisal_reviews.reply_to'), 'Get A Security Quote (GASQ) Team')]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appraisal-appointment', with: [
            'review' => $this->review, 'appointment' => $this->appointment, 'kind' => $this->kind,
            'passwordNotice' => self::PASSWORD_NOTICE,
            'testDelivery' => $this->testDelivery,
            'when' => $this->appointment->starts_at->setTimezone($this->appointment->timezone),
        ]);
    }

    public function attachments(): array
    {
        if ($this->kind !== 'report') {
            return [];
        }
        $bytes = Storage::disk('local')->get($this->review->pdf_path);
        if (! is_string($bytes) || ! hash_equals($this->review->pdf_sha256, hash('sha256', $bytes))) {
            throw new \RuntimeException('The final protected appraisal failed its integrity check.');
        }

        return [Attachment::fromData(fn () => $bytes, $this->review->report_number.'.pdf')->withMime('application/pdf')];
    }
}
