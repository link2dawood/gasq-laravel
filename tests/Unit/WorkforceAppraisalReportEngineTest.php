<?php

namespace Tests\Unit;

use App\Services\V24\Standalone\WorkforceAppraisalReportEngine;
use Tests\TestCase;

class WorkforceAppraisalReportEngineTest extends TestCase
{
    public function test_demo_defaults_match_workbook_excerpt_totals(): void
    {
        $eng = new WorkforceAppraisalReportEngine;
        $k = $eng->compute([
            'meta' => [
                'annualBillableHours' => 21322,
            ],
        ]);

        $this->assertSame('workbook:CFO_Bill_Rate+Post_Positions+Appraisal_Summary', $k['reference']);

        $this->assertEqualsWithDelta(43.67, $k['cfoBillRate']['grandTotal']['hourly'], 0.001);
        $this->assertEqualsWithDelta(931084.24, $k['cfoBillRate']['grandTotal']['annual'], 0.02);

        $tot = $k['postPositionSummary']['totals'];
        $this->assertEquals(15, $tot['qty']);
        $this->assertEqualsWithDelta(680928.0, $tot['annualDirectLaborCost'], 0.02);

        $rows = $k['appraisalComparison']['rows'];
        $last = $rows[array_key_last($rows)];
        $this->assertSame('Total Annual Cost', $last['description']);
        $this->assertEqualsWithDelta(78.25 * 21322, $last['internal'], 0.05);
        $this->assertEqualsWithDelta(54.78 * 21322, $last['vendor'], 0.05);

        $foot = $k['appraisalComparison']['footerRows'][0];
        $this->assertSame('Operational Capital Recovered', $foot['description']);
        $this->assertGreaterThan(500000, $foot['vendor']);
    }

    public function test_scope_of_work_derives_annual_hours_and_ftes(): void
    {
        $eng = new WorkforceAppraisalReportEngine;

        $k = $eng->compute([
            'meta' => [
                'annualBillableHours' => 6240,
                'scope' => [
                    'hoursOfCoveragePerDay' => 12,
                    'daysOfCoveragePerWeek' => 5,
                    'weeksOfCoverage' => 52,
                    'staffPerShift' => 2,
                ],
                'appraisal' => [
                    'hoursPerProfessionalAnnual' => 1560,
                ],
            ],
        ]);

        $scope = $k['scopeOfWork'];

        $this->assertEquals(60, $scope['derived']['weeklyCoverageHours']);
        $this->assertEquals(3120, $scope['derived']['totalAnnualHours']);
        $this->assertEquals(120, $scope['derived']['weeklyBillableHours']);
        $this->assertEquals(6240, $scope['derived']['annualBillableHours']);
        $this->assertEquals(4, $scope['derived']['ftesRequiredRoundedUp']);

        $posts = $k['postPositionSummary']['totals'];
        $this->assertEquals(6240, $posts['annualHours']);
        $this->assertEquals(3120, $posts['totalAnnualHours']);
    }

    public function test_post_position_rows_derive_weekly_monthly_and_annual_costs_from_user_inputs(): void
    {
        $eng = new WorkforceAppraisalReportEngine;

        $k = $eng->compute([
            'meta' => [
                'posts' => [
                    [
                        'positionTitle' => 'Test Officer',
                        'blendedPayRate' => 20.00,
                        'annualHours' => 2080,
                    ],
                ],
            ],
        ]);

        $row = $k['postPositionSummary']['rows'][0];
        $totals = $k['postPositionSummary']['totals'];

        $this->assertSame('Test Officer', $row['positionTitle']);
        $this->assertEquals(40, $row['weeklyHours']);
        $this->assertEqualsWithDelta(800.00, $row['weeklyCost'], 0.01);
        $this->assertEqualsWithDelta(173.33, $row['monthlyHours'], 0.01);
        $this->assertEqualsWithDelta(3466.67, $row['monthlyCost'], 0.01);
        $this->assertEquals(2080, $row['annualHours']);
        $this->assertEqualsWithDelta(41600.00, $row['annualDirectLaborCost'], 0.01);

        $this->assertEquals(2080, $totals['annualHours']);
        $this->assertEquals(40, $totals['weeklyHours']);
        $this->assertEqualsWithDelta(173.33, $totals['monthlyHours'], 0.01);
        $this->assertEqualsWithDelta(41600.00, $totals['annualDirectLaborCost'], 0.01);
    }

    public function test_post_position_summary_preserves_sparse_row_indexes(): void
    {
        $eng = new WorkforceAppraisalReportEngine;

        $k = $eng->compute([
            'meta' => [
                'posts' => [
                    [
                        'index' => 4,
                        'positionTitle' => 'Late Row Officer',
                        'blendedPayRate' => 22.00,
                        'annualHours' => 1040,
                    ],
                ],
            ],
        ]);

        $row = $k['postPositionSummary']['rows'][0];

        $this->assertSame(4, $row['index']);
        $this->assertSame('Late Row Officer', $row['positionTitle']);
        $this->assertEqualsWithDelta(20.00, $row['weeklyHours'], 0.01);
        $this->assertEqualsWithDelta(1906.67, $row['monthlyCost'], 0.02);
    }
}
