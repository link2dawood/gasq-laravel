<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

/**
 * React parity for `/global-security-pricing` (bundle symbol `_De`).
 *
 * Bill-rate row math and summary `fe` match the three `x.useMemo` blocks in the React bundle.
 * The `totals` object nested under bill-rate analysis uses the same hard-coded demo figures as React
 * (only `weeklyHours` and `annualHours` are summed from rows).
 *
 * @see public/react-ui/assets/index-Bx21dMi4.js
 */
class GlobalSecurityPricingEngine
{
    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $posts = array_values((array) Arr::get($scenario, 'posts', Arr::get($scenario, 'categories', [])));

        $equipment = (array) Arr::get($scenario, 'equipment', []);
        $recruiting = (array) Arr::get($scenario, 'recruiting', []);
        $vehicleCosts = (array) Arr::get($scenario, 'vehicleCosts', []);
        $benefits = (array) Arr::get($scenario, 'benefits', []);

        $useFixedHeadcount = (bool) Arr::get($scenario, 'useFixedHeadcount', true);
        $fixedHeadcount = (float) Arr::get($scenario, 'fixedHeadcount', 262);

        $holidaysPerYear = (float) Arr::get($scenario, 'holidaysPerYear', 88);
        $vacationWeeks = (float) Arr::get($scenario, 'vacationWeeks', 1);

        $hwPerHour = (float) Arr::get($benefits, 'hwPerHour', 4.93);

        $totalWeeklyHours = 0.0;
        foreach ($posts as $p) {
            $totalWeeklyHours += (float) ($p['weeklyHours'] ?? 0);
        }

        $totalAnnualHours = $totalWeeklyHours * 52;
        $systemGeneratedHeadcount = $totalWeeklyHours > 0 ? (int) ceil($totalWeeklyHours / 28) : 0;
        $headcountUsed = $useFixedHeadcount ? $fixedHeadcount : (float) $systemGeneratedHeadcount;

        $totalDirectLabor = 0.0;
        $totalBilling = 0.0;
        foreach ($posts as $p) {
            $wh = (float) ($p['weeklyHours'] ?? 0);
            $pay = (float) ($p['stPayRate'] ?? 0);
            $bill = (float) ($p['stBillRate'] ?? 0);
            $totalDirectLabor += $wh * 52 * $pay;
            $totalBilling += $wh * 52 * $bill;
        }

        $totalEquipmentCost = $this->sumNumericValues($equipment);
        $totalRecruitingCost = $this->sumNumericValues($recruiting);
        $totalVehicleCost = $this->sumNumericValues($vehicleCosts);

        $totalBenefitsCost = $hwPerHour * $totalAnnualHours;

        $annualContributoryProfit = $totalBilling - $totalDirectLabor - $totalEquipmentCost - $totalRecruitingCost - $totalVehicleCost - $totalBenefitsCost;

        $effDirectLaborPercent = $totalBilling > 0 ? ($totalDirectLabor / $totalBilling) * 100 : 0.0;
        $contribProfitPercent = $totalBilling > 0 ? ($annualContributoryProfit / $totalBilling) * 100 : 0.0;
        $perHourProfit = $totalAnnualHours > 0 ? $annualContributoryProfit / $totalAnnualHours : 0.0;

        $fe = [
            'totalWeeklyHours' => round($totalWeeklyHours, 2),
            'totalAnnualHours' => round($totalAnnualHours, 2),
            'systemGeneratedHeadcount' => $systemGeneratedHeadcount,
            'headcountUsed' => round($headcountUsed, 2),
            'totalDirectLabor' => round($totalDirectLabor, 2),
            'totalBilling' => round($totalBilling, 2),
            'annualContributoryProfit' => round($annualContributoryProfit, 2),
            'effDirectLaborPercent' => round($effDirectLaborPercent, 4),
            'contribProfitPercent' => round($contribProfitPercent, 4),
            'perHourProfit' => round($perHourProfit, 4),
            'totalEquipmentCost' => round($totalEquipmentCost, 2),
            'totalRecruitingCost' => round($totalRecruitingCost, 2),
            'totalVehicleCost' => round($totalVehicleCost, 2),
            'totalBenefitsCost' => round($totalBenefitsCost, 2),
        ];

