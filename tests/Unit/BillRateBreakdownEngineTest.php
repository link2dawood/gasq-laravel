<?php

namespace Tests\Unit;

use App\Services\V24\Standalone\BillRateBreakdownEngine;
use Tests\TestCase;

/**
 * Acceptance fixtures from the GASQ Seven Layer Pricing Model spec, Version 1.0
 * (September 2026): section 17.1 baseline regression and 17.2 AT001-AT014.
 * Any engine change that moves these values must fail here unless the approved
 * assumptions were intentionally changed.
 */
class BillRateBreakdownEngineTest extends TestCase
{
    /** @param array<string, mixed> $brb */
    private function calc(array $brb = []): array
    {
        return (new BillRateBreakdownEngine)->compute(['meta' => ['brb' => $brb]]);
    }

    /** @return array<int, string> validation codes raised by a scenario */
    private function codes(array $brb = []): array
    {
        return array_column($this->calc($brb)['validations'], 'code');
    }

    public function test_approved_baseline_regression(): void
    {
        $r = $this->calc();

        $this->assertEqualsWithDelta(40500, $r['coverage']['annualHours'], 1e-9);
        $this->assertEqualsWithDelta(1120, $r['layer1']['totalWeeklyPaidHours'], 1e-9);
        $this->assertEqualsWithDelta(58240, $r['layer1']['totalAnnualPaidHours'], 1e-9);
        $this->assertEqualsWithDelta(1528800, $r['layer1']['totalPayroll'], 1e-6);
        $this->assertEqualsWithDelta(26.25, $r['summary']['weightedWage'], 1e-9);
        $this->assertEqualsWithDelta(37.50, $r['summary']['fullBurden'], 1e-9);
        $this->assertEqualsWithDelta(11.25, $r['layer2']['incrementalBurden'], 1e-9);
        $this->assertEqualsWithDelta(0.30, $r['layer2']['burdenShare'], 1e-12);
        $this->assertEqualsWithDelta(1456, $r['staffing']['availableHoursPerEmployee'], 1e-9);
        $this->assertEqualsWithDelta(624, $r['layer3']['wmcHours'], 1e-9);
        $this->assertEqualsWithDelta(624, $r['layer3']['categoryTotal'], 1e-9);
        $this->assertEqualsWithDelta(6, $r['summary']['manpowerPerPost'], 1e-9);
        $this->assertSame(28, $r['staffing']['roundedHeadcount']);
        $this->assertEqualsWithDelta(40768, $r['staffing']['availableCapacity'], 1e-9);
        $this->assertEqualsWithDelta(268, $r['summary']['staffingReserveCapacity'], 1e-9);
        $this->assertEqualsWithDelta(3744, $r['layer3']['wmcPerPost'], 1e-9);
        $this->assertEqualsWithDelta(140400, $r['layer3']['wmcValue'], 1e-6);
        $this->assertEqualsWithDelta(67.50, $r['summary']['finalBillRate'], 1e-9);
        $this->assertEqualsWithDelta(96.428571, $r['summary']['costToProtect'], 1e-6);
        $this->assertSame('96.43', number_format($r['summary']['costToProtect'], 2));
        $this->assertEqualsWithDelta(28.928571, $r['summary']['croHourly'], 1e-6);
        $this->assertSame('28.93', number_format($r['summary']['croHourly'], 2));
        $this->assertEqualsWithDelta(2733750, $r['summary']['annualContractValue'], 1e-6);
        $this->assertSame('1,171,607.14', number_format($r['summary']['annualCro'], 2));
        // Spec prints 4.635 (truncated); the raw value 4.63599 rounds to 4.636.
        $this->assertEqualsWithDelta(40500 / 8736, $r['summary']['equivalentPosts'], 1e-12);

        $this->assertSame([], array_filter($r['validations'], fn ($m) => $m['blocking']));
    }

