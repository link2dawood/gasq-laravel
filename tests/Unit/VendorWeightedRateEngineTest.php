<?php

namespace Tests\Unit;

use App\Services\V24\MainMenu\MainMenuComputeService;
use App\Services\VendorWeightedRateApplication;
use App\Services\VendorWeightedRateEngine;
use Tests\TestCase;

class VendorWeightedRateEngineTest extends TestCase
{
    public function test_attached_sample_reproduces_every_acceptance_total_with_implied_labels(): void
    {
        $r = app(VendorWeightedRateEngine::class)->compute(VendorWeightedRateEngine::presets()['sample']);
        $this->assertTrue($r['isValid']);
        $t = $r['totals'];
        $this->assertCount(8, $r['roles']);
        foreach (['headcount' => 145, 'weeklyHours' => 5800, 'monthlyHours' => 25133.33, 'annualRoleHours' => 301600, 'annualBillCost' => 12048524.80, 'weightedBillRate' => 39.95, 'weightedPayWage' => 15.54] as $key => $expected) {
            $this->assertEqualsWithDelta($expected, round($t[$key], 2), 1e-8, $key);
        }
        $this->assertSame(38.89, round($t['overallWageShare'] * 100, 2));
        $this->assertEqualsWithDelta(12048524.80 * 7 / 18, $t['annualWages'], 1e-8);
        foreach ($r['roles'] as $role) {
            $this->assertSame('Implied pay wage — model estimate', $role['paySourceLabel']);
            $this->assertEqualsWithDelta(7 / 18, $role['wageShareOfBillRate'], 1e-12);
        }
        $this->assertNotSame(round($t['weightedBillRate'], 2) * $t['annualRoleHours'], $t['annualBillCost']);
    }

    public function test_unequal_hours_weight_wages_and_bill_rates_by_annual_role_hours(): void
    {
        $in = VendorWeightedRateEngine::presets()['sample'];
        $in['roles'] = [
            ['classification' => 'unarmed', 'headcount' => 2, 'actualWage' => 10, 'billRate' => 20, 'weeklyHoursPerEmployee' => 20, 'weeksPerYear' => 26],
            ['classification' => 'armed', 'headcount' => 1, 'actualWage' => 30, 'billRate' => 60],
        ];
        $r = app(VendorWeightedRateEngine::class)->compute($in);
        $this->assertTrue($r['isValid']);
        $this->assertEquals(3120, $r['totals']['annualRoleHours']);
        $this->assertEqualsWithDelta((10 * 1040 + 30 * 2080) / 3120, $r['totals']['weightedPayWage'], 1e-10);
        $this->assertEqualsWithDelta((20 * 1040 + 60 * 2080) / 3120, $r['totals']['weightedBillRate'], 1e-10);
        $this->assertEqualsWithDelta(0.5, $r['totals']['overallWageShare'], 1e-10);
    }

    public function test_actual_wage_replaces_estimate_without_changing_current_bill_rate(): void
    {
        $engine = app(VendorWeightedRateEngine::class);
        $in = VendorWeightedRateEngine::presets()['sample'];
        $before = $engine->compute($in);
        $in['roles'][0]['actualWage'] = 20;
        $after = $engine->compute($in);
        $this->assertSame($before['totals']['annualBillCost'], $after['totals']['annualBillCost']);
        $this->assertSame(20.0, $after['roles'][0]['payRate']);
        $this->assertSame('actual', $after['roles'][0]['paySource']);
        $this->assertSame('Mixed actual wages and model estimates', $after['wageSource']);
        $this->assertEqualsWithDelta(20 / 39.29, $after['roles'][0]['wageShareOfBillRate'], 1e-10);
        $expectedWages = $before['totals']['annualWages'] + (20 - $before['roles'][0]['payRate']) * 43680;
        $this->assertEqualsWithDelta($expectedWages, $after['totals']['annualWages'], 1e-8);
        $this->assertEqualsWithDelta($expectedWages / 301600, $after['totals']['weightedPayWage'], 1e-10);
        $this->assertEqualsWithDelta($expectedWages / $after['totals']['annualBillCost'], $after['totals']['overallWageShare'], 1e-10);
    }

    public function test_model_constants_are_fixed_independently_of_staffing_and_hours(): void
    {
        $in = VendorWeightedRateEngine::presets()['six_person'];
        $in['weeklyHoursPerEmployee'] = 30;
        $in['weeksPerYear'] = 40;
        $in['roles'][0]['headcount'] = 12;
        $in['roles'][] = ['classification' => 'custom', 'label' => 'Custom security role', 'headcount' => 2, 'actualWage' => 19.125];
        $r = app(VendorWeightedRateEngine::class)->compute($in);
        $this->assertTrue($r['isValid']);
        foreach ($r['roles'] as $role) {
            $this->assertEqualsWithDelta($role['payRate'] / 0.7 * 3744 / 2080, $role['billRate'], 1e-10);
        }
        $this->assertSame(3744, $r['model']['modelHours']);
        $this->assertSame('GASQ 6:1 model rate', $r['rateSource']);
    }

