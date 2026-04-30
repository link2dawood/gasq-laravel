<?php

namespace App\Notifications;

use App\Models\VendorOpportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BuyerVendorMatchNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VendorOpportunity $opportunity,
        public string $type,
        public int $acceptedCount
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->opportunity->jobPosting;
        $progress = $this->progressSnapshot();
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hello ' . ($notifiable->name ?? 'Buyer') . ',');

        if ($this->type === 'live') {
            return $mail
                ->line('Your security service request has been approved and released to the GASQ vendor network.')
                ->line('Project Status: Active Vendor Match')
                ->line('Buyer Qualification: Approved')
                ->line('Bid Offer Status: Verified')
                ->line('Vendor Network Release: Live')
                ->line('Current Status: ' . $progress['headline'])
                ->line('Your bid offer has been verified, and qualified vendors are now reviewing your project.')
                ->line('You will receive real-time updates as vendors accept your offer.')
                ->line('Your project is backed by the GASQ Vendor Replacement Guarantee and Price Lock Guarantee.')
                ->action('View Project', route('jobs.show', $job));
        }

        return $mail
            ->line('Vendor Acceptance Progress')
            ->line($progress['headline'])
            ->line('Status Message: ' . $progress['status_message'])
            ->line('Next Step: ' . $progress['next_step'])
            ->action('View Project', route('jobs.show', $job));
    }

    public function toArray(object $notifiable): array
    {
        $progress = $this->progressSnapshot();

        return [
            'type' => $this->type,
            'opportunity_id' => $this->opportunity->id,
            'job_id' => $this->opportunity->job_posting_id,
            'accepted_count' => $progress['accepted_count'],
            'max_accepts' => $progress['max_accepts'],
            'headline' => $progress['headline'],
            'status_message' => $progress['status_message'],
            'next_step' => $progress['next_step'],
        ];
    }

    public function smsBody(): string
    {
        $progress = $this->progressSnapshot();

        if ($this->type === 'live') {
            return 'GASQ Update: Your project is now live. Status: '
                . $progress['headline']
                . '. Vendors are reviewing your approved bid offer.';
        }

        return 'GASQ Update: ' . $progress['status_message'] . ' Status: ' . $progress['headline'] . '.';
    }

    private function subject(): string
    {
        if ($this->type === 'live') {
            return 'Your Security Service Request Is Now Live';
        }

        $progress = $this->progressSnapshot();

        return 'Vendor Match Update: ' . $progress['headline'];
    }

    /**
     * @return array{
     *     accepted_count: int,
     *     max_accepts: int,
     *     headline: string,
     *     status_message: string,
     *     next_step: string
     * }
     */
    private function progressSnapshot(): array
    {
        $maxAccepts = max(1, (int) ($this->opportunity->max_accepts ?: 5));
        $acceptedCount = max(0, min($this->acceptedCount, $maxAccepts));

        $copy = match ($acceptedCount) {
            0 => [
                'status_message' => 'Your project has been released to the GASQ vendor network. Vendors are currently reviewing your approved bid offer.',
                'next_step' => 'You will be notified as each qualified vendor accepts your offer.',
            ],
            1 => [
                'status_message' => 'Good news. Your first qualified vendor has accepted your approved bid offer.',
                'next_step' => 'We are continuing to route your project until additional vendors accept.',
            ],
            2 => [
                'status_message' => 'Your project is gaining traction. Two qualified vendors have accepted your offer.',
                'next_step' => 'You may begin preparing for vendor interviews.',
            ],
            3 => [
                'status_message' => 'Your project has reached strong vendor interest. Three qualified vendors are ready for interview consideration.',
                'next_step' => 'We recommend reviewing vendor profiles and preparing interview times.',
            ],
            4 => [
                'status_message' => 'Your project is nearly fully matched. Four qualified vendors have accepted your approved bid offer.',
                'next_step' => 'Final vendor routing is still active.',
            ],
            default => [
                'status_message' => 'Your project has reached full vendor acceptance. Five qualified vendors are ready for interview and final selection.',
                'next_step' => 'Schedule interviews and select your preferred provider.',
            ],
        };

        return [
            'accepted_count' => $acceptedCount,
            'max_accepts' => $maxAccepts,
            'headline' => sprintf('%d of %d vendors accepted', $acceptedCount, $maxAccepts),
            'status_message' => $copy['status_message'],
            'next_step' => $copy['next_step'],
        ];
    }
}