    public function test_layer_2_default_thirty_percent_allocation(): void
    {
        $lines = $this->calc()['layer2']['lines'];
        // Spec 4.1 hourly allocations against the $37.50 employer cost.
        $expected = [
            'socialSecurity' => 1.62, 'medicare' => 0.38, 'futa' => 0.15, 'suta' => 0.53,
            'workersComp' => 0.78, 'medical' => 4.77, 'dental' => 0.38, 'vision' => 0.12,
            'retirement' => 1.31, 'lifeInsurance' => 0.12, 'disability' => 0.37,
            'eapWellness' => 0.12, 'hsaFsa' => 0.38,
            // Spec prints $0.22 for the balancing row so the column shows $11.25;
            // 0.57% of $37.50 is $0.2138 raw. The display balances, the maths does not.
            'otherBenefit' => 0.21,
        ];
        $byKey = array_column($lines, null, 'key');

        foreach ($expected as $key => $hourly) {
            $this->assertSame(number_format($hourly, 2), number_format($byKey[$key]['hourly'], 2), $key);
        }
        $this->assertSame('30.00%', number_format(array_sum(array_column($lines, 'share')) * 100, 2) . '%');
        $this->assertSame('11.25', number_format(array_sum(array_column($lines, 'hourly')), 2));
    }

    public function test_layer_3_default_624_hour_allocation(): void
    {
        $l3 = $this->calc()['layer3'];

        $this->assertEqualsWithDelta(30.00, $l3['rateAllocation'], 1e-9);
        $this->assertTrue($l3['categoriesReconcile']);

        // Spec 5.1 hours and rate allocation. The spec's final row carries a one
        // cent display adjustment ($3.84); raw values stay unrounded here.
        $expected = [
            'vacation' => [120, 5.77], 'sick' => [40, 1.92], 'holidays' => [80, 3.85],
            'training' => [40, 1.92], 'breaks' => [104, 5.00], 'absenteeism' => [80, 3.85],
            'turnover' => [80, 3.85], 'unbillableOt' => [80, 3.85],
        ];
        $byKey = array_column($l3['categories'], null, 'key');
        foreach ($expected as $key => [$hours, $hourly]) {
            $this->assertEqualsWithDelta($hours, $byKey[$key]['hours'], 1e-9, $key);
            $this->assertSame(number_format($hourly, 2), number_format($byKey[$key]['hourly'], 2), $key);
        }
        $this->assertSame('624', number_format(array_sum(array_column($l3['categories'], 'hours'))));
        $this->assertSame('30.00', number_format(array_sum(array_column($l3['categories'], 'hourly')), 2));
    }

    public function test_detailed_burden_method_reconciles_to_the_configured_share(): void
    {
        $r = $this->calc(['burden' => ['method' => 'detailed']]);

        $this->assertEqualsWithDelta(37.50, $r['layer2']['fullBurden'], 1e-9);
        $this->assertTrue($r['layer2']['reconciles']);
        $this->assertNotContains('VAL003', $this->codes(['burden' => ['method' => 'detailed']]));

        // VAL003 — shares that miss the configured 30% burden share block approval.
        $short = ['burden' => ['method' => 'detailed', 'items' => [
            ['key' => 'socialSecurity', 'label' => 'FICA', 'method' => 'pct', 'value' => 25],
        ]]];
        $this->assertContains('VAL003', $this->codes($short));
    }

    public function test_position_bill_rates_allocate_the_final_rate_by_cash_wage(): void
    {
        $positions = $this->calc()['layer1']['positions'];

        $this->assertSame('64.80', number_format($positions[0]['positionBillRate'], 2));
        $this->assertSame('72.36', number_format($positions[1]['positionBillRate'], 2));
        $this->assertSame('61.71%', number_format($positions[0]['wageShare'] * 100, 2) . '%');
        $this->assertSame('19.14%', number_format($positions[1]['wageShare'] * 100, 2) . '%');
        $this->assertSame('2.571428571', number_format($this->calc()['layer1']['rateMultiplier'], 9));
    }

    /** AT001 — a wage change flows through payroll, rate, benchmarks and forecast. */
    public function test_at001_wage_change_flows_through_every_layer(): void
    {
        $base = $this->calc();
        $positions = BillRateBreakdownEngine::defaults()['positions'];
        $positions[0]['hourlyWage'] = 27.00;
        $r = $this->calc(['positions' => $positions]);

        $this->assertGreaterThan($base['layer1']['totalPayroll'], $r['layer1']['totalPayroll']);
        $this->assertGreaterThan($base['summary']['weightedWage'], $r['summary']['weightedWage']);
        $this->assertGreaterThan($base['summary']['fullBurden'], $r['summary']['fullBurden']);
        $this->assertGreaterThan($base['summary']['finalBillRate'], $r['summary']['finalBillRate']);
        $this->assertGreaterThan($base['summary']['costToProtect'], $r['summary']['costToProtect']);
        $this->assertGreaterThan($base['forecast']['totals']['contractValue'], $r['forecast']['totals']['contractValue']);
    }

