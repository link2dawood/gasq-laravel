<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class GasqDirectLaborBuildUpEngine
{
    public function __construct(
        private WorkforceAppraisalReportEngine $report,
    ) {}

    /**
     * Returns the CFO-style direct labor build-up stack (direct + fringe + ops + overhead).
     * Annual amounts are hourly × annualBillableHours.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);
        $annualBillableHours = max(1.0, (float) Arr::get($meta, 'annualBillableHours', 21322));

        // Allow line-item overrides by passing through to the report engine’s override map.
        $override = (array) Arr::get($meta, 'cfoHourlyOverrides', []);

        $out = $this->report->compute([
            'meta' => [
                'annualBillableHours' => $annualBillableHours,
                'cfoHourlyOverrides' => $override,
            ],
        ]);

        return [
            'title' => 'GASQ Direct Labor Build-Up',
            'annualBillableHours' => $annualBillableHours,
            'stack' => $out['cfoBillRate'],
            'reference' => 'standalone:gasq-direct-labor-build-up',
        ];
    }
}

