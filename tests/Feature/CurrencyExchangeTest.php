<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\CalculatorStateStore;
use App\Services\ExchangeRateService;
use App\Services\V24\MainMenu\EconomicJustificationEngine;
use App\Services\VendorReportSigningService;
use App\Services\VendorWeightedRateEngine;
use App\Services\VendorWeightedRateReport;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyExchangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow('2026-10-22 10:00:00 UTC');
        config(['currency.refresh_on_request' => false]);
    }

    protected function tearDown(): void
    {
        Date::setTestNow();
        parent::tearDown();
    }

    private function xml(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/currency/ecb-2026-10-21.xml');
    }

    private function refresh(?string $xml = null): array
    {
        Http::swap(new Factory);
        Http::fake([ExchangeRateService::FEED => Http::response($xml ?? $this->xml(), 200, ['Content-Type' => 'text/xml'])]);

        return app(ExchangeRateService::class)->refresh();
    }

    public function test_verified_ecb_cross_rates_replace_undated_manual_defaults_and_keep_usd_fixed(): void
    {
        Setting::set('exchange_rate_cad', '999');
        Setting::set('exchange_rate_usd', '999');
        $sheet = $this->refresh();
        $this->assertSame('2026-10-21', $sheet['asOf']);
        $this->assertSame('USD', $sheet['base']);
        $this->assertCount(11, $sheet['rates']);
        $this->assertSame(1.0, Currency::rate('USD'));
        $this->assertEqualsWithDelta(1.5779 / 1.1234, Currency::rate('CAD'), 1e-12);
        $this->assertEqualsWithDelta(1 / 1.1234, Currency::rate('EUR'), 1e-12);
        $this->assertEqualsWithDelta(177.36 / 1.1234, Currency::rate('JPY'), 1e-12);
        $this->assertEqualsWithDelta(106.1265 / 1.1234, Currency::rate('INR'), 1e-12);
    }

    public function test_invalid_incomplete_future_and_older_sheets_never_replace_verified_rates(): void
    {
        $before = $this->refresh();
        foreach (['<html>Unavailable</html>', str_replace('2026-10-21', '2026-10-23', $this->xml()),
            str_replace('2026-10-21', '2026-10-20', $this->xml()), str_replace("rate='1.1234'", "rate='0'", $this->xml()),
            str_replace("<Cube currency='CAD' rate='1.5779'/>", '', $this->xml()),
            str_replace("rate='1.5779'", "rate='NaN'", $this->xml()), '<!DOCTYPE data SYSTEM "file:///etc/passwd">'.$this->xml(),
        ] as $xml) {
            try {
                $this->refresh($xml);
                $this->fail('An invalid or stale reference sheet was accepted.');
            } catch (\RuntimeException) {
                $this->assertSame($before, app(ExchangeRateService::class)->current());
            }
        }
        Http::swap(new Factory);
        Http::fake([ExchangeRateService::FEED => Http::response('', 503)]);
        config(['currency.refresh_on_request' => true]);
        Cache::forget('exchange_rates.refresh_attempt');
        $this->assertSame($before, (new ExchangeRateService)->current());
    }

    public function test_render_refresh_is_throttled_across_requests_and_exposes_publication_date(): void
    {
        Http::fake([ExchangeRateService::FEED => Http::response($this->xml())]);
        config(['currency.refresh_on_request' => true]);
        (new ExchangeRateService)->current();
        (new ExchangeRateService)->current();
        Http::assertSentCount(1);
        $this->assertSame('2026-10-21', Currency::jsConfig()['asOf']);
    }

    public function test_currency_switch_preserves_canonical_roster_inputs_and_staffing_totals(): void
    {
        $vendor = User::factory()->create(['user_type' => 'vendor', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        $scenario = ['meta' => ['employeeTrueHourlyCost' => 133, 'vendorWeightedRates' => ['draft' => VendorWeightedRateEngine::presets()['sample']]]];
        app(CalculatorStateStore::class)->store($vendor, 'main-menu', $scenario);
        foreach (['CAD', 'JPY', 'EUR', 'USD'] as $code) {
            $this->actingAs($vendor)->post(route('preferences.currency'), ['currency' => $code])->assertRedirect();
            $this->assertSame($scenario, $vendor->calculatorStates()->sole()->scenario);
            $report = app(VendorWeightedRateReport::class)->prepare(['scenario' => $scenario, 'user' => $vendor->fresh()]);
            $this->assertSame($code, $report['reportCurrency']['code']);
            $this->assertSame(301600.0, $report['weighted']['totals']['annualRoleHours']);
            $this->assertSame(5800.0, $report['weighted']['totals']['weeklyHours']);
            $this->assertEquals(145, $report['weighted']['totals']['headcount']);
        }
    }

    public function test_weighted_pdf_converts_money_and_reconciles_the_local_rounded_rate(): void
    {
        $this->refresh();
        $owner = new User(['currency' => 'CAD']);
        $scenario = ['meta' => ['vendorWeightedRates' => ['draft' => VendorWeightedRateEngine::presets()['sample']]]];
        $data = app(VendorWeightedRateReport::class)->prepare(['scenario' => $scenario, 'user' => $owner]);
        $t = $data['weighted']['totals'];
        $rate = 1.5779 / 1.1234;
        $html = view('pdf.vendor-weighted-rates', $data)->render();
        $this->assertStringContainsString('CA$'.number_format($t['weightedBillRate'] * $rate, 2), $html);
        $this->assertStringContainsString('CA$'.number_format($t['annualBillCost'] * $rate, 2), $html);
        $this->assertStringContainsString('301,600.00', $html);
        $roundedTotal = round($t['weightedBillRate'] * $rate, 2) * $t['annualRoleHours'];
        $this->assertEqualsWithDelta($roundedTotal, $t['roundedRateAnnualCost'] * $rate, 1e-8);
        $this->assertEqualsWithDelta($roundedTotal - $t['annualBillCost'] * $rate, $t['roundingDifference'] * $rate, 1e-8);
    }

    public function test_fx_changes_invalidate_a_signature_for_converted_figures(): void
    {
        $this->refresh();
        $vendor = User::factory()->create(['currency' => 'CAD']);
        $scenario = ['meta' => ['vendorWeightedRates' => ['draft' => VendorWeightedRateEngine::presets()['sample']]]];
        $service = app(VendorReportSigningService::class);
        $before = $service->status($scenario, $vendor)['reportHash'];
        $service->sign($scenario, $vendor, ['reportHash' => $before, 'signerName' => 'Test Vendor', 'method' => 'typed']);
        $this->assertSame('signed', $service->status($scenario, $vendor)['signatureStatus']);
        $this->refresh(str_replace("rate='1.5779'", "rate='1.6000'", $this->xml()));
        $this->assertSame('changed', $service->status($scenario, $vendor)['signatureStatus']);
        $this->assertNull($service->active($scenario, $vendor));
    }

    public function test_roi_preserves_precision_before_foreign_currency_rounding(): void
    {
        $rate = 1.5779 / 1.1234;
        $roi = app(EconomicJustificationEngine::class)->compute(['meta' => ['employeeTrueHourlyCost' => 130.25 / $rate]]);
        $this->assertEqualsWithDelta(130.25, $roi['inHouseHourly'] * $rate, 1e-10);
        $this->assertEqualsWithDelta(91.175, $roi['vendorHourly'] * $rate, 1e-10);
        $this->actingAs(User::factory()->create(['currency' => 'CAD']));
        $this->assertSame('CA$91.18', Currency::format($roi['vendorHourly']));
    }

    public function test_only_admin_can_refresh_and_generic_settings_cannot_forge_reference_rates(): void
    {
        $vendor = User::factory()->create(['user_type' => 'vendor', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        $this->actingAs($vendor)->post(route('admin.settings.exchange-rates'))->assertForbidden();
        $vendor->update(['user_type' => 'admin']);
        $this->actingAs($vendor->fresh())->postJson(route('admin.settings.update'), ['settings' => [ExchangeRateService::KEY => ['value' => '{}']]])->assertUnprocessable();
        Http::fake([ExchangeRateService::FEED => Http::response($this->xml())]);
        $this->post(route('admin.settings.exchange-rates'))->assertRedirect()->assertSessionHas('success');
        $this->get(route('admin.settings'))->assertOk()->assertSee('ECB daily reference')->assertSee('2026-10-21');
    }
}