    /** AT003 — Layer 3 hours that miss 624 block approval unless overridden. */
    public function test_at003_workforce_maintenance_hours_must_reconcile(): void
    {
        $short = ['wmcCategories' => [['key' => 'sick', 'label' => 'Sick Leave', 'hours' => 100]]];
        $r = $this->calc($short);

        $this->assertContains('VAL005', array_column($r['validations'], 'code'));
        $this->assertTrue(collect($r['validations'])->firstWhere('code', 'VAL005')['blocking']);

        $authorised = $this->calc($short + ['wmcCategoryOverrideReason' => 'Client-approved schedule']);
        $this->assertFalse(collect($authorised['validations'])->firstWhere('code', 'VAL005')['blocking']);
    }

    /** AT004 — cash H&W plus the same employer benefit is a duplicate cost error. */
    public function test_at004_duplicate_health_and_welfare(): void
    {
        $positions = BillRateBreakdownEngine::defaults()['positions'];
        $positions[0]['healthWelfareCash'] = 4.22;

        $this->assertContains('VAL004', $this->codes(['positions' => $positions]));
    }

    /** AT005 / AT006 — inactive, absorbed and buyer-provided costs contribute zero. */
    public function test_at005_and_at006_recovery_status_controls_the_rate(): void
    {
        $line = fn (array $over) => ['odc' => [array_merge(
            ['key' => 'uniforms', 'label' => 'Uniforms', 'basis' => 'hour', 'amount' => 2, 'enabled' => true, 'provider' => 'vendor', 'recoveryStatus' => 'recovered'],
            $over,
        )]];

        $this->assertEqualsWithDelta(2.0, $this->calc($line([]))['layer4']['hourly'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->calc($line(['enabled' => false]))['layer4']['hourly'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->calc($line(['recoveryStatus' => 'absorbed']))['layer4']['hourly'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->calc($line(['provider' => 'buyer', 'recoveryStatus' => 'buyer_provided']))['layer4']['hourly'], 1e-9);

        // An absorbed cost stays visible with its own hourly figure.
        $absorbed = $this->calc($line(['recoveryStatus' => 'absorbed']))['layer4']['lines'][0];
        $this->assertEqualsWithDelta(2.0, $absorbed['hourly'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $absorbed['contributedHourly'], 1e-9);
    }

    /** AT007 — approved mode never adds Layers 4-6 to the approved rate. */
    public function test_at007_approved_mode_embeds_layers_4_to_6(): void
    {
        $r = $this->calc(['odc' => [['key' => 'uniforms', 'label' => 'Uniforms', 'enabled' => true, 'basis' => 'hour', 'amount' => 2]]]);

        $this->assertEqualsWithDelta(67.50, $r['summary']['finalBillRate'], 1e-9);
        $this->assertEqualsWithDelta(57.375, $r['layer6']['preProfit'], 1e-9);
        $this->assertEqualsWithDelta(10.125, $r['layer6']['profitHourly'], 1e-9);
        $this->assertEqualsWithDelta(67.5 - 37.5 - 2 - 10.125, $r['reconciliation']['remaining'], 1e-9);
        $this->assertSame('embedded', $r['layer4']['treatment']);
    }

    /** AT008 — build-up adds Layers 4 and 5 before the Layer 6 gross-up. */
    public function test_at008_full_build_up_adds_every_layer(): void
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

    /** AT009 — a 15% margin on $38.00 returns $44.705882 raw, $44.71 display, 17.65% markup. */
    public function test_at009_margin_gross_up_and_markup(): void
    {
        $r = $this->calc([
            'pricingMode' => 'buildup',
            'positions' => [],
            'ga' => [['key' => 'x', 'label' => 'Fixed', 'enabled' => true, 'method' => 'hourly', 'value' => 38]],
        ]);

        $this->assertEqualsWithDelta(44.705882, $r['summary']['finalBillRate'], 1e-6);
        $this->assertSame('44.71', number_format($r['summary']['finalBillRate'], 2));
        $this->assertEqualsWithDelta(6.705882, $r['layer6']['profitHourly'], 1e-6);
        $this->assertSame('15.00%', number_format($r['layer6']['profitHourly'] / $r['summary']['finalBillRate'] * 100, 2) . '%');
        $this->assertSame('17.65%', number_format($r['layer6']['markup'] * 100, 2) . '%');
    }

    /** AT010 — above cost to protect, recovery is zero and the premium is positive. */
    public function test_at010_premium_above_cost_to_protect(): void
    {
        $r = $this->calc(['finalRateOverride' => ['value' => 110, 'reason' => 'Negotiated']]);

        $this->assertSame(0.0, $r['summary']['croHourly']);
        $this->assertEqualsWithDelta(110 - 140400 / 1456, $r['summary']['premiumAboveCtp'], 1e-9);
    }

    /** AT011 / AT012 — price lock, overrides and the reset path. */
    public function test_at011_and_at012_price_lock_and_year_override(): void
    {
        $years = $this->calc(['forecast' => ['years' => [
            ['year' => 2, 'priceLock' => true],
            ['year' => 3, 'rateOverride' => 72],
        ]]])['forecast']['years'];

        $this->assertEqualsWithDelta(0.0, $years[1]['rateEscalation'], 1e-12);
        $this->assertEqualsWithDelta(67.50, $years[1]['finalBillRate'], 1e-9);
        $this->assertSame('overridden', $years[2]['status']);
        // Year 2 is price locked, so Year 3 escalates from $67.50, not from $69.53.
        $this->assertEqualsWithDelta(67.5 * 1.03, $years[2]['calculatedRate'], 1e-9);
        $this->assertEqualsWithDelta(72 * 1.03, $years[3]['finalBillRate'], 1e-9);
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

    public function test_validation_codes(): void
    {
        $positions = BillRateBreakdownEngine::defaults()['positions'];

        // VAL001 negative input · VAL002 wage share · VAL008 margin · VAL017 schedule
        $this->assertContains('VAL001', $this->codes(['coverage' => ['annualHours' => -5]]));
        $this->assertContains('VAL002', $this->codes(['burden' => ['wageSharePct' => 0]]));
        $this->assertContains('VAL008', $this->codes(['profit' => ['marginPct' => 100]]));
        $this->assertContains('VAL017', $this->codes(['coverage' => ['mode' => 'schedule', 'annualHours' => 40500, 'daysPerWeek' => 5]]));

        // VAL006 paid hours must reconcile to available + maintenance
        $this->assertContains('VAL006', $this->codes(['workforce' => ['wmcHoursOverride' => 700]]));
        $this->assertContains('VAL016', $this->codes(['workforce' => ['wmcHoursOverride' => 700]]));
        $this->assertNotContains('VAL016', $this->codes(['workforce' => ['wmcHoursOverride' => 700, 'wmcOverrideReason' => 'Union agreement']]));

        // VAL010 / VAL011 staffing shortfalls are warnings, not blockers
        $short = $this->calc(['coverage' => ['annualHours' => 50000]]);
        $this->assertContains('VAL010', array_column($short['validations'], 'code'));
        $this->assertContains('VAL011', array_column($short['validations'], 'code'));
        $this->assertSame([], array_filter($short['validations'], fn ($m) => $m['blocking']));

        // VAL013 supervision in Layers 1 and 4 · VAL014 shared duplicate key
        $this->assertContains('VAL013', $this->codes([
            'odc' => [['key' => 'dedicatedSupervisor', 'label' => 'Dedicated Field Supervision', 'duplicateKey' => 'supervision', 'enabled' => true, 'basis' => 'hour', 'amount' => 1]],
        ]));
        $this->assertContains('VAL014', $this->codes([
            'positions' => [],
            'odc' => [['key' => 'vehicleInsurance', 'label' => 'Vehicle Insurance', 'duplicateKey' => 'insurance', 'enabled' => true, 'basis' => 'hour', 'amount' => 1]],
            'ga' => [['key' => 'corporateInsurance', 'label' => 'Corporate Insurance', 'duplicateKey' => 'insurance', 'enabled' => true, 'method' => 'hourly', 'value' => 1]],
        ]));

        // VAL015 profit carried outside Layer 6 · VAL018 embedded allocations over the rate
        $this->assertContains('VAL015', $this->codes([
            'ga' => [['key' => 'x', 'label' => 'Profit reserve', 'enabled' => true, 'method' => 'hourly', 'value' => 2]],
        ]));
        $this->assertContains('VAL018', $this->codes([
            'ga' => [['key' => 'admin', 'label' => 'Admin', 'enabled' => true, 'method' => 'hourly', 'value' => 25]],
        ]));
    }
}
