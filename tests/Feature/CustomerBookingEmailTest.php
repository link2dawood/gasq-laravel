<?php

namespace Tests\Feature;

use App\Services\CustomerBookingLink;
use Tests\TestCase;

class CustomerBookingEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['appraisal_reviews.provider' => 'calendly', 'appraisal_reviews.booking_url' => null, 'appraisal_reviews.bookafy.booking_url' => 'https://getasecurityquote.bookafy.com/schedule/?type=iframe&selected_staff=grayling']);
    }

    public function test_bookafy_is_used_when_selected_calendly_link_is_missing(): void
    {
        $link = app(CustomerBookingLink::class)->configured();
        $this->assertSame('bookafy', $link['provider']);
        $this->assertSame(config('appraisal_reviews.bookafy.booking_url'), $link['url']);
        $html = view('emails.cost-to-protect-protected', ['clientName' => 'Avery', 'datePrepared' => 'November 9, 2026', 'bookingUrl' => app(CustomerBookingLink::class)->url()])->render();
        $this->assertStringContainsString('href="'.e($link['url']).'"', $html);
        $this->assertStringContainsString('Choose an available date and time', $html);
        $this->assertStringContainsString('copy this booking link', $html);
        $this->assertStringNotContainsString('Please reply to this email to arrange your appointment.', $html);
        $this->assertSame('calendly', config('appraisal_reviews.provider'));
    }

    public function test_configured_selected_provider_keeps_priority(): void
    {
        config(['appraisal_reviews.booking_url' => 'https://calendly.com/gasq/review']);
        $this->assertSame('calendly', app(CustomerBookingLink::class)->configured()['provider']);
        $this->assertSame('https://calendly.com/gasq/review', app(CustomerBookingLink::class)->url());
    }

    public function test_untrusted_insecure_or_credential_urls_are_not_used(): void
    {
        foreach (['http://getasecurityquote.bookafy.com/schedule', 'https://bookafy.com.evil.test/schedule', 'https://user:password@bookafy.com/schedule', 'https://:password@bookafy.com/schedule', 'https://bookafy.com/schedule#fragment'] as $url) {
            config(['appraisal_reviews.bookafy.booking_url' => $url]);
            $this->assertNull(app(CustomerBookingLink::class)->url());
        }
    }
}
