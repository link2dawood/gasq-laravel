<?php

namespace App\Services\V24\ContractAnalysis;

class ContractAnalysisV24ComputeService
{
    public function __construct(
        private ContractAnalysisV24Engine $engine
    ) {}

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        return $this->engine->compute($scenario);
    }
}

