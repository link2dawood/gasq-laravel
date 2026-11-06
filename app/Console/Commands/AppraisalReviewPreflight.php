<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AppraisalReviewPreflight extends Command
{
    protected $signature = 'appraisals:preflight';

    protected $description = 'Check appraisal scheduling and mail configuration without sending messages or revealing credentials';

    public function handle(): int
    {
        $this->line('Provider: '.config('appraisal_reviews.provider'));

        return config('appraisal_reviews.provider') === 'bookafy' ? $this->bookafy() : $this->calendly();
    }

    private function bookafy(): int
    {
        $missing = false;
        foreach (['api_key', 'webhook_token', 'staff_email', 'service_id', 'booking_url', 'cancel_url', 'reschedule_url'] as $key) {
            $present = (bool) config('appraisal_reviews.bookafy.'.$key);
            $this->line($key.': '.($present ? 'configured' : 'missing'));
            $missing = $missing || ! $present;
        }
        $this->line('reply_to: '.(config('appraisal_reviews.reply_to') ? 'configured' : 'missing'));
        $this->line('Automated delivery: '.(config('appraisal_reviews.enabled') ? 'enabled' : 'inactive'));
        $this->line('Mail transport: '.config('mail.default'));
        if ($missing || ! filter_var(config('appraisal_reviews.reply_to'), FILTER_VALIDATE_EMAIL)) {
            $this->warn('Complete the Bookafy configuration before live acceptance testing.');

            return self::FAILURE;
        }
        if (strlen((string) config('appraisal_reviews.bookafy.webhook_token')) < 32) {
            $this->error('The Bookafy callback secret must be at least 32 characters; Bookafy signs nothing else.');

            return self::FAILURE;
        }
        $client = Http::withHeaders(['api-key' => config('appraisal_reviews.bookafy.api_key')])->acceptJson()
            ->timeout(15)->withOptions(['allow_redirects' => false]);
        $account = $client->get('https://app.bookafy.com/api/v3/me');
        if (! $account->successful()) {
            $this->error('Bookafy account access failed (HTTP '.$account->status().'). A paid plan is required for API access.');

            return self::FAILURE;
        }
        $this->line('Account: '.$account->json('email'));
        $services = $client->get('https://app.bookafy.com/api/v3/services');
        $match = collect($services->json('response.services') ?: [])
            ->firstWhere('id', (int) config('appraisal_reviews.bookafy.service_id'));
        $this->line('Appraisal service: '.($match ? $match['service_name'].' ('.$match['duration'].' minutes, Zoom '.($match['zoom'] ? 'on' : 'off').')' : 'NOT FOUND'));
        $subscriptions = $client->get('https://app.bookafy.com/api/v3/api_subscriptions');
        $events = collect($subscriptions->json('response.subscriptions') ?: [])
            ->filter(fn ($subscription) => str_contains((string) ($subscription['callback_url'] ?? ''), '/webhooks/bookafy/appraisals/'))
            ->pluck('event_type')->sort()->values();
        $this->line('Callbacks registered: '.($events->isEmpty() ? 'none' : $events->implode(', ')));
        $expected = ['appointment_created', 'appointment_destroyed', 'appointment_updated'];
        if ($events->values()->all() !== $expected) {
            $this->warn('Register appointment_created, appointment_updated and appointment_destroyed against '.route('appraisals.bookafy-webhook', ['token' => 'THE-SECRET']).'.');
        }
        $appointments = $client->get('https://app.bookafy.com/api/v3/appointments', [
            'email' => config('appraisal_reviews.bookafy.staff_email'), 'per_page' => 1,
        ]);
        $this->line('Appointment listing: HTTP '.$appointments->status());
        $this->line('Verify the Zoom location, the native booking confirmation, and that Bookafy\'s own reminders are off for this service.');

        return $match && $appointments->successful() && $events->values()->all() === $expected ? self::SUCCESS : self::FAILURE;
    }

    private function calendly(): int
    {
        $missing = false;
        foreach (['calendly_token', 'webhook_signing_key', 'event_type_uri', 'booking_url', 'reply_to'] as $key) {
            $present = (bool) config('appraisal_reviews.'.$key);
            $this->line($key.': '.($present ? 'configured' : 'missing'));
            $missing = $missing || ! $present;
        }
        $this->line('Automated delivery: '.(config('appraisal_reviews.enabled') ? 'enabled' : 'inactive'));
        $this->line('Mail transport: '.config('mail.default'));
        if ($missing) {
            $this->warn('Connect the scheduling account and configure the appraisal event before live acceptance testing.');

            return self::FAILURE;
        }
        if (! preg_match('~^https://api\.calendly\.com/event_types/[a-zA-Z0-9-]+$~D', config('appraisal_reviews.event_type_uri'))
            || ! preg_match('~^https://calendly\.com/[^?]+$~D', config('appraisal_reviews.booking_url'))
            || ! filter_var(config('appraisal_reviews.reply_to'), FILTER_VALIDATE_EMAIL)) {
            $this->error('Verify the event type URI, Calendly booking URL, and reply address.');

            return self::FAILURE;
        }
        $client = Http::withToken(config('appraisal_reviews.calendly_token'))->acceptJson()->timeout(15)->withOptions(['allow_redirects' => false]);
        $account = $client->get('https://api.calendly.com/users/me');
        if (! $account->successful()) {
            $this->error('Calendly account access failed (HTTP '.$account->status().').');

            return self::FAILURE;
        }
        $event = $client->get(config('appraisal_reviews.event_type_uri'));
        $webhooks = $client->get('https://api.calendly.com/webhook_subscriptions', ['organization' => $account->json('resource.current_organization'), 'scope' => 'organization']);
        $this->line('Event access: HTTP '.$event->status());
        $this->line('Webhook subscription access: HTTP '.$webhooks->status());
        $this->line('Verify Zoom location, native booking confirmation, plan, and mailbox monitoring in the connected accounts.');

        return $event->successful() && $webhooks->successful() ? self::SUCCESS : self::FAILURE;
    }
}
