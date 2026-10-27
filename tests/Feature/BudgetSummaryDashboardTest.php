<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BudgetSummaryService;
use App\Services\CalculatorStateStore;
use App\Services\MasterInputsService;
use App\Services\ReportService;
use App\Services\V24\MainMenu\MainMenuComputeService;
use App\Services\VendorWeightedRateApplication;
use App\Services\VendorWeightedRateEngine;
use App\Services\WalletService;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetSummaryDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $type = 'vendor'): User
    {
        $user = User::factory()->create(['user_type' => $type, 'phone_verified' => true, 'nda_accepted_at' => now()]);
        app(MasterInputsService::class)->getOrCreate($user)->update(['is_complete' => true]);
        app(WalletService::class)->addTokens($user, 500, 'test', 'Budget summary test');

        return $user;
    }

    private function saveBudget(User $user, array $meta = []): void
    {
        app(CalculatorStateStore::class)->store($user, 'budget-calculator', ['meta' => array_replace([
            'baselineWage' => 25,
            'hoursPerDay' => 24, 'daysPerWeek' => 7, 'weeksPerYear' => 52, 'staffPerShift' => 1,
        ], $meta)], ['kpis' => ['total' => 1, 'internalTcoHourly' => 999]]);
    }

    public function test_budget_math_is_recomputed_from_inputs_with_shared_baseline_and_distinct_bill_rates(): void
    {
        $user = $this->user();
        $this->saveBudget($user);
        $summary = app(BudgetSummaryService::class)->forUser($user);

        $this->assertSame(25.0, $summary['baselineHourly']);
        $this->assertEquals(91.84, $summary['inHouseHourly']);
        $this->assertEquals(64.29, $summary['vendorHourly']);
        $this->assertEquals(802285.71, $summary['inHouseAnnual']);
        $this->assertEquals(561600, $summary['vendorAnnual']);
        $this->assertEquals(240685.71, $summary['costSavings']);
        $this->assertEquals(168, $summary['totalWeeklyHours']);
        $this->assertEquals(728, $summary['totalMonthlyHours']);
        $this->assertEquals(8736, $summary['totalAnnualHours']);
        $this->assertSame(6, $summary['totalStaffRequired']);
        $this->assertEquals(8.4, $summary['paybackMonths']);
    }

    public function test_vendor_dashboard_and_roi_show_the_saved_budget_and_reading_does_not_charge_credits(): void
    {
        $user = $this->user();
        $this->saveBudget($user);
        $this->actingAs($user);
        $before = app(WalletService::class)->getBalance($user);
        foreach (['home', 'main-menu-calculator.index'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('Buyer Cost to Protect In-house')->assertSee('Buyer Cost to Protect via Outsourcing')
                ->assertSee('Hourly Bill / Cost to Protect Rate')->assertSee('$91.84')->assertSee('$64.29')
                ->assertSee('$802,285.71')->assertSee('$561,600.00')
                ->assertSee('>168</td>', false)->assertSee('>728</td>', false)->assertSee('>8,736</td>', false)
                ->assertDontSee('>168.00</td>', false)->assertDontSee('>728.00</td>', false);
        }
        $this->assertSame($before, app(WalletService::class)->getBalance($user));
        $this->assertSame(1, $user->calculatorStates()->count());
    }

    public function test_roi_uses_latest_own_budget_instead_of_old_or_forged_client_figures(): void
    {
        $user = $this->user();
        $other = $this->user();
        $this->saveBudget($user);
        $this->saveBudget($other, ['baselineWage' => 500]);
        $scenario = app(BudgetSummaryService::class)->applyToMainMenu($user, ['meta' => ['companyName' => 'My Site']]);
        $this->saveBudget($user, ['baselineWage' => 35, 'weeksPerYear' => 40]);
        $scenario['meta']['budgetScenario']['meta']['baselineWage'] = 999;
        $response = $this->actingAs($user)->postJson(route('backend.main-menu.compute'), ['version' => 'v24', 'scenario' => $scenario])
            ->assertOk()->assertJsonPath('tabs.economicJustification.baselineHourly', 35)
            ->assertJsonPath('tabs.economicJustification.totalAnnualHours', 6720);
        $summary = app(BudgetSummaryService::class)->forUser($user);
        $this->assertEquals($summary['inHouseAnnual'], $response->json('tabs.economicJustification.inHouseAnnual'));
        $rows = array_column($response->json('tabs.contractSummary.tableRows'), 'value', 'label');
        $this->assertEquals($summary['vendorAnnual'], $rows['Total Annual Revenue (hourly billings)']);
        $saved = $user->calculatorStates()->where('calculator_type', 'main-menu')->firstOrFail();
        $this->assertEquals(35, data_get($saved->scenario, 'meta.budgetScenario.meta.baselineWage'));
        $this->assertSame('My Site', data_get($saved->scenario, 'meta.companyName'));
    }

    public function test_missing_budget_has_clear_empty_state_and_does_not_use_another_users_budget(): void
    {
        $owner = $this->user();
        $this->saveBudget($owner);
        $other = $this->user();
        $this->actingAs($other);
        foreach (['home', 'main-menu-calculator.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Save your Budget Calculator inputs')
                ->assertDontSee('$802,285.71');
        }
        $this->postJson(route('backend.main-menu.compute'), ['version' => 'v24', 'scenario' => ['meta' => ['roiCostBasis' => 'budget']]])
            ->assertUnprocessable()->assertJsonValidationErrors('budget');
        $this->assertSame(0, $other->calculatorStates()->count());
    }

    public function test_fractional_hours_are_preserved_and_short_coverage_weeks_reconcile_to_annual_cost(): void
    {
        $user = $this->user();
        $this->saveBudget($user, ['scope' => ['hoursOfCoveragePerDay' => 7.5, 'daysOfCoveragePerWeek' => 5, 'weeksOfCoverage' => 41, 'staffPerShift' => 2]]);
        $summary = app(BudgetSummaryService::class)->forUser($user);
        $this->assertEquals(75, $summary['totalWeeklyHours']);
        $this->assertEquals(256.25, $summary['totalMonthlyHours']);
        $this->assertEquals(3075, $summary['totalAnnualHours']);
        $this->assertEqualsWithDelta($summary['inHouseAnnual'], $summary['inHouseWeekly'] * 41, 1e-8);
        $this->assertEqualsWithDelta($summary['vendorAnnual'], $summary['vendorMonthly'] * 12, 1e-8);
        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('>256.25</td>', false)->assertSee('>75</td>', false);
    }

    public function test_dashboard_currency_is_converted_once_and_buyer_does_not_see_vendor_summary(): void
    {
        $vendor = $this->user();
        $vendor->update(['currency' => 'CAD']);
        $this->saveBudget($vendor);
        $this->actingAs($vendor);
        $summary = app(BudgetSummaryService::class)->forUser($vendor);
        $this->get(route('home'))->assertOk()->assertSee(Currency::format($summary['inHouseAnnual']))->assertSee('>8,736</td>', false);
        $buyer = $this->user('buyer');
        $this->saveBudget($buyer);
        $this->actingAs($buyer)->get(route('home'))->assertOk()->assertSee('$802,285.71')
            ->assertDontSee('Buyer Cost to Protect via Outsourcing')->assertDontSee('$561,600.00')->assertDontSee('$240,685.71');
    }

    public function test_applied_weighted_quote_does_not_replace_the_budget_basis_of_roi(): void
    {
        $user = $this->user();
        $this->saveBudget($user);
        $scenario = app(BudgetSummaryService::class)->applyToMainMenu($user, []);
        $scenario = app(VendorWeightedRateApplication::class)->apply($scenario, VendorWeightedRateEngine::presets()['sample']);
        $result = app(MainMenuComputeService::class)->compute($scenario);
        $this->assertEquals(64.29, $result['tabs']['economicJustification']['vendorHourly']);
        $this->assertEquals(561600, $result['tabs']['economicJustification']['vendorAnnual']);
        $this->assertArrayHasKey('vendorComparison', $result['tabs']);
    }

    public function test_budget_roi_report_uses_latest_saved_budget_and_matching_formatted_summary(): void
    {
        $user = $this->user();
        $this->saveBudget($user);
        $scenario = app(BudgetSummaryService::class)->applyToMainMenu($user, []);
        $result = app(MainMenuComputeService::class)->compute($scenario);
        $this->saveBudget($user, ['baselineWage' => 35]);
        $realCompute = app(MainMenuComputeService::class);
        $this->partialMock(MainMenuComputeService::class)->shouldReceive('compute')->once()
            ->withArgs(fn ($current) => data_get($current, 'meta.budgetScenario.meta.baselineWage') === 35.0)
            ->andReturnUsing(fn ($current) => $realCompute->compute($current));
        $pdf = app(ReportService::class)->calculatorPdf('main-menu', compact('scenario', 'result', 'user'), 'SamplePdfOnly2026');
        $scenario = app(BudgetSummaryService::class)->applyToMainMenu($user, $scenario);
        $result = $realCompute->compute($scenario);
        $html = view('pdf.main-menu', compact('scenario', 'result', 'user'))->render();
        $this->assertStringContainsString('Budget Summary &amp; ROI Analysis', $html);
        $this->assertStringContainsString('$35.00', $html);
        $this->assertStringNotContainsString('$25.00', $html);
        $this->assertStringContainsString('>168</td>', $html);
        $this->assertStringContainsString('/Encrypt', $pdf->output());
    }
}
