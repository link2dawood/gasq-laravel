<?php

namespace App\Services\V24\SecurityBilling;

class SecurityBillingV24ComputeService
{
    public function __construct(
        private SecurityBillingV24Engine $engine
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

