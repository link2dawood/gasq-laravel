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
            'vendor_accepted' => 'Vendor accepted job offer: ' . $job->title,
            'vendor_declined' => 'Vendor declined job offer: ' . $job->title,
            'counter_offer' => 'Counter offer on your bid: ' . $job->title,
            default => 'Bid update: ' . $job->title,
        };
        $url = route('jobs.show', $job);
        $budgetText = $this->jobBudgetText($job);

        $mail = (new MailMessage)
            ->subject($subject)
            ->action('View', $url);

        $mail->line(match ($this->type) {
            'submitted' => $this->bid->user->name . ' submitted a bid of $' . number_format($this->bid->amount, 2) . '. Buyer budget: ' . $budgetText . '.',
            'updated' => $this->bid->user->name . ' updated their bid to $' . number_format($this->bid->amount, 2) . '. Buyer budget: ' . $budgetText . '.',
            'accepted' => 'Your bid of $' . number_format($this->bid->amount, 2) . ' was accepted.',
            'rejected' => 'Your bid was declined by the buyer.',
            'vendor_accepted' => $this->bid->user->name . ' accepted the job offer announcement. Buyer budget: ' . $budgetText . '.',
            'vendor_declined' => $this->bid->user->name . ' declined the job offer announcement.',
            'counter_offer' => 'The buyer sent a counter offer of $' . number_format($this->bid->counter_offer_amount, 2) . '.',
            default => 'There is an update on your bid.',
        });

        return $mail;
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
