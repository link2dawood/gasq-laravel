<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class BudgetEngine
{
    /**
     * Basic mirror of the current UI (placeholder until workbook mapping is finalized).
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);
        $annual = (float) Arr::get($m, 'annualBudget', 180000);
        $monthly = $annual / 12;
        $weekly = $annual / 52;

        return [
            'annualBudget' => round($annual, 2),
            'monthlyBudget' => round($monthly, 2),
            'weeklyBudget' => round($weekly, 2),
        ];
    }
}

