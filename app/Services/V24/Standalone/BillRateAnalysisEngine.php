<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class BillRateAnalysisEngine
{
    /**
     * Mirrors the demo math in `resources/views/calculators/bill-rate.blade.php` (quick + components).
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);

        $base = (float) Arr::get($m, 'quick.basePayRate', 18.0);
        $benefitsPct = (float) Arr::get($m, 'quick.benefitsPct', 20.0) / 100;
        $overheadPct = (float) Arr::get($m, 'quick.overheadPct', 35.0) / 100;
        $profitPct = (float) Arr::get($m, 'quick.profitPct', 15.0) / 100;

        $benefitsAmt = $base * $benefitsPct;
        $burdened = $base + $benefitsAmt;
        $overheadAmt = $burdened * $overheadPct;
        $withOverhead = $burdened + $overheadAmt;
        $billRate = $profitPct < 1 ? ($withOverhead / (1 - $profitPct)) : 0.0;
        $markup = $base > 0 ? (($billRate - $base) / $base) * 100 : 0.0;

        $components = (array) Arr::get($m, 'components', []);
        $compDefs = [
            ['key' => 'wages', 'label' => 'Wages & Benefits', 'default' => 41.05],
            ['key' => 'taxes', 'label' => 'Taxes & Insurance', 'default' => 10.96],
            ['key' => 'training', 'label' => 'Training Costs', 'default' => 2.02],
            ['key' => 'recruiting', 'label' => 'Recruiting, Screening & Drug Testing', 'default' => 0.09],
            ['key' => 'uniforms', 'label' => 'Uniforms & Equipment', 'default' => 1.47],
            ['key' => 'overhead', 'label' => 'Overhead', 'default' => 0.50],
            ['key' => 'profit', 'label' => 'Profit', 'default' => 3.07],
        ];
        $total = 0.0;
        $outComps = [];
        foreach ($compDefs as $d) {
            $val = (float) ($components[$d['key']] ?? $d['default']);
            $total += $val;
            $outComps[] = ['key' => $d['key'], 'label' => $d['label'], 'value' => round($val, 2)];
        }
        $outComps = array_map(function (array $c) use ($total) {
            $pct = $total > 0 ? ($c['value'] / $total) * 100 : 0.0;
            $c['pct'] = round($pct, 2);
            return $c;
        }, $outComps);

        return [
            'quick' => [
                'basePayRate' => round($base, 2),
                'benefitsAmt' => round($benefitsAmt, 2),
                'burdenedCost' => round($burdened, 2),
                'overheadAmt' => round($overheadAmt, 2),
                'withOverhead' => round($withOverhead, 2),
                'billRate' => round($billRate, 2),
                'markupPct' => round($markup, 1),
                'weeklyAt40' => round($billRate * 40, 2),
            ],
            'components' => [
                'totalBillRate' => round($total, 2),
                'rows' => $outComps,
            ],
        ];
    }
}

