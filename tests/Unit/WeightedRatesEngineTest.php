<?php

namespace Tests\Unit;

use App\Services\V24\Standalone\BillRateBreakdownEngine;
use App\Services\V24\Standalone\WeightedRatesEngine;
use Tests\TestCase;

class WeightedRatesEngineTest extends TestCase
{
    private function calc(array $inputs = []): array
    {
        return (new WeightedRatesEngine)->compute($inputs);
    }

    public function test_template_reproduces_full_precision_role_costs_and_rounding_difference(): void
    {
        $r = $this->calc();
        $this->assertTrue($r['isValid']);
        $t = $r['totals'];
        $this->assertEqualsWithDelta(51.28 / 6, $t['weightedPayRate'], 1e-10);
        $this->assertEqualsWithDelta(18 / 7, $r['multiplier'], 1e-10);
        $this->assertSame(21.98, round($t['weightedBillRate'], 2));
        $this->assertEquals(240, $t['weeklyHours']);
        $this->assertEquals(12480, $t['annualHours']);
        $this->assertEquals(12480, $t['annualPaidHours']);
        $this->assertEquals(168, $t['weeklyCoverageHours']);
        $this->assertEquals(8736, $t['annualCoverageHours']);
        $this->assertEqualsWithDelta(10 / 7, $t['paidToCoverageRatio'], 1e-10);
        $this->assertSame(31.40, round($t['coverageBillRate'], 2));
        $this->assertEqualsWithDelta($t['annualBillCost'], $t['coverageBillRate'] * 8736, 1e-8);
        $this->assertEqualsWithDelta($t['annualEmployerCost'], $t['employerCostPerCoverageHour'] * 8736, 1e-8);
        $this->assertSame(274310.40, round($t['annualCostAtRoundedCoverageBillRate'], 2));
        $this->assertSame(35.66, round($t['coverageRoundingDifference'], 2));
        $this->assertSame([], $r['warnings']);
        $this->assertSame(274274.74, round($t['annualBillCost'], 2));
        $this->assertSame(274310.40, round($t['annualCostAtRoundedBillRate'], 2));
        $this->assertSame(35.66, round($t['roundingDifference'], 2));
        $this->assertEquals([6240, 4160, 2080], array_column($r['positions'], 'annualHours'));
        $this->assertEquals([88893.26, 112747.89, 72633.60], array_map(fn ($p) => round($p['annualBillCost'], 2), $r['positions']));
        $this->assertEqualsWithDelta(1, array_sum(array_column($r['positions'], 'timeShare')), 1e-10);
        $this->assertEqualsWithDelta(1, array_sum(array_column($r['positions'], 'wageShare')), 1e-10);
    }

    public function test_fifty_percent_markup_and_changed_hours_use_editable_assumptions(): void
    {
        $r = $this->calc(['markupPct' => 50, 'weeklyHoursPerPerson' => 20, 'annualWeeks' => 26]);
        $this->assertSame(18.31, round($r['totals']['weightedBillRate'], 2));
        $this->assertEquals(3120, $r['totals']['annualHours']);
        $this->assertEqualsWithDelta(51.28 / 6 / 0.7 * 1.5 * 3120, $r['totals']['annualBillCost'], 1e-8);
        $r = $this->calc(['employerCostSharePct' => 20, 'markupPct' => 0]);
        $this->assertEqualsWithDelta(51.28 / 6 / 0.8, $r['totals']['weightedBillRate'], 1e-10);
    }

    public function test_each_role_can_be_solved_from_either_target_type(): void
    {
        $defaults = WeightedRatesEngine::defaults();
        foreach (['pay' => 10.0, 'bill' => 25.0, 'coverageBill' => 25.0] as $targetType => $target) {
            foreach ([0, 1, 2] as $index) {
                $positions = $defaults['positions'];
                $positions[$index]['payRate'] = null;
                $r = $this->calc(['mode' => 'solve', 'solveIndex' => $index, 'targetType' => $targetType, 'targetRate' => $target, 'positions' => $positions]);
                $this->assertTrue($r['isValid']);
                $key = match ($targetType) {
                    'pay' => 'weightedPayRate',
                    'bill' => 'weightedBillRate',
                    'coverageBill' => 'coverageBillRate',
                };
                $this->assertEqualsWithDelta($target, $r['totals'][$key], 1e-10);
                foreach ($positions as $i => $p) {
                    if ($i !== $index) {
                        $this->assertEquals($p['payRate'], $r['positions'][$i]['payRate']);
                    }
                }
            }
        }
    }

    public function test_generator_centers_distinct_rates_on_target_with_an_unequal_roster(): void
    {
        $positions = WeightedRatesEngine::defaults()['positions'];
        foreach ([4, 3, 2] as $i => $count) {
            $positions[$i]['count'] = $count;
            unset($positions[$i]['payRate']);
        }
        foreach (['pay' => 20.0, 'bill' => 50.0, 'coverageBill' => 50.0] as $targetType => $target) {
            $r = $this->calc(['mode' => 'generate', 'targetType' => $targetType, 'targetRate' => $target, 'positions' => $positions, 'seniorPremium' => 2, 'supervisorPremium' => 6]);
            $this->assertTrue($r['isValid']);
            $rates = array_column($r['positions'], 'payRate');
            $this->assertEqualsWithDelta(2, $rates[1] - $rates[0], 1e-10);
            $this->assertEqualsWithDelta(6, $rates[2] - $rates[0], 1e-10);
            $key = match ($targetType) {
                'pay' => 'weightedPayRate',
                'bill' => 'weightedBillRate',
                'coverageBill' => 'coverageBillRate',
            };
            $this->assertEqualsWithDelta($target, $r['totals'][$key], 1e-10);
        }
    }

