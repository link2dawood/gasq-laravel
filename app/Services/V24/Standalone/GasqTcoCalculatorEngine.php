<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class GasqTcoCalculatorEngine
{
    public function __construct(
        private WorkforceAppraisalReportEngine $report
    ) {}

    /**
     * Lightweight “TCO summary” wrapper around the Workforce Appraisal report.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);
        $includeReport = (bool) ($meta['includeReport'] ?? false);
        $report = $includeReport ? $this->report->compute($scenario) : null;

        $hours = (float) Arr::get($meta, 'annualBillableHours', $includeReport ? Arr::get($report, 'cfoBillRate.annualBillableHours', 21322) : 21322);
        $billRate = (float) Arr::get($meta, 'gasqBillRateHourly', $includeReport ? Arr::get($report, 'cfoBillRate.grandTotal.hourly', 43.68) : 43.68);
        $annualTotal = $includeReport ? (float) Arr::get($report, 'cfoBillRate.grandTotal.annual', round($billRate * max(1.0, $hours), 2)) : round($billRate * max(1.0, $hours), 2);

        $vendorTco = (float) Arr::get($meta, 'vendorTcoHourly', $includeReport ? Arr::get($report, 'priceRealism.benchmark.2.hourly', 54.78) : 54.78);
        $vendorAnnual = round($vendorTco * max(1.0, $hours), 2);

        $premiumHr = round($vendorTco - $billRate, 2);
        $premiumAnnual = round($vendorAnnual - $annualTotal, 2);

        return [
            'summary' => [
                'annualBillableHours' => round($hours, 2),
                'gasqBillRateHourly' => round($billRate, 2),
                'gasqAnnualTotal' => round($annualTotal, 2),
                'vendorTcoHourly' => round($vendorTco, 2),
                'vendorAnnualTotal' => round($vendorAnnual, 2),
                'vendorPremiumHourly' => $premiumHr,
                'vendorPremiumAnnual' => $premiumAnnual,
            ],
            'report' => $report,
            'reference' => 'standalone:gasq-tco-calculator',
        ];
    }
}

