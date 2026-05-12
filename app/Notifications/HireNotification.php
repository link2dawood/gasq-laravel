<?php

namespace App\Notifications;

use App\Models\JobPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HireNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public JobPosting $job,
        public string $audience
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $view] = match ($this->audience) {
            'vendor' => ['🎉 Congratulations — You Were Hired: ' . $this->job->title, 'emails.notifications.hire-vendor'],
            'other_vendors' => ['GASQ Update – Opportunity Awarded: ' . $this->job->title, 'emails.notifications.hire-other-vendors'],
            default => ['🤝 Hire Confirmed: ' . $this->job->title, 'emails.notifications.hire-buyer'],
        };

        // Pass `notification` so Blade templates can reach $notification->jobPosting and ->bid.
        return (new MailMessage)
            ->subject($subject)
            ->view($view, [
                'notification' => (object) [
                    'jobPosting' => $this->job,
                    'bid' => $this->job->hiredBid,
                ],
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'hired',
            'audience' => $this->audience,
            'job_id' => $this->job->id,
            'job_title' => $this->job->title,
            'hired_bid_id' => $this->job->hired_bid_id,
            'vendor_name' => $this->job->hiredBid?->user?->name ?? $this->job->hired_external_name,
        ];
    }
}