    public function test_invalid_and_infeasible_inputs_return_errors_without_misleading_totals(): void
    {
        $positions = WeightedRatesEngine::defaults()['positions'];
        $zero = array_map(fn ($p) => array_replace($p, ['count' => 0]), $positions);
        $zeroSolved = $positions;
        $zeroSolved[2]['count'] = 0;
        $badCount = $positions;
        $badCount[0]['count'] = 1.5;
        $missingPay = $positions;
        $missingPay[0]['payRate'] = '';
        foreach ([
            ['employerCostSharePct' => 100],
            ['employerCostSharePct' => -1],
            ['markupPct' => -1],
            ['weeklyHoursPerPerson' => 0],
            ['annualWeeks' => 0],
            ['coveragePosts' => 0],
            ['coveragePosts' => 1.5],
            ['positions' => $zero],
            ['positions' => $badCount],
            ['positions' => $missingPay],
            ['positions' => []],
            ['mode' => 'solve', 'positions' => $zeroSolved],
            ['mode' => 'solve', 'solveIndex' => 4],
            ['mode' => 'solve', 'targetRate' => 1],
            ['mode' => 'generate', 'targetRate' => 1],
            ['mode' => 'generate', 'supervisorPremium' => 4],
            ['mode' => 'generate', 'targetRate' => null],
        ] as $inputs) {
            $r = $this->calc($inputs);
            $this->assertFalse($r['isValid'], json_encode($inputs));
            $this->assertNotEmpty($r['errors']);
            $this->assertNull($r['totals']);
        }
    }

    public function test_zero_pay_and_unstaffed_roles_have_defined_shares(): void
    {
        $positions = WeightedRatesEngine::defaults()['positions'];
        foreach ($positions as &$p) {
            $p['payRate'] = 0;
        }
        unset($p);
        $positions[0]['count'] = 0;
        $r = $this->calc(['positions' => $positions]);
        $this->assertTrue($r['isValid']);
        $this->assertEquals(0, $r['totals']['wageShare']);
        $this->assertEquals(0, $r['totals']['annualBillCost']);
        $this->assertEquals(0, $r['positions'][0]['timeShare']);
    }

    public function test_weighted_template_integrates_without_changing_the_seven_layer_summary(): void
    {
        $engine = new BillRateBreakdownEngine;
        $baseline = $engine->compute([]);
        $r = $engine->compute(['meta' => ['brb' => ['weightedRates' => ['markupPct' => 50]]]]);
        $this->assertEquals($baseline['summary'], $r['summary']);
        $this->assertSame(18.31, round($r['weightedRates']['totals']['weightedBillRate'], 2));
        $this->assertArrayNotHasKey('weightedRates', $baseline);
    }

    public function test_existing_bill_rate_fixture_remains_identical(): void
    {
        $path = base_path('tests/Fixtures/v24/standalone/bill-rate-analysis.basic');
        $in = json_decode(file_get_contents($path.'.json'), true, flags: JSON_THROW_ON_ERROR);
        $expected = json_decode(file_get_contents($path.'.expected.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertEquals($expected['kpis'], (new BillRateBreakdownEngine)->compute($in['scenario']));
    }

    public function test_extra_posts_change_coverage_rates_without_changing_paid_roster_costs(): void
    {
        $one = $this->calc();
        $two = $this->calc(['coveragePosts' => 2]);
        $this->assertEquals(17472, $two['totals']['annualCoverageHours']);
        foreach (['annualPaidHours', 'annualBillCost', 'weightedPayRate', 'weightedBillRate'] as $key) {
            $this->assertSame($one['totals'][$key], $two['totals'][$key]);
        }
        $this->assertEqualsWithDelta($one['totals']['coverageBillRate'] / 2, $two['totals']['coverageBillRate'], 1e-10);
        $this->assertNotEmpty($two['warnings']);
        $this->assertTrue($two['isValid']);
    }

    public function test_coverage_target_uses_the_entered_posts_hours_and_markup(): void
    {
        $r = $this->calc([
            'mode' => 'generate', 'targetType' => 'coverageBill', 'targetRate' => 40,
            'coveragePosts' => 2, 'weeklyHoursPerPerson' => 35, 'annualWeeks' => 50, 'markupPct' => 50,
        ]);
        $this->assertTrue($r['isValid']);
        $this->assertEquals(16800, $r['totals']['annualCoverageHours']);
        $this->assertEquals(10500, $r['totals']['annualPaidHours']);
        $this->assertEqualsWithDelta(40, $r['totals']['coverageBillRate'], 1e-10);
        $this->assertEqualsWithDelta(672000, $r['totals']['annualBillCost'], 1e-6);
        $this->assertEqualsWithDelta(64, $r['totals']['weightedBillRate'], 1e-10);
    }
}
