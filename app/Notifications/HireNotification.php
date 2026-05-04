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
        $hiredBid = $this->job->hiredBid;
        $vendorName = $hiredBid?->user?->name
            ?? $this->job->hired_external_name
            ?? 'a professional';
        $url = route('jobs.show', $this->job);

        $mail = (new MailMessage)->action('View Job', $url);

        if ($this->audience === 'vendor') {
            return $mail
                ->subject('Congratulations — you were hired: ' . $this->job->title)
                ->line("The buyer hired you for \"{$this->job->title}\".")
                ->line('They will be in touch with next steps.');
        }

        if ($this->audience === 'other_vendors') {
            return $mail
                ->subject('Job awarded to another vendor: ' . $this->job->title)
                ->line("The buyer has hired another vendor for \"{$this->job->title}\".")
                ->line('Thank you for your bid.');
        }

        // buyer confirmation
        return $mail
            ->subject('Hire confirmed: ' . $this->job->title)
            ->line("You hired {$vendorName} for \"{$this->job->title}\".")
            ->line('We sent them a confirmation email.');
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
