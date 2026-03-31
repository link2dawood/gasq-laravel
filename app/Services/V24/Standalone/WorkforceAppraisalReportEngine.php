<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

/**
 * Workforce-to-Post™ style report: CFO bill rate build-up, post position grid,
 * appraisal comparison, and price realism memo — aligned with V24 demo figures
 * from the Capital Recovery / CFO workbook excerpts.
 */
class WorkforceAppraisalReportEngine
{
    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);

        $annualBillableHours = max(1.0, (float) Arr::get($meta, 'annualBillableHours', 21322));

        $cfo = $this->computeCfoBreakdown($meta, $annualBillableHours);
        $posts = $this->computePostPositionSummary($meta);
        $appraisal = $this->computeAppraisalComparison($meta);
        $priceRealism = $this->computePriceRealism($meta, $annualBillableHours, $cfo);

        return [
            'cfoBillRate' => $cfo,
            'postPositionSummary' => $posts,
            'appraisalComparison' => $appraisal,
            'priceRealism' => $priceRealism,
            'reference' => 'workbook:CFO_Bill_Rate+Post_Positions+Appraisal_Summary',
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function computeCfoBreakdown(array $meta, float $hours): array
    {
        $ov = (array) Arr::get($meta, 'cfoHourlyOverrides', []);

        $sections = [
            [
                'key' => 'directLabor',
                'title' => 'Direct Labor',
                'titleClass' => 'text-primary fw-semibold',
                'rows' => [
                    ['key' => 'baseBlended', 'label' => 'Base Consolidated Blended Direct Labor Wage', 'highlight' => true],
                    ['key' => 'localityPay', 'label' => 'Locality Pay'],
                    ['key' => 'laborMarketAdj', 'label' => 'Labor Market Adjustment'],
                    ['key' => 'hwCash', 'label' => 'H&W (Cash)'],
                    ['key' => 'shiftDifferential', 'label' => 'Shift Differential'],
                    ['key' => 'otHolidayPremium', 'label' => 'OT/Holiday Premium'],
                    ['key' => 'donDoff', 'label' => 'DON/DOFF'],
                ],
                'subtotalKey' => 'totalDirectLabor',
                'subtotalLabel' => 'Total Direct Labor',
            ],
            [
                'key' => 'fringe',
                'title' => 'Fringe / Employer Burden',
                'titleClass' => 'text-primary fw-semibold',
                'rows' => [
                    ['key' => 'ficaMedicare', 'label' => 'FICA / Medicare'],
                    ['key' => 'futa', 'label' => 'FUTA'],
                    ['key' => 'suta', 'label' => 'SUTA'],
                    ['key' => 'workersComp', 'label' => 'Workers Compensation'],
                    ['key' => 'healthWelfare', 'label' => 'Health & Welfare'],
                    ['key' => 'vacation', 'label' => 'Vacation'],
                    ['key' => 'paidHolidays', 'label' => 'Paid Holidays'],
                    ['key' => 'sickLeave', 'label' => 'Sick Leave'],
                ],
                'subtotalKey' => 'totalFringe',
                'subtotalLabel' => 'Total Fringe / Burden',
            ],
            [
                'key' => 'operations',
                'title' => 'Operations / Support',
                'titleClass' => 'text-primary fw-semibold',
                'rows' => [
                    ['key' => 'recruiting', 'label' => 'Recruiting / Hiring'],
                    ['key' => 'training', 'label' => 'Training / Certification'],
                    ['key' => 'uniformsEquipment', 'label' => 'Uniforms / Equipment'],
                    ['key' => 'fieldSupervision', 'label' => 'Field Supervision'],
                    ['key' => 'contractManagement', 'label' => 'Contract Management'],
                    ['key' => 'qualityAssurance', 'label' => 'Quality Assurance'],
                    ['key' => 'vehiclesPatrol', 'label' => 'Vehicles / Patrol'],
                    ['key' => 'technologySystems', 'label' => 'Technology / Systems'],
                    ['key' => 'generalLiability', 'label' => 'General Liability Insurance'],
                    ['key' => 'umbrellaInsurance', 'label' => 'Umbrella / Other Insurance'],
                ],
                'subtotalKey' => 'totalOperations',
                'subtotalLabel' => 'Total Operations / Support',
            ],
            [
                'key' => 'overhead',
                'title' => 'Overhead / G&A / Profit',
                'titleClass' => 'text-primary fw-semibold',
                'rows' => [
                    ['key' => 'adminHrPayroll', 'label' => 'Administrative / HR / Payroll'],
                    ['key' => 'accountingLegal', 'label' => 'Accounting / Legal'],
                    ['key' => 'corporateOverhead', 'label' => 'Corporate Overhead'],
                    ['key' => 'ga', 'label' => 'G&A'],
                    ['key' => 'profitFee', 'label' => 'Profit / Fee'],
                ],
                'subtotalKey' => 'totalOverhead',
                'subtotalLabel' => 'Total Overhead / G&A / Profit',
            ],
        ];

        $defaults = [
            'baseBlended' => 20.76,
            'localityPay' => 0.0,
            'laborMarketAdj' => 0.0,
            'hwCash' => 4.22,
            'shiftDifferential' => 0.0,
            'otHolidayPremium' => 0.88,
            'donDoff' => 0.81,
            'ficaMedicare' => 2.04,
            'futa' => 0.16,
            'suta' => 0.53,
            'workersComp' => 0.43,
            'healthWelfare' => 0.0,
            'vacation' => 0.27,
            'paidHolidays' => 0.29,
            'sickLeave' => 0.05,
            'recruiting' => 0.0,
            'training' => 0.40,
            'uniformsEquipment' => 1.13,
            'fieldSupervision' => 0.0,
            'contractManagement' => 0.0,
            'qualityAssurance' => 0.0,
            'vehiclesPatrol' => 1.31,
            'technologySystems' => 0.27,
            'generalLiability' => 2.38,
            'umbrellaInsurance' => 0.20,
            'adminHrPayroll' => 0.0,
            'accountingLegal' => 0.0,
            'corporateOverhead' => 3.20,
            'ga' => 1.33,
            'profitFee' => 3.02,
        ];

        $hourlyMap = array_replace($defaults, $ov);

        $outSections = [];
        $grandHourly = 0.0;
        $grandAnnual = 0.0;

        foreach ($sections as $sec) {
            $rowsOut = [];
            $subH = 0.0;
            foreach ($sec['rows'] as $rowDef) {
                $key = $rowDef['key'];
                $h = (float) ($hourlyMap[$key] ?? 0.0);
                $a = round($h * $hours, 2);
                $subH += $h;
                $rowsOut[] = [
                    'key' => $key,
                    'label' => $rowDef['label'],
                    'hourly' => round($h, 2),
                    'annual' => $a,
                    'highlight' => (bool) ($rowDef['highlight'] ?? false),
                ];
            }
            $subA = round($subH * $hours, 2);
            $grandHourly += $subH;
            $grandAnnual += $subA;

            $laborPlusFringeHourly = null;
            $laborPlusFringeAnnual = null;
            if ($sec['key'] === 'fringe') {
                $dir = $outSections[0]['subtotal']['hourly'] ?? 0.0;
                $dirA = $outSections[0]['subtotal']['annual'] ?? 0.0;
                $laborPlusFringeHourly = round($dir + $subH, 2);
                $laborPlusFringeAnnual = round($dirA + $subA, 2);
            }

            $outSections[] = [
                'key' => $sec['key'],
                'title' => $sec['title'],
                'titleClass' => $sec['titleClass'],
                'rows' => $rowsOut,
                'subtotal' => [
                    'key' => $sec['subtotalKey'],
                    'label' => $sec['subtotalLabel'],
                    'hourly' => round($subH, 2),
                    'annual' => $subA,
                    'highlight' => true,
                ],
                'laborPlusFringe' => $laborPlusFringeHourly !== null ? [
                    'label' => 'Total Direct Labor + Fringe',
                    'hourly' => $laborPlusFringeHourly,
                    'annual' => $laborPlusFringeAnnual,
                    'highlight' => true,
                ] : null,
            ];
        }

        return [
            'annualBillableHours' => round($hours, 2),
            'sections' => $outSections,
            'grandTotal' => [
                'label' => 'Total Loaded Bill Rate (Hourly / Annual)',
                'hourly' => round($grandHourly, 2),
                'annual' => round($grandAnnual, 2),
                'highlight' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function computePostPositionSummary(array $meta): array
    {
        $rowsIn = Arr::get($meta, 'posts', null);
        if (! is_array($rowsIn) || $rowsIn === []) {
            $rowsIn = $this->defaultPostRows();
        }

        $rowsOut = [];
        $sumQty = 0;
        $sumWeeklyHrs = 0;
        $sumWeeklyCost = 0.0;
        $sumMonthlyHrs = 0;
        $sumMonthlyCost = 0.0;
        $sumAnnualHrs = 0.0;
        $sumAnnualCost = 0.0;
        $ratesForAvg = [];

        foreach ($rowsIn as $i => $r) {
            $r = (array) $r;
            $title = (string) ($r['positionTitle'] ?? $r['title'] ?? '—');
            $qty = max(0, (int) ($r['qty'] ?? 0));
            $pay = (float) ($r['blendedPayRate'] ?? 0.0);

            /** Annual hours per person (FTE) in this row */
            $annualHoursPer = isset($r['annualHours']) ? (float) $r['annualHours'] : null;
            if ($annualHoursPer === null && isset($r['weeklyHours'])) {
                $annualHoursPer = (float) $r['weeklyHours'] * 52.0;
            }
            $annualHoursPer = $annualHoursPer ?? 0.0;

            $weeklyHoursPer = (int) round($annualHoursPer / 52.0);
            $monthlyHoursPer = (int) round($annualHoursPer / 12.0);

            $annualHoursLine = $annualHoursPer * $qty;
            $annualCost = round($annualHoursPer * $pay * $qty, 2);
            $weeklyCost = $annualCost > 0 ? round($annualCost / 52.0, 2) : 0.0;
            $monthlyCost = $annualCost > 0 ? round($annualCost / 12.0, 2) : 0.0;

            if ($qty > 0 && $annualHoursPer > 0 && $pay > 0) {
                $ratesForAvg[] = $pay;
            }

            $sumQty += $qty;
            $sumWeeklyHrs += $weeklyHoursPer * $qty;
            $sumMonthlyHrs += $monthlyHoursPer * $qty;
            $sumWeeklyCost += $weeklyCost;
            $sumMonthlyCost += $monthlyCost;
            $sumAnnualHrs += $annualHoursLine;
            $sumAnnualCost += $annualCost;

            $rowsOut[] = [
                'index' => $i,
                'positionTitle' => $title,
                'qty' => $qty,
                'blendedPayRate' => round($pay, 2),
                'weeklyHours' => $weeklyHoursPer * $qty,
                'weeklyCost' => $weeklyCost,
                'monthlyHours' => $monthlyHoursPer * $qty,
                'monthlyCost' => $monthlyCost,
                'annualHours' => round($annualHoursLine, 2),
                'annualDirectLaborCost' => $annualCost,
            ];
        }

        $avgBlended = count($ratesForAvg) > 0 ? array_sum($ratesForAvg) / count($ratesForAvg) : 0.0;

        return [
            'rows' => $rowsOut,
            'totals' => [
                'qty' => $sumQty,
                'blendedPayRateAvg' => round($avgBlended, 2),
                'weeklyHours' => $sumWeeklyHrs,
                'weeklyCost' => round($sumWeeklyCost, 2),
                'monthlyHours' => $sumMonthlyHrs,
                'monthlyCost' => round($sumMonthlyCost, 2),
                'annualHours' => round($sumAnnualHrs, 2),
                'annualDirectLaborCost' => round($sumAnnualCost, 2),
            ],
            'derivedAnnualHoursFromPosts' => round($sumAnnualHrs, 2),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultPostRows(): array
    {
        return [
            ['positionTitle' => 'Unarmed S/O', 'qty' => 8, 'blendedPayRate' => 19.25, 'annualHours' => 2080],
            ['positionTitle' => 'Supervisor', 'qty' => 4, 'blendedPayRate' => 24.50, 'annualHours' => 2080],
            ['positionTitle' => 'Roving Patrol Officer', 'qty' => 3, 'blendedPayRate' => 21.00, 'annualHours' => 2496],
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function computeAppraisalComparison(array $meta): array
    {
        $reportMeta = (array) Arr::get($meta, 'appraisal', []);

        $baselineLaborRate = (float) Arr::get($reportMeta, 'baselineLaborRate', 30.43);
        $internalHourly = (float) Arr::get($reportMeta, 'governmentShouldCostHourly', 78.25);
        $vendorHourly = (float) Arr::get($reportMeta, 'vendorTcoHourly', 54.78);
        $totalWeeklyHours = (float) Arr::get($reportMeta, 'totalWeeklyHours', 410);
        $totalMonthlyHours = (float) Arr::get($reportMeta, 'totalMonthlyHours', 1777);
        $totalAnnualHours = (float) Arr::get($reportMeta, 'totalAnnualHours', 21322);
        $ftes = max(1, (int) Arr::get($reportMeta, 'ftesRequired', 15));
        $hoursPerProfessional = (float) Arr::get($reportMeta, 'hoursPerProfessionalAnnual', 1456);

        $otMult = (float) Arr::get($reportMeta, 'overtimeMultiplier', 1.5);
        $internalOt = round($internalHourly * $otMult, 2);
        $vendorOt = round($vendorHourly * $otMult, 2);

        $annualPerInt = round($internalHourly * $hoursPerProfessional, 2);
        $annualPerVend = round($vendorHourly * $hoursPerProfessional, 2);

        $totalWeeklyInt = round($internalHourly * $totalWeeklyHours, 2);
        $totalWeeklyVend = round($vendorHourly * $totalWeeklyHours, 2);
        $totalMonthlyInt = round($internalHourly * $totalMonthlyHours, 2);
        $totalMonthlyVend = round($vendorHourly * $totalMonthlyHours, 2);
        $totalAnnualInt = round($internalHourly * $totalAnnualHours, 2);
        $totalAnnualVend = round($vendorHourly * $totalAnnualHours, 2);

        $operationalCapital = round($totalAnnualInt - $totalAnnualVend, 2);
        $operationalCapitalPct = $totalAnnualInt > 0
            ? round(100.0 * $operationalCapital / $totalAnnualInt, 0)
            : 0.0;

        $monthlySavings = round($totalMonthlyInt - $totalMonthlyVend, 2);
        $paybackMonths = $monthlySavings > 0.01
            ? (int) ceil($operationalCapital / $monthlySavings)
            : 0;

        $rows = [
            ['description' => 'Workforce Baseline Assumption Labor Rate', 'internal' => $baselineLaborRate, 'vendor' => $baselineLaborRate],
            ['description' => 'Workforce Hourly Cost Per Security Professional', 'internal' => $internalHourly, 'vendor' => $vendorHourly],
            ['description' => 'Overtime/Holiday Rate', 'internal' => $internalOt, 'vendor' => $vendorOt],
            ['description' => 'Workforce Annual Cost per Security Professional', 'internal' => $annualPerInt, 'vendor' => $annualPerVend],
            ['description' => 'Total Weekly Hours of Coverage', 'internal' => $totalWeeklyHours, 'vendor' => $totalWeeklyHours],
            ['description' => 'Total Monthly Hours of Coverage', 'internal' => $totalMonthlyHours, 'vendor' => $totalMonthlyHours],
            ['description' => 'Total Annual Hours of Coverage', 'internal' => $totalAnnualHours, 'vendor' => $totalAnnualHours],
            ['description' => 'Total Workforce Required for Coverage', 'internal' => (float) $ftes, 'vendor' => (float) $ftes],
            ['description' => 'Total Weekly Cost', 'internal' => $totalWeeklyInt, 'vendor' => $totalWeeklyVend],
            ['description' => 'Total Monthly Cost', 'internal' => $totalMonthlyInt, 'vendor' => $totalMonthlyVend],
            ['description' => 'Total Annual Cost', 'internal' => $totalAnnualInt, 'vendor' => $totalAnnualVend],
        ];

        return [
            'preparedFor' => (string) Arr::get($reportMeta, 'preparedFor', ''),
            'reportDate' => (string) Arr::get($reportMeta, 'reportDate', ''),
            'rows' => $rows,
            'footerRows' => [
                ['description' => 'Operational Capital Recovered', 'internal' => null, 'vendor' => $operationalCapital, 'emphasis' => true],
                ['description' => 'Operational Capital Recovered (%)', 'internal' => null, 'vendor' => $operationalCapitalPct, 'emphasis' => true, 'isPercent' => true],
                ['description' => 'Payback & Recovery Period', 'internal' => null, 'vendor' => $paybackMonths, 'emphasis' => true, 'suffix' => ' months'],
            ],
            'coverageStatement' => (string) Arr::get($reportMeta, 'coverageStatement', $this->defaultCoverageStatement()),
            'hoursPerProfessionalAnnual' => $hoursPerProfessional,
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $cfo
     * @param  array<string, mixed>  $appraisal
     * @return array<string, mixed>
     */
    private function computePriceRealism(array $meta, float $hours, array $cfo): array
    {
        $pr = (array) Arr::get($meta, 'priceRealism', []);
        $grandHourly = (float) ($cfo['grandTotal']['hourly'] ?? 43.67);
        $grandAnnual = (float) ($cfo['grandTotal']['annual'] ?? ($grandHourly * $hours));

        $vehicleHr = (float) Arr::get($pr, 'vehiclePerHour', 1.31);
        $uniformHr = (float) Arr::get($pr, 'uniformPerHour', 1.13);
        $trainingHr = (float) Arr::get($pr, 'trainingProgramPerHour', 3.47);

        $appraisalMeta = (array) Arr::get($meta, 'appraisal', []);
        $vendorBenchHr = (float) Arr::get($pr, 'vendorTcoBenchmarkHourly',
            (float) Arr::get($appraisalMeta, 'vendorTcoHourly', 54.78));

        $govBenchHr = (float) Arr::get($pr, 'governmentBenchmarkHourly',
            (float) Arr::get($appraisalMeta, 'governmentShouldCostHourly', 78.25));
        $reservedGovHr = (float) Arr::get($pr, 'reservedGovernmentRateHourly', 38.34);

        $toAnnual = fn (float $h) => round($h * $hours, 2);

        $premiumHr = round($vendorBenchHr - $grandHourly, 2);
        $premiumAnnual = round($vendorBenchHr * $hours - $grandAnnual, 2);

        return [
            'moduleFeeds' => [
                ['label' => 'Vehicle Cost per Labor Hour', 'hourly' => $vehicleHr, 'annual' => $toAnnual($vehicleHr)],
                ['label' => 'Uniform & Equipment Cost per Labor Hour', 'hourly' => $uniformHr, 'annual' => $toAnnual($uniformHr)],
                ['label' => 'Training Program Cost per Labor Hour', 'hourly' => $trainingHr, 'annual' => $toAnnual($trainingHr)],
            ],
            'leftSummary' => [
                ['label' => 'Total Bill Rate', 'hourly' => round($grandHourly, 2), 'annual' => round($grandAnnual, 2), 'strong' => true, 'rateClass' => 'text-primary'],
                ['label' => 'Vendor TCO Benchmark', 'hourly' => round($vendorBenchHr, 2), 'annual' => $toAnnual($vendorBenchHr), 'annualClass' => 'text-success'],
                ['label' => 'Vendor Premium / (Discount) vs GASQ', 'hourly' => round($premiumHr, 2), 'annual' => $premiumAnnual, 'annualClass' => 'text-success'],
            ],
            'benchmark' => [
                ['label' => 'Government Should-Cost TCO Benchmark', 'hourly' => round($govBenchHr, 2), 'annual' => $toAnnual($govBenchHr), 'annualClass' => 'text-success'],
                ['label' => 'Reserved Should-Cost Government Rate', 'hourly' => round($reservedGovHr, 2), 'annual' => $toAnnual($reservedGovHr), 'rateClass' => 'text-danger', 'annualClass' => 'text-danger'],
                ['label' => 'Vendor Total Cost of Ownership Rate', 'hourly' => round($vendorBenchHr, 2), 'annual' => $toAnnual($vendorBenchHr), 'strong' => true, 'rateClass' => 'text-primary', 'annualClass' => 'text-success'],
            ],
        ];
    }

    private function defaultCoverageStatement(): string
    {
        return 'All price calculations include the full cost of workforce staffing and support services, including but not limited to: livable base wages, employer-paid payroll taxes (FICA, FUTA, SUTA), workers\' compensation, general liability insurance, unemployment insurance, paid time off, healthcare and fringe benefits, uniforms and equipment, onboarding and training, site supervision, quality assurance oversight, management and administrative support, 24/7 dispatch capability, compliance with local, state, and federal labor laws, and all service-level guarantees (including open post protection, vendor replacement, and price lock guarantees) unless otherwise specified.';
    }
}
