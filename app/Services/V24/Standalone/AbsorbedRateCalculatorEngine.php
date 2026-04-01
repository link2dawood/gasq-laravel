<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class AbsorbedRateCalculatorEngine
{
    /**
     * Simple absorbed-rate model:
     * - burdened = basePay * (1 + benefitsPct)
     * - absorbed = burdened * (1 + opsPct + overheadPct) / (1 - profitPct)
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);

        $basePay = max(0.0, (float) Arr::get($m, 'basePayRate', 18.0));
        $benefitsPct = max(0.0, (float) Arr::get($m, 'benefitsPct', 20.0)) / 100.0;
        $opsPct = max(0.0, (float) Arr::get($m, 'opsPct', 10.0)) / 100.0;
        $overheadPct = max(0.0, (float) Arr::get($m, 'overheadPct', 35.0)) / 100.0;
        $profitPct = max(0.0, min(0.9, (float) Arr::get($m, 'profitPct', 15.0) / 100.0));
        $annualHours = max(1.0, (float) Arr::get($m, 'annualHours', 2080));

        $benefitsAmt = $basePay * $benefitsPct;
        $burdened = $basePay + $benefitsAmt;
        $opsAmt = $burdened * $opsPct;
        $overheadAmt = $burdened * $overheadPct;
        $costWithSupport = $burdened + $opsAmt + $overheadAmt;
        $absorbed = $costWithSupport / (1 - $profitPct);

        return [
            'inputs' => [
                'basePayRate' => round($basePay, 2),
                'benefitsPct' => round($benefitsPct * 100.0, 2),
                'opsPct' => round($opsPct * 100.0, 2),
                'overheadPct' => round($overheadPct * 100.0, 2),
                'profitPct' => round($profitPct * 100.0, 2),
                'annualHours' => round($annualHours, 2),
            ],
            'breakdown' => [
                'benefitsAmt' => round($benefitsAmt, 2),
                'burdenedCost' => round($burdened, 2),
                'opsAmt' => round($opsAmt, 2),
                'overheadAmt' => round($overheadAmt, 2),
                'costWithSupport' => round($costWithSupport, 2),
                'absorbedRate' => round($absorbed, 2),
                'annualAbsorbedTotal' => round($absorbed * $annualHours, 2),
            ],
            'reference' => 'standalone:absorbed-rate-calculator',
        ];
    }
}

