<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TokenNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $type,
        public string $userName,
        public int $tokensChange,
        public int $currentBalance,
        public ?string $reason = null,
        public ?int $poolAvailable = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'bonus', 'grant' => 'Bonus credits received',
            'free_pool', 'free_pool_refresh' => 'Monthly free credits available',
            'purchase' => 'Purchase confirmed',
            default => 'Credits updated',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.token-notification');
    }
}