        $analysisData = [];
        $sumWeekly = 0.0;
        $sumAnnualHoursRows = 0.0;

        foreach ($posts as $p) {
            $row = $this->computeBillRateRow($p, $holidaysPerYear, $vacationWeeks);
            $analysisData[] = $row;
            $sumWeekly += (float) $row['weeklyHours'];
            $sumAnnualHoursRows += (float) $row['annualHours'];
        }

        $totals = [
            'weeklyHours' => round($sumWeekly, 2),
            'annualHours' => round($sumAnnualHoursRows, 2),
            'revenueSTTime' => 59.0,
            'revenueOvertimePremium' => 0.0,
            'revenueHolidayPremium' => 0.17,
            'totalRevenue' => 59.17,
            'wages' => 33.0,
            'totalWages' => 41.05,
            'ficaTax' => 3.14,
            'federalUnemployment' => 0.33,
            'stateUnemployment' => 2.36,
            'workersComp' => 1.23,
            'liabilityInsurance' => 1.23,
            'disability' => 1.23,
            'fidelity' => 1.44,
            'drugTesting' => 0.05,
            'backgroundInvest' => 0.04,
            'uniformsCost' => 1.08,
            'equipmentCost' => 0.39,
            'trainingEquipment' => 2.02,
            'totalDirectAndVariableExp' => 55.59,
            'operatingProfit' => 3.57,
            'overheadAllocationAmt' => 0.5,
            'contributoryProfit' => 3.07,
            'contributoryProfitPercent' => 5.2,
            'effectiveDirectLaborPercent' => 69.4,
        ];

        $billComponents = $this->staticBillComponents();

