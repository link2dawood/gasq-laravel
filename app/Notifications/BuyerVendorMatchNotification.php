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

    public const TYPE_LIVE = 'live';
    public const TYPE_ACCEPTED_PROGRESS = 'accepted_progress';
    public const TYPE_PENDING_QUALIFICATION = 'pending_qualification';

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
        // Pending-qualification is fully handled by BuyerQualificationNotApprovedMail
        // dispatched from VendorOpportunityManager. Skip the email here.
        if ($this->type === self::TYPE_PENDING_QUALIFICATION) {
            return (new MailMessage)
                ->subject($this->subject())
                ->view('emails.notifications.buyer-vendor-progress', [
                    'notification' => $this,
                    'notifiable' => $notifiable,
                ]);
        }

        // Both TYPE_LIVE and TYPE_ACCEPTED_PROGRESS render the same branded
        // progress-snapshot view; the template adapts based on acceptedCount.
        return (new MailMessage)
            ->subject($this->subject())
            ->view('emails.notifications.buyer-vendor-progress', [
                'notification' => $this,
                'notifiable' => $notifiable,
            ]);
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
            'progress_bar' => $progress['progress_bar'],
            'status_message' => $progress['status_message'],
            'next_step' => $progress['next_step'],
        ];
    }

    public function smsBody(): string
    {
        $progress = $this->progressSnapshot();

        if ($this->type === self::TYPE_LIVE) {
            return 'GASQ Update: Your project is now live. Status: '
                . $progress['headline']
                . '. Vendors are reviewing your approved bid offer.';
        }

        if ($this->type === self::TYPE_PENDING_QUALIFICATION) {
            return '';
        }

        return 'GASQ Update: ' . $progress['status_message'] . ' Status: ' . $progress['headline'] . '.';
    }

    private function subject(): string
    {
        if ($this->type === self::TYPE_LIVE) {
            return 'Qualification Approved - Your Security Request Is Now Live';
        }

        if ($this->type === self::TYPE_PENDING_QUALIFICATION) {
            return 'Status Update - Service Request Qualification';
        }

        $progress = $this->progressSnapshot();

        return 'Real-Time Vendor Progress Update - ' . $progress['headline'];
    }

    /**
     * @return array{
     *     accepted_count: int,
     *     max_accepts: int,
     *     headline: string,
     *     progress_bar: string,
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
            'progress_bar' => $this->progressBar($acceptedCount, $maxAccepts),
            'status_message' => $copy['status_message'],
            'next_step' => $copy['next_step'],
        ];
    }

    private function progressBar(int $acceptedCount, int $maxAccepts): string
    {
        $filled = str_repeat('[x] ', $acceptedCount);
        $open = str_repeat('[ ] ', max($maxAccepts - $acceptedCount, 0));

        return trim($filled . $open);
    }
}
