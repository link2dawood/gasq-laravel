<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class GovernmentContractCalculatorEngine
{
    /**
     * Government contract estimator (simplified, V24-style):
     * pay base + locality + shift diff + H&W cash, then add burdens/ops/overhead and profit.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);

        $baseWage = max(0.0, (float) Arr::get($m, 'baseWage', 20.76));
        $localityPct = (float) Arr::get($m, 'localityPayPct', 0.0) / 100.0;
        $shiftPct = (float) Arr::get($m, 'shiftDifferentialPct', 0.0) / 100.0;
        $hwCash = max(0.0, (float) Arr::get($m, 'healthWelfareCashPerHour', 4.22));

        $burdenPct = max(0.0, (float) Arr::get($m, 'employerBurdenPct', 18.15)) / 100.0;
        $opsPct = max(0.0, (float) Arr::get($m, 'opsSupportPct', 13.05)) / 100.0;
        $overheadPct = max(0.0, (float) Arr::get($m, 'overheadPct', 17.23)) / 100.0;
        $profitPct = max(0.0, min(0.9, (float) Arr::get($m, 'profitPct', 6.89) / 100.0));

        $annualHours = max(1.0, (float) Arr::get($m, 'annualHours', 21322));

        $locality = $baseWage * $localityPct;
        $shift = ($baseWage + $locality) * $shiftPct;
        $direct = $baseWage + $locality + $shift + $hwCash;

        $burden = $direct * $burdenPct;
        $ops = $direct * $opsPct;
        $overhead = $direct * $overheadPct;
        $cost = $direct + $burden + $ops + $overhead;
        $bill = $cost / (1 - $profitPct);

        return [
            'inputs' => [
                'baseWage' => round($baseWage, 2),
                'localityPayPct' => round($localityPct * 100.0, 2),
                'shiftDifferentialPct' => round($shiftPct * 100.0, 2),
                'healthWelfareCashPerHour' => round($hwCash, 2),
                'employerBurdenPct' => round($burdenPct * 100.0, 2),
                'opsSupportPct' => round($opsPct * 100.0, 2),
                'overheadPct' => round($overheadPct * 100.0, 2),
                'profitPct' => round($profitPct * 100.0, 2),
                'annualHours' => round($annualHours, 2),
            ],
            'breakdown' => [
                'directHourly' => round($direct, 2),
                'burdenHourly' => round($burden, 2),
                'opsHourly' => round($ops, 2),
                'overheadHourly' => round($overhead, 2),
                'costHourly' => round($cost, 2),
                'billRateHourly' => round($bill, 2),
                'annualCost' => round($cost * $annualHours, 2),
                'annualBillTotal' => round($bill * $annualHours, 2),
            ],
            'reference' => 'standalone:government-contract-calculator',
        ];
    }
}

