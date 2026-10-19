<?php

namespace Tests\Feature;

use App\Models\CalculatorState;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorStatePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_roi_updates_save_matching_vendor_costs_and_summary_for_reports(): void
    {
        $user = User::factory()->create([
            'user_type' => 'vendor',
            'nda_accepted_at' => now(),
            'phone_verified' => true,
        ]);
        app(WalletService::class)->addTokens($user, 500, 'test', 'Calculator test');

        foreach ([55 => 38.5, 80 => 56] as $employerCost => $vendorCost) {
            $response = $this->actingAs($user)->postJson(route('backend.main-menu.compute'), [
                'version' => 'v24',
                'scenario' => ['meta' => [
                    'employeeTrueHourlyCost' => $employerCost,
                    'weeklyHoursPerformed' => 200,
                    'weeksInYear' => 48,
                    'monthsInYear' => 12,
                ]],
            ]);

            $response->assertOk()->assertJsonPath('ok', true);
            $this->assertEquals($vendorCost, $response->json('tabs.economicJustification.vendorHourly'));
            $this->assertEquals(800, $response->json('tabs.economicJustification.totalMonthlyHours'));
            $this->assertEquals(9600, $response->json('tabs.economicJustification.totalAnnualHours'));
            $response->assertJsonPath('tabs.economicJustification.totalStaffRequired', 8);

            $state = CalculatorState::where('user_id', $user->id)->where('calculator_type', 'main-menu')->firstOrFail();
            $summary = array_column(data_get($state->result, 'tabs.contractSummary.tableRows'), 'value', 'label');
            $this->assertEquals($employerCost, data_get($state->scenario, 'meta.employeeTrueHourlyCost'));
            $this->assertEquals($vendorCost, $summary['Blended Straight Time Bill Rate']);
            $this->assertEquals($vendorCost * 9600, $summary['Total Annual Revenue (hourly billings)']);
            $this->assertEquals($state->result, session('report_payload.result'));
        }
    }

    public function test_report_payload_store_persists_latest_calculator_state_to_database(): void
    {
        $user = User::factory()->create([
            'phone' => null,
            'phone_verified' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('backend.report-payload.store'), [
            'type' => 'mobile-patrol-comparison',
            'scenario' => [
                'a' => ['hoursPerDay' => 24],
                'b' => ['hoursPerDay' => 12],
            ],
            'result' => [
                'scenario_a_annual' => 1000,
                'scenario_b_annual' => 800,
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('calculator_states', [
            'user_id' => $user->id,
            'calculator_type' => 'mobile-patrol-comparison',
        ]);

        $state = CalculatorState::query()
            ->where('user_id', $user->id)
            ->where('calculator_type', 'mobile-patrol-comparison')
            ->first();

        $this->assertNotNull($state);
        $this->assertSame(24, data_get($state?->scenario, 'a.hoursPerDay'));
        $this->assertSame(800, data_get($state?->result, 'scenario_b_annual'));
        $this->assertSame('mobile-patrol-comparison', session('report_payload.type'));
    }
}
