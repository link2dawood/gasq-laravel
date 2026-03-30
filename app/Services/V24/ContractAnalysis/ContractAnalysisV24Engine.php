<?php

namespace App\Services\V24\ContractAnalysis;

use Illuminate\Support\Arr;

class ContractAnalysisV24Engine
{
    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        // NOTE: Mirrors current UI math with rows. Replace with V24 workbook logic once mapped.
        $categories = (array) ($scenario['categories'] ?? []);

        $totalHrs = 0.0;
        $totalOt = 0.0;
        $totalRev = 0.0;
        $totalPay = 0.0;

        $rowsOut = [];
        foreach ($categories as $row) {
            $hrs = (float) (Arr::get($row, 'weeklyHours') ?? 0);
            $pay = (float) (Arr::get($row, 'payRate') ?? 0);
            $bill = (float) (Arr::get($row, 'billRate') ?? 0);
            $ot = (float) (Arr::get($row, 'otHours') ?? 0);

            $otPayRate = $pay * 1.5;
            $otBillRate = $bill * 1.5;
            $revW = ($hrs * $bill) + ($ot * $otBillRate);
            $payW = ($hrs * $pay) + ($ot * $otPayRate);

            $rowsOut[] = [
                'category' => (string) (Arr::get($row, 'category') ?? ''),
                'armed' => (bool) (Arr::get($row, 'armed') ?? false),
                'weeklyHours' => $hrs,
                'payRate' => $pay,
                'billRate' => $bill,
                'otHours' => $ot,
                'weeklyRevenue' => round($revW, 2),
                'weeklyPayCost' => round($payW, 2),
            ];

            $totalHrs += $hrs;
            $totalOt += $ot;
            $totalRev += $revW;
            $totalPay += $payW;
        }

        $annualHrs = $totalHrs * 52;
        $annualRev = $totalRev * 52;
        $annualPay = $totalPay * 52;
        $gm = $annualRev - $annualPay;

        $avgBill = $totalHrs > 0 ? $totalRev / $totalHrs : 0.0;
        $avgPay = $totalHrs > 0 ? $totalPay / $totalHrs : 0.0;
        $gphr = $avgBill - $avgPay;
        $dlr = $avgBill > 0 ? ($avgPay / $avgBill) * 100 : 0.0;

        return [
            'rows' => $rowsOut,
            'footers' => [
                'totalWeeklyHours' => round($totalHrs, 1),
                'totalWeeklyOtHours' => round($totalOt, 1),
                'totalWeeklyRevenue' => round($totalRev, 2),
                'totalWeeklyPayCost' => round($totalPay, 2),
            ],
            'perHour' => [
                'avgBillRateWeighted' => round($avgBill, 2),
                'avgPayRateWeighted' => round($avgPay, 2),
                'grossMarginPerHour' => round($gphr, 2),
                'directLaborRatioPct' => round($dlr, 1),
                'annualHours' => round($annualHrs, 1),
                'annualRevenue' => round($annualRev, 2),
                'annualPayCost' => round($annualPay, 2),
                'annualGrossMargin' => round($gm, 2),
            ],
            'summary' => [
                'annualHours' => round($annualHrs, 1),
                'annualRevenue' => round($annualRev, 2),
                'annualPayCost' => round($annualPay, 2),
                'grossMargin' => round($gm, 2),
            ],
        ];
    }
}

