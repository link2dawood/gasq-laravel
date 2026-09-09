<?php

namespace Tests\Unit;

use App\Services\CostToProtectEstimate;
use Tests\TestCase;

class CostToProtectEstimateTest extends TestCase
{
    /**
     * Scenario behind the reference master estimate: $29.38 baseline wage over
     * 24h × 4 days × 52 weeks of coverage.
     *
     * @return array<string, mixed>
     */
    private function scenario(): array
    {
        return ['meta' => [
            'annualBudget' => 538769,
            'baselineWage' => 29.38,
            'scope' => [
                'hoursOfCoveragePerDay' => 24,
                'daysOfCoveragePerWeek' => 4,
                'weeksOfCoverage' => 52,
                'staffPerShift' => 1,
            ],
            'contact' => [
                'contactName' => 'Test Buyer',
                'companyName' => 'Northgate Logistics',
                'contactEmail' => 'buyer@example.com',
            ],
        ]];
    }

    public function test_it_computes_the_cost_to_protect_figures_the_estimate_prints(): void
    {
        $d = app(CostToProtectEstimate::class)->build($this->scenario());

        // Coverage
        $this->assertEquals(96, $d['weeklyCoverageHours']);
        $this->assertEquals(416, $d['monthlyCoverageHours']);
        $this->assertEquals(4992, $d['annualCoverageHours']);
        $this->assertSame(4, $d['ftesRequired']);

        // Rates
        $this->assertEqualsWithDelta(107.93, $d['internalTcoHourly'], 0.01);
        $this->assertEqualsWithDelta(75.55, $d['vendorTcoHourly'], 0.01);
        $this->assertEqualsWithDelta(161.89, $d['internalOtHourly'], 0.01);
        $this->assertEqualsWithDelta(113.32, $d['vendorOtHourly'], 0.01);

        // Totals and recovery
        $this->assertEqualsWithDelta(538769.24, $d['totalAnnualInternal'], 0.5);
        $this->assertEqualsWithDelta(377138.47, $d['totalAnnualVendor'], 0.5);
        $this->assertEqualsWithDelta(161630.77, $d['annualCapitalRecovery'], 0.5);
        $this->assertSame(30, $d['recoveryPct']);
        $this->assertEqualsWithDelta(8.4, $d['paybackMonths'], 0.05);

        // Donut shares always add up
        $this->assertEqualsWithDelta(100.0, $d['internalSharePct'] + $d['vendorSharePct'], 0.001);
    }

    public function test_it_falls_back_to_defaults_on_an_empty_scenario_without_dividing_by_zero(): void
    {
        $d = app(CostToProtectEstimate::class)->build([]);

        $this->assertGreaterThan(0, $d['totalAnnualInternal']);
        $this->assertGreaterThan(0, $d['ftesRequired']);
        $this->assertGreaterThan(0, $d['paybackMonths']);
    }

    public function test_contact_prefers_the_calculator_details(): void
    {
        $d = app(CostToProtectEstimate::class)->build($this->scenario());

        $this->assertSame('Test Buyer', $d['contact']['name']);
        $this->assertSame('Northgate Logistics', $d['contact']['company']);
        $this->assertSame('buyer@example.com', $d['contact']['email']);
    }
}
