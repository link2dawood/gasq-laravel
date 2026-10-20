<?php

namespace App\Console\Commands;

use App\Jobs\SendAppraisalDelivery;
use App\Models\AppraisalAppointment;
use App\Models\AppraisalDelivery;
use App\Models\AppraisalReview;
use App\Services\AppraisalReviewService;
use App\Services\CalendlyAppraisalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessAppraisalReviews extends Command
{
    protected $signature = 'appraisals:process {--sync : Refresh active appointments from Calendly}';

    protected $description = 'Reconcile appraisal deliveries and schedule 24-hour and 1-hour reminders; never release passwords';

    public function handle(AppraisalReviewService $service, CalendlyAppraisalService $calendly): int
    {
        if ($this->option('sync') && config('appraisal_reviews.calendly_token')) {
            AppraisalAppointment::where('status', 'scheduled')->where('ends_at', '>', now())->limit(100)->each(function ($appointment) use ($calendly) {
                try {
                    $calendly->ingest($appointment->invitee_uri);
                } catch (\Throwable) {
                    $this->warn('Could not refresh appointment '.$appointment->id.'.');
                }
            });
        }
        AppraisalReview::whereNotNull('ready_at')->whereNull('delivered_at')->each(function ($review) use ($service) {
            DB::transaction(function () use ($review, $service) {
                $service->queueReady(AppraisalReview::whereKey($review->id)->lockForUpdate()->firstOrFail());
            });
        });
        AppraisalAppointment::where('status', 'scheduled')->where('starts_at', '>', now())->each(function ($appointment) use ($service) {
            foreach ([24, 1] as $hours) {
                $due = $appointment->starts_at->subHours($hours);
                if ($due->lte(now()) && $due->gte(now()->subMinutes(5)) && $appointment->source_created_at->lte($due)) {
                    $service->queueMessage($appointment, 'reminder_'.$hours.'h');
                }
            }
        });
        if (config('appraisal_reviews.enabled')) {
            AppraisalDelivery::where('status', 'pending')->each(fn ($delivery) => SendAppraisalDelivery::dispatch($delivery->id));
        }
        $uncertain = AppraisalDelivery::where('status', 'uncertain')->orWhere(fn ($query) => $query->where('status', 'sending')->where('claimed_at', '<', now()->subMinutes(5)))->count();
        $this->info('Appraisal processing finished. Messages requiring manual delivery review: '.$uncertain.'.');

        return self::SUCCESS;
    }
}
