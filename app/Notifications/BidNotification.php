<?php

namespace App\Notifications;

use App\Models\Bid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Bid $bid,
        public string $type
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->bid->jobPosting;
        $subject = match ($this->type) {
            'submitted' => 'New bid on: ' . $job->title,
            'updated' => 'Bid updated on: ' . $job->title,
            'accepted' => 'Your bid was accepted: ' . $job->title,
            'rejected' => 'Your bid was declined: ' . $job->title,
            'counter_offer' => 'Counter offer on your bid: ' . $job->title,
            default => 'Bid update: ' . $job->title,
        };
        $url = route('jobs.show', $job);

        $mail = (new MailMessage)
            ->subject($subject)
            ->action('View', $url);

        $mail->line(match ($this->type) {
            'submitted' => $this->bid->user->name . ' submitted a bid of $' . number_format($this->bid->amount, 2) . '.',
            'updated' => $this->bid->user->name . ' updated their bid to $' . number_format($this->bid->amount, 2) . '.',
            'accepted' => 'Your bid of $' . number_format($this->bid->amount, 2) . ' was accepted.',
            'rejected' => 'Your bid was declined by the buyer.',
            'counter_offer' => 'The buyer sent a counter offer of $' . number_format($this->bid->counter_offer_amount, 2) . '.',
            default => 'There is an update on your bid.',
        });

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->bid->jobPosting;
        return [
            'type' => $this->type,
            'bid_id' => $this->bid->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'amount' => (float) $this->bid->amount,
            'vendor_name' => $this->bid->user->name,
        ];
    }
}
