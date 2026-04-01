<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class BuyerFitIndexEngine
{
    /**
     * Score 0–100 based on procurement readiness signals.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);

        $budgetApproved = (bool) Arr::get($m, 'budgetApproved', false);
        $isDecisionMaker = (bool) Arr::get($m, 'isDecisionMaker', false);
        $timelineDays = max(0, (int) Arr::get($m, 'timelineDays', 60));
        $sites = max(1, (int) Arr::get($m, 'sites', 1));
        $guards = max(1, (int) Arr::get($m, 'guards', 1));
        $hasIncidents = (bool) Arr::get($m, 'hasIncidents', false);
        $needs24x7 = (bool) Arr::get($m, 'needs24x7', false);

        $score = 0;
        $score += $budgetApproved ? 25 : 0;
        $score += $isDecisionMaker ? 20 : 0;

        // timeline: <=30 best, 31-90 medium, >90 low
        $score += $timelineDays <= 30 ? 20 : ($timelineDays <= 90 ? 12 : 5);

        // scope: more sites/guards means higher fit
        $scopePoints = min(20, (int) round((log($sites + 1, 2) + log($guards + 1, 2)) * 5));
        $score += $scopePoints;

        $score += $hasIncidents ? 10 : 0;
        $score += $needs24x7 ? 5 : 0;

        $score = max(0, min(100, $score));
        $band = $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Good' : ($score >= 40 ? 'Fair' : 'Low'));

        return [
            'inputs' => [
                'budgetApproved' => $budgetApproved,
                'isDecisionMaker' => $isDecisionMaker,
                'timelineDays' => $timelineDays,
                'sites' => $sites,
                'guards' => $guards,
                'hasIncidents' => $hasIncidents,
                'needs24x7' => $needs24x7,
            ],
            'result' => [
                'score' => $score,
                'band' => $band,
            ],
            'reference' => 'standalone:buyer-fit-index',
        ];
    }
}

