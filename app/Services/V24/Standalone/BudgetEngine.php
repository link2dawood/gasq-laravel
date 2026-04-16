<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class BudgetEngine
{
    /**
     * Mirror the budget calculator UI so saved state / PDF exports include detailed line items.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);
        $governmentShouldCostHourly = (float) Arr::get(
            $meta,
            'governmentShouldCostHourly',
            (float) config('budget_calculator.default_government_should_cost_hourly', 86.75)
        );
        $annualBillableHours = (float) Arr::get(
            $meta,
            'annualBillableHours',
            (float) config('budget_calculator.default_annual_billable_hours', 8736)
        );
        $hasBenchmarkInputs = array_key_exists('governmentShouldCostHourly', $meta) || array_key_exists('annualBillableHours', $meta);
        $annual = $hasBenchmarkInputs
            ? round($governmentShouldCostHourly * $annualBillableHours, 2)
            : (float) Arr::get($meta, 'annualBudget', round($governmentShouldCostHourly * $annualBillableHours, 2));
        $allocations = (array) Arr::get($meta, 'allocations', []);
        $groups = (array) config('budget_calculator.groups', []);
        $modelAnnualTotal = $this->modelAnnualTotal($groups);

        $groupPercents = [];
        $groupAmounts = [];
        $allocationPercents = [];
        $allocationAmounts = [];
        $laborPct = 0.0;

        foreach ($groups as $group) {
            $groupKey = (string) ($group['key'] ?? 'group');
            $groupPct = 0.0;

            foreach ((array) ($group['items'] ?? []) as $item) {
                $itemKey = (string) ($item['key'] ?? 'item');
                $defaultPct = $modelAnnualTotal > 0
                    ? (((float) ($item['annual'] ?? 0.0)) / $modelAnnualTotal) * 100
                    : 0.0;
                $pct = $this->normalizePercent((float) Arr::get($allocations, $itemKey, $defaultPct));

                $allocationPercents[$itemKey] = round($pct / 100, 4);
                $allocationAmounts[$itemKey] = $this->allocationAmount($annual, $pct);
                $groupPct += $pct;
            }

            $groupPercents[$groupKey] = round($groupPct / 100, 4);
            $groupAmounts[$groupKey] = $this->allocationAmount($annual, $groupPct);

            if ((bool) ($group['benchmarked'] ?? false)) {
                $laborPct += $groupPct;
            }
        }

        $monthly = $annual / 12;
        $weekly = $annual / 52;
        $daily = $annual / 365;
        $totalAllocatedPct = array_sum($groupPercents);
        $laborStatus = $laborPct < 55
            ? 'Below benchmark'
            : ($laborPct > 70 ? 'Above benchmark' : 'Within benchmark');

        return [
            'governmentShouldCostHourly' => round($governmentShouldCostHourly, 2),
            'annualBillableHours' => round($annualBillableHours, 2),
            'annualBudget' => round($annual, 2),
            'monthlyBudget' => round($monthly, 2),
            'weeklyBudget' => round($weekly, 2),
            'dailyBudget' => round($daily, 2),
            'totalAllocatedPct' => round($totalAllocatedPct, 4),
            'laborAllocationPct' => round($laborPct / 100, 4),
            'laborAllocationAmount' => $this->allocationAmount($annual, $laborPct),
            'laborBenchmarkLowPct' => 0.55,
            'laborBenchmarkHighPct' => 0.70,
            'laborStatus' => $laborStatus,
            'groupPercents' => $groupPercents,
            'groupAmounts' => $groupAmounts,
            'allocationPercents' => $allocationPercents,
            'allocationAmounts' => $allocationAmounts,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $groups
     */
    private function modelAnnualTotal(array $groups): float
    {
        $total = 0.0;

        foreach ($groups as $group) {
            foreach ((array) ($group['items'] ?? []) as $item) {
                $total += (float) ($item['annual'] ?? 0.0);
            }
        }

        return $total;
    }

    private function allocationAmount(float $annual, float $pct): float
    {
        return round($annual * ($pct / 100), 2);
    }

    private function normalizePercent(float $value): float
    {
        if ($value > 0 && $value <= 1) {
            return $value * 100;
        }

        return $value;
    }
}
