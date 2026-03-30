<?php

namespace App\Services\V24\MainMenu;

use Illuminate\Support\Arr;

class BillRateComponentsEngine
{
    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);

        // Mirrors current demo: user enters per-component $/hr directly.
        $components = [
            ['key' => 'wages', 'label' => 'Wages & Benefits', 'value' => (float) Arr::get($meta, 'components.wages', 41.05)],
            ['key' => 'taxes', 'label' => 'Taxes & Insurance', 'value' => (float) Arr::get($meta, 'components.taxes', 10.96)],
            ['key' => 'training', 'label' => 'Training Costs', 'value' => (float) Arr::get($meta, 'components.training', 2.02)],
            ['key' => 'recruiting', 'label' => 'Recruiting & Screening', 'value' => (float) Arr::get($meta, 'components.recruiting', 0.09)],
            ['key' => 'uniforms', 'label' => 'Uniforms & Equipment', 'value' => (float) Arr::get($meta, 'components.uniforms', 1.47)],
            ['key' => 'overhead', 'label' => 'Overhead', 'value' => (float) Arr::get($meta, 'components.overhead', 0.50)],
            ['key' => 'profit', 'label' => 'Profit', 'value' => (float) Arr::get($meta, 'components.profit', 3.07)],
        ];

        $total = 0.0;
        foreach ($components as $c) {
            $total += (float) $c['value'];
        }

        $componentsOut = array_map(function (array $c) use ($total) {
            $val = (float) $c['value'];
            $pct = $total > 0 ? ($val / $total) * 100 : 0.0;
            return [
                'key' => $c['key'],
                'label' => $c['label'],
                'value' => round($val, 2),
                'pct' => round($pct, 2),
            ];
        }, $components);

        return [
            'totalBillRate' => round($total, 2),
            'components' => $componentsOut,
        ];
    }
}

