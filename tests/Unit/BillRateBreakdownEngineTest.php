<?php

namespace Tests\Unit;

use App\Services\V24\Standalone\BillRateBreakdownEngine;
use Tests\TestCase;

/**
 * Regression fixtures from the Bill Rate Breakdown implementation spec
 * (Sep 19, 2026), sections 20-21. Any engine change that moves these values
 * must fail here unless the approved assumptions were intentionally changed.
 */
class BillRateBreakdownEngineTest extends TestCase
{
    /** @param array<string, mixed> $brb */
    private function calc(array $brb = []): array
    {
        return (new BillRateBreakdownEngine)->compute(['meta' => ['brb' => $brb]]);
    }

    public function test_approved_baseline_regression(): void
    {
        $r = $this->calc();

        $this->assertEqualsWithDelta(40500, $r['coverage']['annualHours'], 1e-9);
        $this->assertSame(28, $r['staffing']['roundedHeadcount']);
        $this->assertSame([18.0, 5.0, 5.0], array_column($r['layer1']['positions'], 'employees'));
        $this->assertEqualsWithDelta(1120, $r['layer1']['totalWeeklyPaidHours'], 1e-9);
        $this->assertEqualsWithDelta(58240, $r['layer1']['totalAnnualPaidHours'], 1e-9);
        $this->assertEqualsWithDelta(26.25, $r['summary']['weightedWage'], 1e-9);
        $this->assertEqualsWithDelta(1528800, $r['layer1']['totalPayroll'], 1e-6);
        $this->assertEqualsWithDelta(37.50, $r['summary']['fullBurden'], 1e-9);
        $this->assertEqualsWithDelta(1456, $r['staffing']['availableHoursPerEmployee'], 1e-9);
        $this->assertEqualsWithDelta(624, $r['layer3']['wmcHours'], 1e-9);
        $this->assertEqualsWithDelta(6, $r['summary']['manpowerPerPost'], 1e-9);
        $this->assertEqualsWithDelta(3744, $r['layer3']['wmcPerPost'], 1e-9);
        $this->assertEqualsWithDelta(140400, $r['layer3']['wmcValue'], 1e-6);
        $this->assertEqualsWithDelta(268, $r['summary']['staffingReserveCapacity'], 1e-9);
        $this->assertEqualsWithDelta(67.50, $r['summary']['finalBillRate'], 1e-9);
        $this->assertEqualsWithDelta(140400 / 1456, $r['summary']['costToProtect'], 1e-9);
        $this->assertEqualsWithDelta(140400 / 1456 - 67.5, $r['summary']['croHourly'], 1e-9);
        $this->assertEqualsWithDelta(2733750, $r['summary']['annualContractValue'], 1e-6);
        $this->assertSame('1,171,607.14', number_format($r['summary']['annualCro'], 2));
        // Spec prints 4.635 (truncated); the raw value 4.63599 rounds to 4.636.
        $this->assertEqualsWithDelta(40500 / 8736, $r['summary']['equivalentPosts'], 1e-12);
        $this->assertSame([], array_filter($r['warnings'], fn ($w) => $w['level'] === 'error'));
    }

    public function test_position_bill_rates_allocate_the_final_rate_by_wage(): void
    {
        $positions = $this->calc()['layer1']['positions'];

        $this->assertSame('64.80', number_format($positions[0]['positionBillRate'], 2));
        $this->assertSame('72.36', number_format($positions[1]['positionBillRate'], 2));
        $this->assertSame('61.71%', number_format($positions[0]['wageShare'] * 100, 2) . '%');
        $this->assertSame('19.14%', number_format($positions[1]['wageShare'] * 100, 2) . '%');
    }

    public function test_five_year_regression(): void
    {
        $years = $this->calc()['forecast']['years'];
        $expected = [
            // wage, employer cost, final rate, annual contract, CTP, annual CRO
            [26.25, 37.50, 67.50, 2733750, 96.43, 1171607],
            [27.04, 38.63, 69.53, 2815763, 99.32, 1206755],
            [27.85, 39.78, 71.61, 2900235, 102.30, 1242958],
            [28.68, 40.98, 73.76, 2987242, 105.37, 1280247],
            [29.54, 42.21, 75.97, 3076860, 108.53, 1318654],
        ];

        $this->assertCount(5, $years);
        foreach ($expected as $i => [$wage, $employer, $rate, $contract, $ctp, $cro]) {
            $y = $years[$i];
            $this->assertSame(number_format($wage, 2), number_format($y['wage'], 2), "Year {$y['year']} wage");
            $this->assertSame(number_format($employer, 2), number_format($y['fullBurden'], 2), "Year {$y['year']} employer cost");
            $this->assertSame(number_format($rate, 2), number_format($y['finalBillRate'], 2), "Year {$y['year']} rate");
            $this->assertSame(number_format($contract), number_format($y['annualContractValue']), "Year {$y['year']} contract");
            $this->assertSame(number_format($ctp, 2), number_format($y['costToProtect'], 2), "Year {$y['year']} CTP");
            $this->assertSame(number_format($cro), number_format($y['annualCro']), "Year {$y['year']} CRO");
        }

        $totals = $this->calc()['forecast']['totals'];
        $this->assertSame('14,513,850', number_format($totals['contractValue']));
        $this->assertSame('6,220,221', number_format($totals['annualCro']));
    }

