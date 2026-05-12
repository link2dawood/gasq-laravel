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
            'submitted' => '📩 New Bid on: ' . $job->title,
            'updated' => '📝 Bid Updated on: ' . $job->title,
            'accepted' => '🤝 Your Bid Was Accepted: ' . $job->title,
            'rejected' => 'GASQ Update – Bid Decision: ' . $job->title,
            'vendor_accepted' => '✅ Vendor Accepted Your Offer: ' . $job->title,
            'vendor_declined' => 'GASQ Update – Vendor Response: ' . $job->title,
            'counter_offer' => '💬 Counter Offer on Your Bid: ' . $job->title,
            'counter_offer_accepted' => '✅ Counter Offer Accepted: ' . $job->title,
            default => 'GASQ Update – Bid: ' . $job->title,
        };

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notifications.bid-event', [
                'bid' => $this->bid,
                'type' => $this->type,
                'notifiable' => $notifiable,
            ]);
    }

    private function jobBudgetText(\App\Models\JobPosting $job): string
    {
        $q = is_array($job->questionnaire_data) ? $job->questionnaire_data : [];
        $range = data_get($q, 'budget_amount_range');
        if (is_string($range) && trim($range) !== '') {
            return trim($range);
        }
        foreach (['hourly_budget' => '/hr', 'monthly_budget' => '/month', 'annual_budget' => '/year'] as $k => $s) {
            $v = data_get($q, $k);
            if (is_numeric($v) && (float) $v > 0) {
                return '$' . number_format((float) $v, 2) . ' ' . $s;
            }
        }
        if ($job->budget_min || $job->budget_max) {
            $min = (float) ($job->budget_min ?? 0);
            $max = (float) ($job->budget_max ?? 0);
            if ($min > 0 && $max > 0 && $min !== $max) {
                return '$' . number_format($min, 2) . ' – $' . number_format($max, 2);
            }
            return '$' . number_format($max > 0 ? $max : $min, 2);
        }
        return 'Not provided';
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
            'budget_text' => $this->jobBudgetText($job),
            'vendor_name' => $this->bid->user->name,
            'vendor_response_status' => $this->bid->vendor_response_status,
        ];
    }
}
