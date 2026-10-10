<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CalculatorStateStore;
use App\Services\ReportService;
use App\Services\VendorWeightedRateEngine;
use App\Services\VendorWeightedRateReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VendorWeightedRateReportTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['scenario' => ['meta' => ['companyName' => 'Northgate Site', 'vendorWeightedRates' => [
            'draft' => VendorWeightedRateEngine::presets()['sample'],
            'draftResult' => ['totals' => ['annualBillCost' => 1]],
        ]]], 'result' => []];
    }

    private function vendor(): User
    {
        return User::factory()->create(['user_type' => 'vendor', 'phone_verified' => true, 'nda_accepted_at' => now()]);
    }

    public function test_dashboard_uses_saved_inputs_full_precision_and_entered_headcount(): void
    {
        $data = app(VendorWeightedRateReport::class)->prepare($this->payload());
        $t = $data['weighted']['totals'];
        $this->assertEqualsWithDelta(12048524.8, $t['annualBillCost'], 1e-8);
        $this->assertEqualsWithDelta(231702.4, $t['weeklyBillCost'], 1e-8);
        $this->assertEqualsWithDelta(1004043.7333333, $t['monthlyBillCost'], 1e-6);
        $this->assertEqualsWithDelta(395.2, $t['roundingDifference'], 1e-8);
        $this->assertEquals(145, $t['headcount']);
        $html = view('pdf.vendor-weighted-rates', $data)->render();
        foreach (['VENDOR WEIGHTED RATE DASHBOARD', '$39.95', '$15.54', '$2,252.66', '$12,048,524.80', 'Northgate Site', 'Implied pay wage — model estimate'] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        $this->assertStringNotContainsString('208', $html);
    }

    public function test_report_period_costs_honor_role_week_overrides_and_actual_wages(): void
    {
        $payload = $this->payload();
        $payload['scenario']['meta']['vendorWeightedRates']['draft']['roles'] = [
            ['classification' => 'unarmed', 'headcount' => 1, 'actualWage' => 10, 'billRate' => 20, 'weeklyHoursPerEmployee' => 10, 'weeksPerYear' => 26],
            ['classification' => 'armed', 'headcount' => 1, 'actualWage' => 30, 'billRate' => 60],
        ];
        $data = app(VendorWeightedRateReport::class)->prepare($payload);
        $this->assertEquals(2600, $data['weighted']['totals']['weeklyBillCost']);
        $this->assertEquals(130000 / 12, $data['weighted']['totals']['monthlyBillCost']);
        $this->assertSame('actual', $data['weighted']['roles'][0]['paySource']);
    }

    public function test_protected_report_requires_a_password_even_in_the_service(): void
    {
        $this->expectException(ValidationException::class);
        app(ReportService::class)->calculatorPdf('vendor-weighted-rates', $this->payload());
    }

    public function test_download_requires_vendor_and_body_password_and_uses_own_saved_state(): void
    {
        $url = route('reports.download');
        $vendor = $this->vendor();
        app(CalculatorStateStore::class)->store($vendor, 'main-menu', $this->payload()['scenario']);
        $this->actingAs($vendor)->getJson($url.'?type=vendor-weighted-rates')->assertStatus(405);
        foreach ([[], ['pdf_password' => 'short'], ['pdf_password' => '雪雪雪雪雪雪雪雪']] as $input) {
            $this->postJson($url, ['type' => 'vendor-weighted-rates'] + $input)->assertUnprocessable()->assertJsonValidationErrors('pdf_password');
        }
        $this->postJson($url.'?pdf_password=PasswordInUrl', ['type' => 'vendor-weighted-rates'])->assertUnprocessable();
        $response = $this->postJson($url, ['type' => 'vendor-weighted-rates', 'pdf_password' => 'PrivateReport2026']);
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('/Encrypt', $response->getContent());
        $this->assertStringContainsString('Vendor-Weighted-Rate-Dashboard', $response->headers->get('Content-Disposition'));
        $state = $vendor->calculatorStates()->firstOrFail()->scenario;
        $this->assertStringNotContainsString('PrivateReport2026', json_encode($state));
        $other = $this->vendor();
        $this->withSession(['report_payload' => ['type' => 'main-menu'] + $this->payload()]);
        $this->actingAs($other)->postJson($url, ['type' => 'vendor-weighted-rates', 'pdf_password' => 'PrivateReport2026'])->assertRedirect();
        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->postJson($url, ['type' => 'vendor-weighted-rates', 'pdf_password' => 'PrivateReport2026'])->assertForbidden();
    }
}
