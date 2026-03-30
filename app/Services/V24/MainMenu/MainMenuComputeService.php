<?php

namespace App\Services\V24\MainMenu;

use App\Services\ScenarioService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class MainMenuComputeService
{
    public function __construct(
        private ScenarioService $scenarioService,
        private SecurityCostEngine $securityCost,
        private ManpowerEngine $manpower,
        private EconomicJustificationEngine $economicJustification,
        private BillRateEngine $billRate,
        private BillRateComponentsEngine $billRateComponents,
        private ContractSummaryEngine $contractSummary,
    ) {}

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $assumptions = (array) ($scenario['assumptions'] ?? []);
        $scope = (array) ($scenario['scope'] ?? []);
        $posts = (array) ($scenario['posts'] ?? []);
        $vehicle = (array) ($scenario['vehicle'] ?? []);
        $uniform = (array) ($scenario['uniform'] ?? []);
        $meta = (array) ($scenario['meta'] ?? []);

        if ($scope !== []) {
            $this->scenarioService->validateScope($scope);
        }

        $derivedCoverage = $scope !== []
            ? $this->scenarioService->deriveRequiredHours($posts, $scope, $assumptions)
            : [];

        $tabSecurity = $this->securityCost->compute($scenario);
        $tabManpower = $this->manpower->compute($scenario, $derivedCoverage);
        $tabEJ = $this->economicJustification->compute($scenario);
        $tabBillRate = $this->billRate->compute($scenario);
        $tabComponents = $this->billRateComponents->compute($scenario);
        $tabSummary = $this->contractSummary->compute($scenario, [
            'coverage' => $derivedCoverage,
            'securityCost' => $tabSecurity,
            'billRate' => $tabBillRate,
            'vehicle' => $vehicle,
            'uniform' => $uniform,
            'meta' => $meta,
        ]);

        $kpis = [
            'annualCoverageHours' => Arr::get($derivedCoverage, 'annualCoverageHours'),
            'annualLaborHours' => Arr::get($derivedCoverage, 'annualLaborHours'),
            'ftesRequiredAtPaidHoursBasis' => Arr::get($derivedCoverage, 'ftesRequiredAtPaidHoursBasis'),
        ];

        return [
            'kpis' => $kpis,
            'tabs' => [
                'securityCost' => $tabSecurity,
                'manpowerHours' => $tabManpower,
                'economicJustification' => $tabEJ,
                'billRate' => $tabBillRate,
                'billRateComponents' => $tabComponents,
                'contractSummary' => $tabSummary,
            ],
        ];
    }
}