    public function test_profit_is_a_margin_gross_up(): void
    {
        // Full build-up with a $38.00 pre-profit subtotal: labor subtotal is the
        // Layer 3 rate, so drive it through a fixed-rate G&A line on a zero-labor scenario.
        $r = $this->calc([
            'pricingMode' => 'buildup',
            'positions' => [],
            'ga' => [['key' => 'x', 'label' => 'Fixed', 'enabled' => true, 'method' => 'hourly', 'value' => 38]],
        ]);

        $this->assertSame('44.71', number_format($r['summary']['finalBillRate'], 2));
        $this->assertEqualsWithDelta(0.15, $r['layer6']['profitHourly'] / $r['summary']['finalBillRate'], 1e-12);
    }

    public function test_approved_mode_embeds_layers_4_to_6_without_adding_them(): void
    {
        $r = $this->calc([
            'odc' => [['key' => 'uniforms', 'label' => 'Uniforms', 'enabled' => true, 'basis' => 'hour', 'amount' => 2]],
        ]);

        $this->assertEqualsWithDelta(67.50, $r['summary']['finalBillRate'], 1e-9);
        $this->assertEqualsWithDelta(10.125, $r['layer6']['profitHourly'], 1e-9);
        $this->assertEqualsWithDelta(57.375, $r['layer6']['preProfit'], 1e-9);
        $this->assertEqualsWithDelta(67.5 - 37.5 - 2 - 10.125, $r['reconciliation']['remaining'], 1e-9);
        $this->assertSame('embedded', $r['layer4']['treatment']);
    }

    public function test_full_build_up_adds_every_layer(): void
    {
        $r = $this->calc([
            'pricingMode' => 'buildup',
            'odc' => [['key' => 'uniforms', 'label' => 'Uniforms', 'enabled' => true, 'basis' => 'year', 'amount' => 40500]],
            'ga' => [['key' => 'admin', 'label' => 'Admin', 'enabled' => true, 'method' => 'pct', 'value' => 10]],
        ]);

        // (67.50 + 1.00 ODC) + 10% G&A = 75.35, grossed up at 15%.
        $this->assertEqualsWithDelta(75.35 / 0.85, $r['summary']['finalBillRate'], 1e-9);
        $this->assertSame('additive', $r['layer5']['treatment']);
    }

    public function test_over_allocation_is_flagged_in_approved_mode(): void
    {
        $r = $this->calc([
            'ga' => [['key' => 'admin', 'label' => 'Admin', 'enabled' => true, 'method' => 'hourly', 'value' => 25]],
        ]);

        $this->assertContains('over_allocated', array_column($r['warnings'], 'code'));
        $this->assertEqualsWithDelta(67.50, $r['summary']['finalBillRate'], 1e-9);
    }

    public function test_cro_never_goes_negative(): void
    {
        $r = $this->calc(['finalRateOverride' => ['value' => 110, 'reason' => 'Negotiated']]);

        $this->assertSame(0.0, $r['summary']['croHourly']);
        $this->assertEqualsWithDelta(110 - 140400 / 1456, $r['summary']['premiumAboveCtp'], 1e-9);
    }

    public function test_guardrails(): void
    {
        $codes = fn (array $brb) => array_column($this->calc($brb)['warnings'], 'code');

        $this->assertContains('invalid_margin', $codes(['profit' => ['marginPct' => 100]]));
        $this->assertContains('negative_value', $codes(['coverage' => ['annualHours' => -5]]));
        $this->assertContains('capacity_shortage', $codes(['coverage' => ['annualHours' => 50000]]));
        $this->assertContains('headcount_below_minimum', $codes(['coverage' => ['annualHours' => 50000]]));
        $this->assertContains('wmc_unreconciled', $codes(['wmcCategories' => [['key' => 'sick', 'label' => 'Sick', 'hours' => 100]]]));
        $this->assertContains('duplicate_health_welfare', $codes([
            'compensation' => ['healthWelfareCash' => 4.22],
            'burden' => ['method' => 'lineItems', 'items' => [['key' => 'healthBenefits', 'label' => 'Health', 'method' => 'hourly', 'value' => 3]]],
        ]));
        $this->assertContains('duplicate_supervision', $codes([
            'odc' => [['key' => 'dedicatedSupervisor', 'label' => 'Dedicated Supervisor', 'enabled' => true, 'basis' => 'hour', 'amount' => 1]],
        ]));
    }

    public function test_price_lock_and_year_override(): void
    {
        $years = $this->calc(['forecast' => ['years' => [
            ['year' => 2, 'priceLock' => true],
            ['year' => 3, 'rateOverride' => 72],
        ]]])['forecast']['years'];

        $this->assertEqualsWithDelta(67.50, $years[1]['finalBillRate'], 1e-9);
        $this->assertSame('overridden', $years[2]['status']);
        $this->assertEqualsWithDelta(72 * 1.03, $years[3]['finalBillRate'], 1e-9);
    }
}
