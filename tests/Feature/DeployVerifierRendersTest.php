<?php

namespace Tests\Feature;

use App\Services\ReportService;
use App\Services\VendorWeightedRateEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two protected renders scripts/verify_report_deployment.php performs.
 * That script runs on every deploy under `set -e`, so a render that throws
 * aborts the deploy before it writes public/deployment.json.
 */
class DeployVerifierRendersTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_vendor_weighted_rate_pdf_still_renders(): void
    {
        $payload = ['scenario' => ['meta' => ['vendorWeightedRates' => ['draft' => VendorWeightedRateEngine::presets()['sample']]]]];
        $pdf = app(ReportService::class)->calculatorPdf('vendor-weighted-rates', $payload, bin2hex(random_bytes(12)));
        $bytes = $pdf->output();

        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertStringContainsString('/Encrypt', $bytes);
        $this->assertGreaterThanOrEqual(2, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_protected_buyer_appraisal_pdf_still_renders(): void
    {
        $pdf = app(ReportService::class)->calculatorPdf('buyer-appraisal', ['scenario' => [], 'reportNumber' => 'DEPLOYMENT-CHECK'], bin2hex(random_bytes(12)));
        $bytes = $pdf->output();

        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertStringContainsString('/Encrypt', $bytes);
    }
}
