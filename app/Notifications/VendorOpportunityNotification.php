<?php

namespace App\Notifications;

use App\Models\VendorOpportunityInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->invitation->opportunity->jobPosting;
        $buyer = $job->user;
        $questionnaire = is_array($job->questionnaire_data) ? $job->questionnaire_data : [];

        $url = $this->invitationUrl();
        $mail = (new MailMessage)->subject($this->subject($job->location ?? $job->title));

        return match ($this->type) {
            'new' => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
                ->line('A new pre-qualified security services opportunity is available through the GASQ Vendor Network.')
                ->line('Location: ' . ($job->location ?: 'Not provided'))
                ->line('Service Type: ' . ($job->category ?: 'Not provided'))
                ->line('Coverage: ' . $this->weeklyHours($questionnaire) . ' hours per week')
                ->line('Estimated Staff Required: ' . ($job->guards_per_shift ?: 'Not provided'))
                ->line('Start Timeline: ' . ($questionnaire['service_start_timeline'] ?? 'Not provided'))
                ->line('Estimated Annual Contract Value: ' . $this->money($this->invitation->opportunity->estimated_annual_contract_value))
                ->line('Qualification Status: Decision maker verified, budget confirmed, scope defined, buyer prepared to move forward.')
                ->action('Respond Now', $url),
            'reminder' => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
                ->line('This opportunity is still awaiting your response.')
                ->line('Location: ' . ($job->location ?: 'Not provided'))
                ->line('Contract Value: ' . $this->money($this->invitation->opportunity->estimated_annual_contract_value))
                ->line('Weekly Hours: ' . $this->weeklyHours($questionnaire))
                ->line('Lead Tier: ' . strtoupper($this->invitation->opportunity->lead_tier))
                ->action('Respond Now', $url),
            'final_notice' => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
                ->line('Final notice: vendor selection is closing soon for this security services opportunity.')
                ->line('If you intend to participate, please submit your response now.')
                ->action('Submit Bid', $url),
            'unlocked' => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
                ->line('Buyer details are now unlocked for your accepted opportunity.')
                ->line('Buyer: ' . ($buyer->name ?: 'Not provided'))
                ->line('Company: ' . ($buyer->company ?: 'Not provided'))
                ->line('Email: ' . ($buyer->email ?: 'Not provided'))
                ->line('Phone: ' . ($buyer->phone ?: 'Not provided'))
                ->action('Submit Bid', $url),
            'accepted_bid_reminder' => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
                ->line('You accepted this opportunity, but pricing submission is still needed to stay eligible.')
                ->line('Your 24-hour bid window is still open.')
                ->action('Submit Bid', $url),
            'bid_received_vendor' => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
                ->line('Your bid has been received.')
                ->line('Next steps: GASQ will review pricing for realism, then the buyer will review qualified submissions.')
                ->action('View Opportunity', $url),
            'bid_received_buyer' => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Buyer') . ',')
                ->line('A vendor submitted pricing for your opportunity.')
                ->line('Vendor: ' . ($this->invitation->vendor->name ?: 'Vendor'))
                ->line('Annual Price: ' . $this->money($this->invitation->bid?->annual_price))
                ->line('Realism: ' . ucfirst((string) ($this->invitation->bid?->realism_label ?? 'pending')))
                ->action('Review Job', route('jobs.show', $job)),
            'awarded' => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
                ->line('You were selected for this GASQ opportunity.')
                ->line('Please coordinate next steps with the buyer.')
                ->action('View Opportunity', $url),
            'not_selected' => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
                ->line('The buyer review process closed and another vendor was selected for this opportunity.')
                ->line('Thank you for participating in the GASQ Vendor Network.')
                ->action('View Opportunity', $url),
            default => $mail
                ->greeting('Hello ' . ($notifiable->name ?? 'Vendor') . ',')
                ->line('There is an update on your GASQ opportunity.')
                ->action('View Opportunity', $url),
        };
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
            'new' => 'New Pre-Qualified Security Opportunity – ' . $locationOrTitle,
            'reminder' => 'Reminder: Security Opportunity Still Open',
            'final_notice' => 'Final Notice: Vendor Selection Closing Soon',
            'unlocked' => 'Buyer Details Unlocked',
            'accepted_bid_reminder' => 'Pricing Submission Needed to Stay Eligible',
            'bid_received_vendor' => 'Your Bid Has Been Submitted',
            'bid_received_buyer' => 'Vendor Bid Received',
            'awarded' => 'Opportunity Awarded',
            'not_selected' => 'Opportunity Update',
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
