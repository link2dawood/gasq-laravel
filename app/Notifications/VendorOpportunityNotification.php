<?php

namespace App\Notifications;

use App\Mail\VendorOpportunityInvitedMail;
use App\Models\VendorOpportunityInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VendorOpportunityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VendorOpportunityInvitation $invitation,
        public string $type
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): Mailable|MailMessage
    {
        // Branded HTML invitation for the "new" type.
        if ($this->type === 'new') {
            return (new VendorOpportunityInvitedMail($this->invitation))
                ->to($notifiable->email);
        }

        $job = $this->invitation->opportunity->jobPosting;
        $url = $this->invitationUrl();
        $mail = (new MailMessage)->subject($this->subject($job->location ?? $job->title));

        // Map each notification type to its branded Blade template. Each template
        // extends emails.layouts.gasq-base and receives the invitation + url +
        // notifiable so it can render fully self-contained HTML.
        $view = match ($this->type) {
            'reminder' => 'emails.notifications.vendor-reminder',
            'final_notice' => 'emails.notifications.vendor-final-notice',
            'unlocked' => 'emails.notifications.vendor-unlocked',
            'accepted_bid_reminder' => 'emails.notifications.vendor-accepted-bid-reminder',
            'bid_received_vendor' => 'emails.notifications.vendor-bid-received',
            'bid_received_buyer' => 'emails.notifications.buyer-bid-received',
            'awarded' => 'emails.notifications.vendor-awarded',
            'not_selected' => 'emails.notifications.vendor-not-selected',
            'expired' => 'emails.notifications.vendor-expired',
            default => null,
        };

        if ($view) {
            return $mail->view($view, [
                'invitation' => $this->invitation,
                'notifiable' => $notifiable,
                'url' => $url,
            ]);
        }

        // Fallback for unknown types
        return $mail
            ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
            ->line('There is an update on your GASQ opportunity.')
            ->action('View Opportunity', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'invitation_id' => $this->invitation->id,
            'opportunity_id' => $this->invitation->vendor_opportunity_id,
            'job_id' => $this->invitation->opportunity->job_posting_id,
            'job_title' => $this->invitation->opportunity->jobPosting->title,
            'status' => $this->invitation->status,
            'credits_to_unlock' => $this->invitation->credits_to_unlock,
        ];
    }

    private function invitationUrl(): string
    {
        return URL::temporarySignedRoute(
            'vendor-opportunities.show',
            now()->addDays(14),
            ['invitation' => $this->invitation]
        );
    }

    private function subject(string $locationOrTitle): string
    {
        return match ($this->type) {
            'new' => '🚨 GASQ ALERT: New Pre-Qualified Opportunity – ' . $locationOrTitle,
            'reminder' => '⏰ Reminder: Pre-Qualified Opportunity Still Open – ' . $locationOrTitle,
            'final_notice' => '🚨 FINAL NOTICE: Vendor Selection Closing Soon',
            'unlocked' => '🔓 Buyer Details Unlocked – Submit Your Bid',
            'accepted_bid_reminder' => '⏰ Pricing Submission Needed to Stay Eligible',
            'bid_received_vendor' => '✅ Your Bid Has Been Submitted',
            'bid_received_buyer' => '💰 GASQ – Vendor Bid Received',
            'awarded' => '🏆 Congratulations – You Were Selected',
            'not_selected' => 'GASQ Update – Opportunity Awarded to Another Vendor',
            'expired' => 'GASQ Update – Opportunity Window Closed',
            default => 'GASQ Opportunity Update',
        };
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function weeklyHours(array $questionnaire): string
    {
        $hoursPerDay = is_numeric(data_get($questionnaire, 'hours_per_day')) ? (float) data_get($questionnaire, 'hours_per_day') : 0.0;
        $daysPerWeek = is_numeric(data_get($questionnaire, 'days_per_week')) ? (float) data_get($questionnaire, 'days_per_week') : 0.0;

        if ($hoursPerDay <= 0 || $daysPerWeek <= 0) {
            return 'Not provided';
        }

        return (string) (int) round($hoursPerDay * $daysPerWeek);
    }

    private function money(mixed $value): string
    {
        return is_numeric($value) ? '$' . number_format((float) $value, 2) : 'Not provided';
    }
}
