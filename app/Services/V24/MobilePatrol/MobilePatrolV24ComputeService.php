<?php

namespace App\Services\V24\MobilePatrol;

class MobilePatrolV24ComputeService
{
    public function __construct(
        private MobilePatrolV24Engine $engine
    ) {}

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        return [
            'kpis' => $this->engine->compute($scenario),
        ];
    }
}

