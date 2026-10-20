<?php

namespace App\Console\Commands;

use App\Mail\AppraisalAppointmentMail;
use App\Models\AppraisalReview;
use App\Services\CalendlyAppraisalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TestAppraisalReviewEmail extends Command
{
    protected $signature = 'appraisals:test-email {review} {--send : Send the acceptance-test email to info@getasecurityquote.com}';

    protected $description = 'Preview or send a protected sample appraisal using a real verified review booking before activation';

    public function handle(CalendlyAppraisalService $calendly): int
    {
        if (! config('appraisal_reviews.calendly_token')) {
            $this->error('Calendly access is required to verify working appointment links before sending a test.');

            return self::FAILURE;
        }
        $review = AppraisalReview::findOrFail($this->argument('review'));
        if (strcasecmp($review->buyer_email, 'info@getasecurityquote.com') !== 0 || ! $review->ready_at) {
            $this->error('Use a dedicated, finalized sample review addressed to info@getasecurityquote.com.');

            return self::FAILURE;
        }
        $appointment = $review->appointments()->where('status', 'scheduled')->where('starts_at', '>', now())->first();
        if (! $appointment || ! $calendly->ingest($appointment->invitee_uri)) {
            $this->error('A current, verified Calendly appraisal appointment is required.');

            return self::FAILURE;
        }
        $appointment->refresh();
        if ($appointment->status !== 'scheduled' || $appointment->meeting_details === '') {
            $this->error('The booking must be active and have meeting details.');

            return self::FAILURE;
        }
        $mail = new AppraisalAppointmentMail($review->fresh(), $appointment, 'report', testDelivery: true);
        $mail->to('info@getasecurityquote.com');
        $mail->attachments(); // Validate the exact stored protected PDF before transport.
        Storage::disk('local')->put('appraisal-reviews/'.$review->id.'/test-email.html', $mail->render());
        if (! $this->option('send')) {
            $this->info('Private preview prepared. Use --send to send the acceptance test; automatic delivery remains unchanged.');

            return self::SUCCESS;
        }
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->error('Configure a delivery-capable mail transport before sending the acceptance test.');

            return self::FAILURE;
        }
        if (parse_url(config('app.url'), PHP_URL_SCHEME) !== 'https' || in_array(parse_url(config('app.url'), PHP_URL_HOST), ['localhost', '127.0.0.1'], true)) {
            $this->error('A public HTTPS GASQ URL is required for working attendance and appointment links.');

            return self::FAILURE;
        }
        Mail::to('info@getasecurityquote.com')->send($mail);
        $this->info('Test submitted to the mail service for info@getasecurityquote.com. Verify inbox receipt, attachment, and appointment actions before activation.');

        return self::SUCCESS;
    }
}
