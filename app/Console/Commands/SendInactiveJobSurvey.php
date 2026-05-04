<?php

namespace App\Console\Commands;

use App\Models\JobPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendInactiveJobSurvey extends Command
{
    protected $signature = 'jobs:send-inactivity-survey {--days=14}';
    protected $description = 'Email buyers a "did you hire?" survey for jobs inactive for N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $jobs = JobPosting::query()
            ->whereNull('hired_at')
            ->whereNull('closed_at')
            ->whereNotIn('status', ['closed', 'awarded'])
            ->whereNull('inactivity_survey_sent_at')
            ->where(function ($q) use ($cutoff) {
                $q->where('last_activity_at', '<', $cutoff)
                    ->orWhere(function ($qq) use ($cutoff) {
                        $qq->whereNull('last_activity_at')->where('created_at', '<', $cutoff);
                    });
            })
            ->with('user')
            ->get();

        foreach ($jobs as $job) {
            $job->user?->notify(new InactiveJobSurveyNotification($job));
            $job->update(['inactivity_survey_sent_at' => now()]);
            $this->info("Sent survey for job {$job->id}");
        }

        $this->info('Total surveys sent: ' . $jobs->count());

        return self::SUCCESS;
    }
}

class InactiveJobSurveyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobPosting $job) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Did you hire someone for: {$this->job->title}?")
            ->line("We noticed your job \"{$this->job->title}\" hasn't had activity recently.")
            ->line("Tell us what happened — did you hire a vendor, or do you need help?")
            ->action('Update job', route('jobs.show', $this->job));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inactivity_survey',
            'job_id' => $this->job->id,
            'job_title' => $this->job->title,
        ];
    }
}
