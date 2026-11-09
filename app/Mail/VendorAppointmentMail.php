<?php

namespace App\Mail;

use App\Models\VendorAppointmentMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VendorAppointmentMail extends Mailable
{
    public function __construct(public VendorAppointmentMessage $notice) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->notice->kind) {
            'confirmation' => 'Your GASQ Estimate Review Appointment Is Confirmed',
            'canceled' => 'Your GASQ Estimate Review Appointment Is Canceled',
            'reminder_24h' => 'Reminder: Your GASQ Estimate Review Is in 24 Hours',
            'reminder_1h' => 'Reminder: Your GASQ Estimate Review Is in 1 Hour',
        };
        $appointment = $this->notice->appointment;

        return new Envelope(subject: $subject, replyTo: [new Address($this->notice->audience === 'buyer' ? $appointment->vendor_email : $appointment->buyer_email)]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-review-appointment', with: ['notice' => $this->notice, 'appointment' => $this->notice->appointment]);
    }
}
