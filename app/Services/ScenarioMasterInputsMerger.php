<?php

namespace App\Services;

use App\Models\User;

class ScenarioMasterInputsMerger
{
    public function __construct(
        private MasterInputsService $masterInputs,
    ) {}

    /**
     * Merge per-user Master Inputs into a scenario array under scenario.meta.inputs.
     * Scenario-provided inputs win over master defaults.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function merge(User $user, array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);
        $scenarioInputs = (array) ($meta['inputs'] ?? []);

        $meta['inputs'] = array_replace($this->masterInputs->forUser($user), $scenarioInputs);
        $scenario['meta'] = $meta;

        return $scenario;
    }
}