    public function test_zero_bill_rates_have_unavailable_wage_shares(): void
    {
        $in = VendorWeightedRateEngine::presets()['sample'];
        $in['roles'] = [['classification' => 'unarmed', 'headcount' => 1, 'billRate' => 0, 'actualWage' => 10]];
        $r = app(VendorWeightedRateEngine::class)->compute($in);
        $this->assertTrue($r['isValid']);
        $this->assertNull($r['roles'][0]['wageShareOfBillRate']);
        $this->assertNull($r['totals']['overallWageShare']);
        $this->assertEquals(20800, $r['totals']['annualWages']);
    }

    public function test_missing_and_negative_inputs_have_field_errors_and_no_weighted_totals(): void
    {
        foreach ([
            ['weeklyHoursPerEmployee' => -1], ['weeksPerYear' => -1], ['roles' => []],
            ['roles.0.headcount' => -1], ['roles.0.headcount' => 1.2],
            ['roles.0.billRate' => -1], ['roles.0.billRate' => null],
            ['roles.0.actualWage' => -1], ['roles.0.weeklyHoursPerEmployee' => -1],
            ['roles.0.weeksPerYear' => -1], ['weeklyHoursPerEmployee' => 0],
            ['mode' => 'wage_based'], ['roles.0.classification' => 'custom', 'roles.0.label' => ''],
        ] as $changes) {
            $in = VendorWeightedRateEngine::presets()['sample'];
            foreach ($changes as $path => $value) {
                data_set($in, $path, $value);
            }
            $r = app(VendorWeightedRateEngine::class)->compute($in);
            $this->assertFalse($r['isValid'], json_encode($changes));
            $this->assertNotEmpty($r['fieldErrors']);
            $this->assertNull($r['totals']);
        }
    }

    public function test_application_preserves_baseline_and_service_hours_and_estimates_at_full_precision(): void
    {
        $application = app(VendorWeightedRateApplication::class);
        $scenario = ['meta' => ['basePayRate' => 24.75, 'employeeTrueHourlyCost' => 133, 'weeklyHoursPerformed' => 168, 'weeksInYear' => 52, 'vendorTrueHourlyCost' => 75], 'scope' => [], 'posts' => []];
        $applied = $application->apply($scenario, VendorWeightedRateEngine::presets()['sample']);
        $this->assertSame(24.75, $applied['meta']['basePayRate']);
        $this->assertSame(168, $applied['meta']['weeklyHoursPerformed']);
        $this->assertSame(52, $applied['meta']['weeksInYear']);
        $out = app(MainMenuComputeService::class)->compute($applied);
        $c = $out['tabs']['vendorComparison'];
        $this->assertEquals(8736, $c['annualBillableServiceHours']);
        $this->assertEquals(301600, $c['annualPayrollHours']);
        $this->assertEqualsWithDelta(12048524.8 / 301600 * 8736, $c['projectAnnualEstimate'], 1e-8);
        $this->assertSame(24.75, $out['tabs']['billRate']['basePayRate']);
        $this->assertSame($scenario, $application->publicScenario($applied));
        $applied['meta']['annualBillableServiceHours'] = 1000;
        $this->assertEqualsWithDelta($c['weightedBillRate'] * 1000, app(MainMenuComputeService::class)->compute($applied)['tabs']['vendorComparison']['projectAnnualEstimate'], 1e-8);
    }

    public function test_main_menu_baseline_rates_are_unchanged_without_an_applied_vendor_rate(): void
    {
        $base = base_path('tests/Fixtures/v24/main_menu/case_basic');
        $inputs = json_decode(file_get_contents($base.'.json'), true);
        $expected = json_decode(file_get_contents($base.'.expected.json'), true);
        $out = app(MainMenuComputeService::class)->compute($inputs['scenario'])['tabs'];
        $this->assertEquals($expected['tabs']['billRate'], $out['billRate']);
        $this->assertSame(array_keys($expected['tabs']['economicJustification']), array_keys($out['economicJustification']));
        foreach ($expected['tabs']['economicJustification'] as $key => $value) {
            $this->assertEqualsWithDelta($value, $out['economicJustification'][$key], 1e-8, $key);
        }
        $this->assertArrayNotHasKey('vendorComparison', $out);
    }
}
