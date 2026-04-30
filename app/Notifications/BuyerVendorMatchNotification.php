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
        $job = $this->opportunity->jobPosting;
        $progress = $this->progressSnapshot();
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hello ' . ($notifiable->name ?? 'Buyer') . ',');

        if ($this->type === self::TYPE_PENDING_QUALIFICATION) {
            return $mail
                ->line('Thank you for your interest in working with GetASecurityQuoteNow (GASQ).')
                ->line('After a thorough review of your questionnaire responses, we regret to inform you that your request does not currently meet our minimum lead qualification standards required for distribution to our prequalified vendor network.')
                ->line('At least one or more responses did not align with our core qualification criteria, which are designed to ensure:')
                ->line('- Verified decision-maker authority')
                ->line('- Confirmed budget or pricing alignment')
                ->line('- Commitment to interview and engage qualified vendors')
                ->line('- Readiness to move forward under established service conditions')
                ->line('Because of this, your request has been placed on Pending Qualification Status and will not be released to vendors at this time.')
                ->line('What You Can Do Next:')
                ->line('We encourage you to revisit and update your responses to meet the qualification requirements. Once your submission reflects a qualified status, we will promptly re-evaluate and activate your request for vendor distribution.')
                ->line('Important Note:')
                ->line('All qualified submissions benefit from access to our pre-screened vendor network, the Vendor Replacement Guarantee, the Price Lock Guarantee, and structured interview coordination and bid acceptance process.')
                ->line('Next Step: Please update your questionnaire to proceed.')
                ->action('Review Qualification Status', route('jobs.edit', $job));
        }

        if ($this->type === self::TYPE_LIVE) {
            return $mail
                ->line('Thank you for completing your submission with GetASecurityQuoteNow (GASQ).')
                ->line('We\'re pleased to inform you that your request has been successfully qualified and is now ACTIVE within our pre-screened vendor network.')
                ->line('Qualification Status: APPROVED')
                ->line('Your submission meets all required criteria:')
                ->line('- Verified decision-maker authority')
                ->line('- Confirmed budget and pricing alignment')
                ->line('- Commitment to interview qualified vendors')
                ->line('- Ready-to-move-forward status')
                ->line('Real-Time Vendor Match Status')
                ->line('- Vendors Accepted: ' . $progress['accepted_count'] . ' of ' . $progress['max_accepts'])
                ->line('- Open Slots Remaining: ' . max($progress['max_accepts'] - $progress['accepted_count'], 0))
                ->line('- Response Activity: Active')
                ->line('- Interview Phase: Pending')
                ->line('(This status updates automatically as vendors accept your request.)')
                ->line('What Happens Next')
                ->line('Your project is now visible to qualified vendors. As they review your request:')
                ->line('- Vendors will accept or decline based on your approved price and scope')
                ->line('- Once accepted, vendors will move into the interview scheduling phase')
                ->line('- You will receive instant notifications as activity occurs')
                ->line('Your Confirmed Commitments')
                ->line('- Interview all qualified vendors prior to selection')
                ->line('- Move forward if a vendor meets your requirements at your approved price')
                ->line('Your GASQ Protections')
                ->line('- Vendor Replacement Guarantee')
                ->line('- Price Lock Guarantee')
                ->line('Next Step: Prepare for interviews. As vendors continue to accept your request, you\'ll be prompted to schedule interviews directly through your dashboard.')
                ->line('GASQ Insight: Projects with strong buyer responsiveness often reach full vendor acceptance faster.')
                ->action('Track Vendor Match', route('jobs.show', $job));
        }

        return $mail
            ->line('Thank you for completing your submission with GetASecurityQuoteNow (GASQ).')
            ->line('Your security service request remains active within our pre-screened vendor network.')
            ->line('Vendor Acceptance Progress')
            ->line($progress['headline'])
            ->line($progress['progress_bar'])
            ->line('Status Message: ' . $progress['status_message'])
            ->line('Next Step: ' . $progress['next_step'])
            ->line('You will continue receiving updates as qualified vendors accept your request and move toward interview coordination.')
            ->action('View Vendor Match', route('jobs.show', $job));
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
