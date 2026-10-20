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
