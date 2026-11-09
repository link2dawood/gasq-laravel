<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProtectedAppraisalEmailTest extends TestCase
{
    public function test_protected_cover_masks_findings_and_links_to_scheduling_without_revealing_payback(): void
    {
        $html = view('emails.cost-to-protect-protected', [
            'clientName' => 'Avery Buyer', 'reportNumber' => 'GASQ-TEST-123',
            'datePrepared' => 'November 7, 2026', 'inHouseCost' => 782068,
            'outsourcingCost' => 547448, 'capitalRecovery' => 234620,
            'paybackPeriod' => '8.4 months', 'bookingUrl' => 'https://example.test/schedule?a=1&b=2',
        ])->render();
        foreach (['Dear Avery Buyer', 'GASQ-TEST-123', '$XXX,068', '$XXX,448', '$XXX,620', 'https://example.test/schedule?a=1&amp;b=2', 'Schedule Your Estimate &amp; Scope Review'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        foreach (['782,068', '547,448', '234,620', '8.4 months'] as $secret) {
            // The approved comparison never prints payback, and masks every amount.
            $this->assertStringNotContainsString($secret, $html);
        }
    }

    public function test_unconfigured_scheduling_offers_reply_and_small_amounts_are_masked(): void
    {
        $html = view('emails.cost-to-protect-protected', [
            'clientName' => null, 'datePrepared' => 'November 7, 2026',
            'inHouseCost' => 123, 'bookingUrl' => null,
        ])->render();
        $this->assertStringContainsString('Please reply to this email to arrange your appointment.', $html);
        $this->assertStringContainsString('$XXX', $html);
        $this->assertStringNotContainsString('href=""', $html);
    }
}
