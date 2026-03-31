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

        $this->assertEqualsWithDelta(43.68, $k['cfoBillRate']['grandTotal']['hourly'], 0.001);
        $this->assertEqualsWithDelta(931186.96, $k['cfoBillRate']['grandTotal']['annual'], 0.02);

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
}
