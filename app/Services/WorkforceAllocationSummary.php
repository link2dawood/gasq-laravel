<?php

namespace App\Services;

use App\Models\User;

class WorkforceAllocationSummary
{
    public function build(array $scenario, ?User $user = null): array
    {
        $estimate = app(CostToProtectEstimate::class)->build($scenario, $user);
        $allocations = (array) data_get($scenario, 'meta.allocations', []);
        $groups = (array) config('budget_calculator.groups', []);
        if (! collect($allocations)->contains(fn ($value) => is_numeric($value) && (float) $value != 0.0)) {
            $benchmarkTotal = collect($groups)->flatMap(fn ($group) => $group['items'] ?? [])->sum('annual');
            $allocations = [];
            if ($benchmarkTotal > 0) {
                foreach ($groups as $group) {
                    foreach ($group['items'] ?? [] as $item) {
                        if (!empty($item['key'])) {
                            $allocations[$item['key']] = (float) ($item['annual'] ?? 0) / $benchmarkTotal * 100;
                        }
                    }
                }
            }
        }
        $amounts = [];
        foreach ($groups as $group) {
            $percentage = 0;
            foreach ($group['items'] ?? [] as $item) {
                $value = $allocations[$item['key'] ?? ''] ?? 0;
                $percentage += is_numeric($value) ? (float) $value : 0;
            }
            $amounts[$group['key']] = $estimate['totalAnnualVendor'] * $percentage / 100;
        }

        return ['estimate' => $estimate, 'allocations' => $allocations, 'amounts' => $amounts, 'total' => array_sum($amounts)];
    }
}
