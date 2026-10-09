<?php

namespace Tests\Feature;

use App\Models\CalculatorState;
use App\Models\User;
use App\Services\CalculatorStateStore;
use App\Services\CalculatorViewStateResolver;
use App\Services\MasterInputsService;
use App\Services\ReportService;
use App\Services\V24\MainMenu\MainMenuComputeService;
use App\Services\VendorWeightedRateEngine;
use App\Services\WalletService;
use Barryvdh\DomPDF\PDF;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorWeightedRateModuleTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(): User
    {
        $vendor = User::factory()->create(['user_type' => 'vendor', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        app(WalletService::class)->addTokens($vendor, 500, 'test', 'test credits');
        app(MasterInputsService::class)->getOrCreate($vendor)->update(['is_complete' => true]);

        return $vendor;
    }

    private function scenario(): array
    {
        return [
            'meta' => ['basePayRate' => 24.75, 'employeeTrueHourlyCost' => 133, 'weeklyHoursPerformed' => 168, 'weeksInYear' => 52, 'vendorTrueHourlyCost' => 75],
            'scope' => ['hoursOfCoveragePerDay' => 24, 'daysOfCoveragePerWeek' => 7, 'weeksOfCoverage' => 52, 'staffPerShift' => 1],
            'posts' => [['weeklyHours' => 168, 'qtyRequired' => 1]],
        ];
    }

    public function test_sample_saves_reopens_and_applies_without_overwriting_baseline_or_service_hours(): void
    {
        $vendor = $this->vendor();
        $scenario = $this->scenario();
        app(CalculatorStateStore::class)->store($vendor, 'main-menu', $scenario, app(MainMenuComputeService::class)->compute($scenario));
        $sample = VendorWeightedRateEngine::presets()['sample'];
        $beforeBalance = app(WalletService::class)->getBalance($vendor);
        $preview = $this->actingAs($vendor)->postJson(route('backend.vendor-weighted-rates.compute'), ['inputs' => $sample]);
        $preview->assertOk()->assertJsonPath('result.isValid', true)->assertJsonPath('applied', false);
        $this->assertEqualsWithDelta(12048524.8, $preview->json('result.totals.annualBillCost'), 1e-8);
        $this->assertEquals(8736, $preview->json('preview.annualBillableServiceHours'));
        $this->assertEquals(301600, $preview->json('preview.annualPayrollHours'));
        $this->assertNull($preview->json('main.tabs.vendorComparison'));

        $loaded = app(CalculatorViewStateResolver::class)->resolve($vendor, 'main-menu-calculator.index');
        $this->assertSame($sample, data_get($loaded['scenario'], 'meta.vendorWeightedRates.draft'));
        $this->assertEqualsWithDelta(12048524.8 / 301600, data_get($loaded['scenario'], 'meta.vendorWeightedRates.draftResult.totals.weightedBillRate'), 1e-12);
        $this->get(route('main-menu-calculator.index'))->assertOk()->assertSee('Vendor Weighted Rates')->assertSee('id="vendor-weighted-rates"', false);

        $postedScenario = $scenario;
        $postedScenario['meta']['basePayRate'] = 1;
        $postedScenario['meta']['vendorWeightedRates'] = ['applied' => ['result' => ['totals' => ['weightedBillRate' => 1]]]];
        $response = $this->postJson(route('backend.vendor-weighted-rates.apply'), ['inputs' => $sample, 'scenario' => $postedScenario]);
        $response->assertOk()->assertJsonPath('applied', true);
        $state = CalculatorState::where('user_id', $vendor->id)->where('calculator_type', 'main-menu')->firstOrFail();
        $this->assertSame(24.75, $state->scenario['meta']['basePayRate']);
        $this->assertSame(133, $state->scenario['meta']['employeeTrueHourlyCost']);
        $this->assertSame($scenario['scope'], $state->scenario['scope']);
        $this->assertSame($scenario['posts'], $state->scenario['posts']);
        $this->assertEqualsWithDelta(12048524.8 / 301600 * 8736, data_get($state->result, 'tabs.vendorComparison.projectAnnualEstimate'), 1e-8);
        $this->assertEqualsWithDelta(12048524.8 / 301600, data_get($state->scenario, 'meta.vendorWeightedBillRate'), 1e-12);
        $this->assertSame($sample, data_get($state->scenario, 'meta.vendorWeightedRates.applied.inputs'));
        $this->assertSame('Current Bill Rate Analysis', data_get($state->result, 'tabs.vendorComparison.mode'));
        $this->assertSame($beforeBalance, app(WalletService::class)->getBalance($vendor));

        // Saving a different draft does not silently replace the applied selection.
        $changed = $sample;
        $changed['roles'][0]['billRate'] = 50;
        $this->postJson(route('backend.vendor-weighted-rates.compute'), ['inputs' => $changed])->assertOk();
        $reopened = app(CalculatorViewStateResolver::class)->resolve($vendor, 'main-menu-calculator.index');
        $this->assertSame($changed, data_get($reopened['scenario'], 'meta.vendorWeightedRates.draft'));
        $this->assertSame($sample, data_get($reopened['scenario'], 'meta.vendorWeightedRates.applied.inputs'));

        // Normal main-calculator edits retain the applied rate and project service basis.
        $this->postJson(route('backend.main-menu.compute'), ['version' => 'v24', 'scenario' => $scenario])
            ->assertOk()->assertJsonPath('tabs.vendorComparison.annualBillableServiceHours', 8736);
    }

    public function test_actual_override_and_custom_hour_rows_survive_save_and_reload(): void
    {
        $vendor = $this->vendor();
        $sample = VendorWeightedRateEngine::presets()['sample'];
        $sample['roles'][0]['actualWage'] = 20.125;
        $sample['roles'][0]['weeklyHoursPerEmployee'] = 24;
        $sample['roles'][0]['weeksPerYear'] = 48;
        $sample['roles'][] = ['classification' => 'custom', 'label' => 'Special event officer', 'headcount' => 2, 'actualWage' => 22.321, 'billRate' => 50, 'weeklyHoursPerEmployee' => 12, 'weeksPerYear' => 26];
        $this->actingAs($vendor)->postJson(route('backend.vendor-weighted-rates.compute'), ['inputs' => $sample])->assertOk();
        $loaded = app(CalculatorViewStateResolver::class)->resolve($vendor, 'main-menu-calculator.index');
        $this->assertSame($sample, data_get($loaded['scenario'], 'meta.vendorWeightedRates.draft'));
        $result = data_get($loaded['scenario'], 'meta.vendorWeightedRates.draftResult');
        $this->assertSame(20.125, $result['roles'][0]['payRate']);
        $this->assertSame('actual', $result['roles'][0]['paySource']);
        $this->assertEquals(24192, $result['roles'][0]['annualRoleHours']);
        $this->assertSame('Special event officer', $result['roles'][8]['label']);
    }

    public function test_buyers_and_guests_cannot_use_vendor_module_or_read_another_vendor_state(): void
    {
        $buyer = User::factory()->create(['user_type' => 'buyer', 'phone_verified' => true, 'nda_accepted_at' => now()]);
        foreach (['compute', 'apply'] as $action) {
            $this->postJson(route('backend.vendor-weighted-rates.'.$action), ['inputs' => VendorWeightedRateEngine::presets()['sample']])->assertUnauthorized();
            $this->actingAs($buyer)->postJson(route('backend.vendor-weighted-rates.'.$action), ['inputs' => VendorWeightedRateEngine::presets()['sample']])->assertForbidden();
            auth()->forgetGuards();
        }
        $one = $this->vendor();
        $two = $this->vendor();
        $this->actingAs($one)->postJson(route('backend.vendor-weighted-rates.apply'), ['inputs' => VendorWeightedRateEngine::presets()['sample']])->assertOk();
        $this->assertNull(app(CalculatorViewStateResolver::class)->resolve($two, 'main-menu-calculator.index')['state']);
        $this->actingAs($buyer)->postJson(route('backend.main-menu.compute'), ['version' => 'v24', 'scenario' => ['meta' => ['vendorWeightedRates' => ['draft' => VendorWeightedRateEngine::presets()['sample']]]]])->assertForbidden();
    }

    public function test_invalid_inputs_cannot_replace_the_saved_estimate(): void
    {
        $vendor = $this->vendor();
        $sample = VendorWeightedRateEngine::presets()['sample'];
        $this->actingAs($vendor)->postJson(route('backend.vendor-weighted-rates.apply'), ['inputs' => $sample])->assertOk();
        $before = CalculatorState::where('user_id', $vendor->id)->firstOrFail()->scenario;
        $sample['roles'][0]['billRate'] = -1;
        $this->postJson(route('backend.vendor-weighted-rates.apply'), ['inputs' => $sample])->assertUnprocessable()->assertJsonStructure(['errors' => ['roles.0.billRate']]);
        $this->assertSame($before, CalculatorState::where('user_id', $vendor->id)->firstOrFail()->scenario);
    }

    public function test_admin_can_use_the_module_with_its_own_saved_state(): void
    {
        $admin = $this->vendor();
        $admin->update(['user_type' => 'admin']);
        $this->actingAs($admin)->postJson(route('backend.vendor-weighted-rates.apply'), [
            'inputs' => VendorWeightedRateEngine::presets()['six_person'],
        ])->assertOk()->assertJsonPath('result.mode', 'wage_based');
        $this->assertEqualsWithDelta(21.97714285714286, data_get(
            app(CalculatorViewStateResolver::class)->resolve($admin, 'main-menu-calculator.index'),
            'scenario.meta.vendorWeightedBillRate'
        ), 1e-12);
    }

    public function test_general_report_excludes_private_vendor_rates_and_staffing(): void
    {
        $vendor = $this->vendor();
        app(CalculatorStateStore::class)->store($vendor, 'main-menu', $this->scenario());
        $this->actingAs($vendor)->postJson(route('backend.vendor-weighted-rates.apply'), ['inputs' => VendorWeightedRateEngine::presets()['sample']])->assertOk();
        $state = CalculatorState::where('user_id', $vendor->id)->firstOrFail();
        $html = '';
        $pdf = \Mockery::mock(PDF::class)->makePartial();
        $pdf->shouldReceive('loadView')->once()->withArgs(function ($view, $data) use (&$html) {
            $this->assertArrayNotHasKey('vendorComparison', $data['result']['tabs']);
            $this->assertArrayNotHasKey('vendorWeightedRates', $data['scenario']['meta']);
            $html = view($view, $data)->render();

            return true;
        })->andReturnSelf();
        $pdf->shouldReceive('setPaper', 'setWarnings', 'render')->andReturnSelf();
        $pdf->shouldReceive('getDomPDF')->andReturn(new Dompdf);
        $this->app->instance('dompdf.wrapper', $pdf);
        app(ReportService::class)->calculatorPdf('main-menu', ['scenario' => $state->scenario, 'result' => $state->result, 'user' => $vendor]);
        $this->assertStringNotContainsString('Unarmed officer — subcontractors', $html);
        $this->assertStringNotContainsString('Vendor Comparison', $html);
        $this->assertStringNotContainsString('39.95', $html);
        $this->assertStringContainsString('75', $html);
    }
}
