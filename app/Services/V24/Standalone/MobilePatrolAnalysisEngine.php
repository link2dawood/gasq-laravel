<?php

namespace App\Services\V24\Standalone;

/**
 * React parity for `/mobile-patrol-analysis` (bundle symbol `bDe`).
 *
 * Labor rates are hard-coded in the React UI: regular 25, OT 37.5 ($/hr).
 *
 * @see public/react-ui/assets/index-Bx21dMi4.js
 */
class MobilePatrolAnalysisEngine
{
    private const REGULAR_HOURLY_USD = 25.0;

    private const OVERTIME_HOURLY_USD = 37.5;

    private const MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $vehicles = array_values((array) ($scenario['vehicles'] ?? []));

        $fleet = [];
        $summaryAnnualTotal = 0.0;
        $monthlyOperatingTotals = array_fill(0, 12, 0.0);

        foreach ($vehicles as $v) {
            if (! ($v['active'] ?? true)) {
                continue;
            }
            $name = (string) ($v['name'] ?? 'Vehicle');
            $monthlyData = array_values((array) ($v['monthlyData'] ?? []));
            while (count($monthlyData) < 12) {
                $monthlyData[] = [];
            }

            $vehicleAnnual = 0.0;
            $totalHits = 0;
            $totalMiles = 0.0;
            $cpsNumerator = 0.0;
            $cpsDenomHits = 0;

            $monthlyCostPerStop = [];

            for ($m = 0; $m < 12; $m++) {
                $cell = is_array($monthlyData[$m] ?? null) ? $monthlyData[$m] : [];
                $cpm = (float) ($cell['costPerMile'] ?? 0);
                $miles = (float) ($cell['milesDriven'] ?? 0);
                $hits = (float) ($cell['hitsPerMonth'] ?? 0);
                $reg = (float) ($cell['regularHours'] ?? 0);
                $ot = (float) ($cell['overtimeHours'] ?? 0);
                $equip = (float) ($cell['equipmentCost'] ?? 0);

                $mileEquip = $cpm * $miles + $equip;
                $labor = $reg * self::REGULAR_HOURLY_USD + $ot * self::OVERTIME_HOURLY_USD;
                $monthTotal = $mileEquip + $labor;
                $vehicleAnnual += $monthTotal;

                $monthlyOperatingTotals[$m] += $mileEquip;

                if ($hits > 0) {
                    $cpsNumerator += $mileEquip + $labor;
                    $cpsDenomHits += $hits;
                }
                $monthlyCostPerStop[$m] = $hits > 0 ? ($mileEquip + $labor) / $hits : 0.0;

                $totalHits += (int) $hits;
                $totalMiles += $miles;
            }

            $avgCostPerStop = $cpsDenomHits > 0 ? $cpsNumerator / $cpsDenomHits : 0.0;
            $efficiency = $totalHits > 0 ? $totalMiles / $totalHits : 0.0;

            $summaryAnnualTotal += $vehicleAnnual;

            $fleet[] = [
                'name' => $name,
                'totalHits' => $totalHits,
                'totalMiles' => (int) round($totalMiles),
                'avgCostPerStop' => round($avgCostPerStop, 2),
                'efficiency' => round($efficiency, 4),
                'annualTotal' => round($vehicleAnnual, 2),
                'monthlyCostPerStop' => array_map(fn (float $x) => round($x, 4), $monthlyCostPerStop),
            ];
        }

        $monthlyChartRows = [];
        for ($m = 0; $m < 12; $m++) {
            $row = ['month' => self::MONTH_LABELS[$m]];
            $rowSum = 0.0;
            foreach ($vehicles as $v) {
                if (! ($v['active'] ?? true)) {
                    continue;
                }
                $vName = (string) ($v['name'] ?? 'Vehicle');
                $cost = $this->operatingOnlyForMonth($v, $m);
                $row[$vName] = $cost;
                $rowSum += $cost;
            }
            $row['totalOperating'] = round($rowSum, 2);
            $monthlyChartRows[] = $row;
        }

        return [
            'summaryAnnualTotal' => round($summaryAnnualTotal, 2),
            'monthlyOperatingTotals' => array_map(fn (float $x) => round($x, 2), $monthlyOperatingTotals),
            'monthlyChartRows' => $monthlyChartRows,
            'fleet' => $fleet,
            'laborRates' => [
                'regularHourlyUsd' => self::REGULAR_HOURLY_USD,
                'overtimeHourlyUsd' => self::OVERTIME_HOURLY_USD,
            ],
            'reference' => 'react_bundle:bDe',
        ];
    }

    /**
     * Per-month operating cost without mileage labor (matches React `C` memo).
     *
     * @param  array<string, mixed>  $vehicle
     */
    private function operatingOnlyForMonth(array $vehicle, int $monthIndex): float
    {
        $monthlyData = array_values((array) ($vehicle['monthlyData'] ?? []));
        $cell = is_array($monthlyData[$monthIndex] ?? null) ? $monthlyData[$monthIndex] : [];
        $cpm = (float) ($cell['costPerMile'] ?? 0);
        $miles = (float) ($cell['milesDriven'] ?? 0);
        $equip = (float) ($cell['equipmentCost'] ?? 0);

        return round($cpm * $miles + $equip, 2);
    }
}