        return [
            'summary' => [
                'county' => Arr::get($scenario, 'county', ''),
                'contractType' => Arr::get($scenario, 'contractType', ''),
            ],
            'fe' => $fe,
            'billRateAnalysis' => [
                'analysisData' => $analysisData,
                'totals' => $totals,
            ],
            'billComponents' => $billComponents,
            'reference' => 'react_bundle:_De',
        ];
    }

    /**
     * @param  array<string, mixed>  $post
     * @return array<string, float|int|string>
     */
    private function computeBillRateRow(array $post, float $h, float $b): array
    {
        $gt = (float) ($post['weeklyHours'] ?? 0);
        $Ge = $gt * 52;
        $at = (float) ($post['stPayRate'] ?? 0);
        $_e = (float) ($post['stBillRate'] ?? 0);
        $otBill = $_e * 1.5;
        $holBill = $_e * 1.5;
        $ae = $_e;
        $rt = 0.0;
        $Ie = $Ge > 0 ? $h * 8 * ($holBill - $_e) / $Ge : 0.0;
        $xe = $ae + $rt + $Ie;
        $Ye = $at;
        $Ve = 0.0;
        $jt = $Ye * ($b / 52);
        $De = $Ye * 0.065;
        $kt = $Ge > 0 ? $h * 8 * ($at * 0.5) / $Ge : 0.0;
        $dt = 4.93;
        $Dt = $Ye + $Ve + $jt + $De + $kt + $dt;
        $Lt = $Dt * 0.0765;
        $zt = $Dt * 0.008;
        $Nt = $Dt * 0.058;
        $ir = $Dt * 0.03;
        $St = $Dt * 0.03;
        $sr = $Dt * 0.03;
        $hr = $Dt * 0.035;
        $fr = 0.05;
        $or = 0.04;
        $Qt = $Ye * 0.033;
        $vt = 0.39;
        $tr = $Ye * 0.061;
        $wt = $Dt + $Lt + $zt + $Nt + $ir + $St + $sr + $hr + $fr + $or + $Qt + $vt + $tr;
        $gr = $xe - $wt;
        $overheadAlloc = 0.5;
        $Wr = $gr - $overheadAlloc;
        $Qr = $xe > 0 ? ($Wr / $xe) * 100 : 0.0;
        $cn = $xe > 0 ? ($Dt / $xe) * 100 : 0.0;

        return [
            'category' => (string) ($post['position'] ?? $post['postName'] ?? ''),
            'weeklyHours' => round($gt, 2),
            'annualHours' => round($Ge, 2),
            'stPayRate' => round($at, 4),
            'stBillRate' => round($_e, 4),
            'overtimeBillRate' => round($otBill, 4),
            'holidayBillRate' => round($holBill, 4),
            'stDirectLaborPercent' => $_e > 0 ? round(($at / $_e) * 100, 4) : 0.0,
            'revenueSTTime' => round($ae, 4),
            'revenueOvertimePremium' => round($rt, 4),
            'revenueHolidayPremium' => round($Ie, 4),
            'totalRevenue' => round($xe, 4),
            'wages' => round($Ye, 4),
            'overtimePremium' => round($Ve, 4),
            'vacationCost' => round($jt, 4),
            'trainingCost' => round($De, 4),
            'holidayPremium' => round($kt, 4),
            'otherUnbilledWages' => round($dt, 4),
            'totalWages' => round($Dt, 4),
            'effectiveDirectLaborPercent' => round($cn, 4),
            'ficaTax' => round($Lt, 4),
            'federalUnemployment' => round($zt, 4),
            'stateUnemployment' => round($Nt, 4),
            'workersComp' => round($ir, 4),
            'liabilityInsurance' => round($St, 4),
            'disability' => round($sr, 4),
            'fidelity' => round($hr, 4),
            'drugTesting' => round($fr, 4),
            'backgroundInvest' => round($or, 4),
            'uniformsCost' => round($Qt, 4),
            'equipmentCost' => round($vt, 4),
            'trainingEquipment' => round($tr, 4),
            'totalDirectAndVariableExp' => round($wt, 4),
            'operatingProfit' => round($gr, 4),
            'overheadAllocationAmt' => round($overheadAlloc, 4),
            'contributoryProfit' => round($Wr, 4),
            'contributoryProfitPercent' => round($Qr, 4),
        ];
    }

    /**
     * @param  array<string, mixed>  $assoc
     */
    private function sumNumericValues(array $assoc): float
    {
        $s = 0.0;
        foreach ($assoc as $v) {
            if (is_numeric($v)) {
                $s += (float) $v;
            }
        }

        return $s;
    }

    /**
     * @return array{components: array<int, array{name: string, value: float, color: string, percentage: float}>, totalBillRate: string}
     */
    private function staticBillComponents(): array
    {
        $components = [
            ['name' => 'Wages and Benefits', 'value' => 41.05, 'color' => '#3b82f6', 'percentage' => 69.4],
            ['name' => 'Taxes and Insurance', 'value' => 10.96, 'color' => '#84cc16', 'percentage' => 18.5],
            ['name' => 'Training Costs', 'value' => 2.02, 'color' => '#ef4444', 'percentage' => 3.4],
            ['name' => 'Recruiting, Screening and Drug Testing', 'value' => 0.09, 'color' => '#8b5cf6', 'percentage' => 0.2],
            ['name' => 'Uniforms and Equipment', 'value' => 1.47, 'color' => '#06b6d4', 'percentage' => 2.5],
            ['name' => 'Overhead', 'value' => 0.5, 'color' => '#f97316', 'percentage' => 0.8],
            ['name' => 'Profit', 'value' => 3.07, 'color' => '#a855f7', 'percentage' => 5.2],
        ];
        $sum = 0.0;
        foreach ($components as $c) {
            $sum += $c['value'];
        }

        return [
            'components' => $components,
            'totalBillRate' => number_format($sum, 2, '.', ''),
        ];
    }
}
