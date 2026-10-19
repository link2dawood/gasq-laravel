<?php

namespace Tests\Unit;

use App\Services\V24\MainMenu\EconomicJustificationEngine as MainMenuEngine;
use App\Services\V24\MainMenu\MainMenuComputeService;
use App\Services\V24\Standalone\EconomicJustificationEngine as StandaloneEngine;
use Tests\TestCase;

class EconomicJustificationEngineTest extends TestCase
{
    public function test_roi_and_summary_match_the_updated_reference_fixtures(): void
    {
        $base = base_path('tests/Fixtures/v24/main_menu/case_basic');
        $scenario = json_decode(file_get_contents($base.'.json'), true)['scenario'];
        $expected = json_decode(file_get_contents($base.'.expected.json'), true)['tabs'];
        $actual = app(MainMenuComputeService::class)->compute($scenario)['tabs'];
        $this->assertEquals($expected['economicJustification'], $actual['economicJustification']);
        $this->assertEquals($expected['contractSummary'], $actual['contractSummary']);

        $base = base_path('tests/Fixtures/v24/standalone/economic-justification.basic');
        $scenario = json_decode(file_get_contents($base.'.json'), true)['scenario'];
        $expected = json_decode(file_get_contents($base.'.expected.json'), true)['kpis'];
        $this->assertEquals($expected, (new StandaloneEngine)->compute($scenario));
    }

    public function test_roi_totals_use_the_same_service_hours_and_calendar_as_costs(): void
    {
        foreach ([new MainMenuEngine, new StandaloneEngine] as $engine) {
            $out = $engine->compute(['meta' => [
                'employeeTrueHourlyCost' => 55,
                'weeklyHoursPerformed' => 5679,
                'weeklyHours' => 5679,
                'weeksInYear' => 52,
                'monthsInYear' => 12,
            ]]);

            $this->assertEquals(5679, $out['totalWeeklyHours']);
            $this->assertEquals(24609, $out['totalMonthlyHours']);
            $this->assertEquals(295308, $out['totalAnnualHours']);
            $this->assertSame(203, $out['totalStaffRequired']);
            $this->assertSame(38.5, $out['vendorHourly']);
            $this->assertSame(16241940.0, $out['inHouseAnnual']);
            $this->assertSame(11369358.0, $out['vendorAnnual']);
        }
    }

    public function test_employer_cost_and_hours_changes_refresh_vendor_cost_and_contract_summary(): void
    {
        $service = app(MainMenuComputeService::class);
        $scenario = ['meta' => ['employeeTrueHourlyCost' => 55, 'weeklyHoursPerformed' => 168, 'weeksInYear' => 52, 'monthsInYear' => 12]];
        $before = $service->compute($scenario)['tabs'];
        $scenario['meta']['employeeTrueHourlyCost'] = 80;
        $scenario['meta']['weeklyHoursPerformed'] = 200;
        $scenario['meta']['weeksInYear'] = 48;
        $scenario['meta']['monthsInYear'] = 10;
        $after = $service->compute($scenario)['tabs'];
        $roi = $after['economicJustification'];
        $rows = array_column($after['contractSummary']['tableRows'], 'value', 'label');

        $this->assertSame(38.5, $before['economicJustification']['vendorHourly']);
        $this->assertSame(56.0, $roi['vendorHourly']);
        $this->assertSame(960.0, $roi['totalMonthlyHours']);
        $this->assertSame(9600.0, $roi['totalAnnualHours']);
        $this->assertSame(8, $roi['totalStaffRequired']);
        $this->assertSame(200.0, $rows['Weekly Hours']);
        $this->assertSame(56.0, $rows['Blended Straight Time Bill Rate']);
        $this->assertSame(11200.0, $rows['Weekly Billings']);
        $this->assertSame(537600.0, $rows['Total Annual Revenue (hourly billings)']);
        $this->assertSame($roi['vendorAnnual'], $rows['Total Annual Revenue (hourly billings)']);
    }

    public function test_zero_service_hours_require_no_staff_or_cost(): void
    {
        foreach ([new MainMenuEngine, new StandaloneEngine] as $engine) {
            $out = $engine->compute(['meta' => ['weeklyHoursPerformed' => 0, 'weeklyHours' => 0, 'monthsInYear' => 0]]);
            $this->assertSame(0, $out['totalStaffRequired']);
            foreach (['totalWeeklyHours', 'totalMonthlyHours', 'totalAnnualHours', 'inHouseAnnual', 'vendorAnnual'] as $key) {
                $this->assertEquals(0, $out[$key]);
            }
        }
    }
}
