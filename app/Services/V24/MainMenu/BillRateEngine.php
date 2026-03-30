<?php

namespace App\Services\V24\MainMenu;

use Illuminate\Support\Arr;

class BillRateEngine
{
    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);

        $base = (float) (Arr::get($meta, 'basePayRate') ?? Arr::get($meta, 'basePay') ?? 0);
        $profitPct = (float) (Arr::get($meta, 'profitMarginPct') ?? Arr::get($meta, 'profit') ?? 15);

        // Mirrors demo math: costWithBenefits assumes 70% labor share.
        $costWithBenefits = $base > 0 ? $base / 0.70 : 0.0;
        $billRate = $costWithBenefits * (1 + ($profitPct / 100));
        $markup = $base > 0 ? (($billRate - $base) / $base) * 100 : 0.0;

        return [
            'basePayRate' => round($base, 2),
            'costWithBenefits' => round($costWithBenefits, 2),
            'finalBillRate' => round($billRate, 2),
            'markupPct' => round($markup, 1),
            'weeklyAt40' => round($billRate * 40, 2),
        ];
    }
}

