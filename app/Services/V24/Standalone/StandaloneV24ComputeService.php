<?php

namespace App\Services\V24\Standalone;

use Illuminate\Validation\ValidationException;

class StandaloneV24ComputeService
{
    public function __construct(
        private CostAnalysisEngine $costAnalysis,
        private ManpowerHoursEngine $manpowerHours,
        private BillRateAnalysisEngine $billRateAnalysis,
        private EconomicJustificationEngine $economicJustification,
        private HourlyPayEngine $hourlyPay,
        private BudgetEngine $budget,
        private MobilePatrolAnalysisEngine $mobilePatrolAnalysis,
        private GlobalSecurityPricingEngine $globalSecurityPricing,
        private WorkforceAppraisalReportEngine $workforceAppraisalReport,
        private MobilePatrolHitCalculatorEngine $mobilePatrolHitCalculator,
        private GasqTcoCalculatorEngine $gasqTcoCalculator,
        private AbsorbedRateCalculatorEngine $absorbedRateCalculator,
        private GovernmentContractCalculatorEngine $governmentContractCalculator,
        private KeepsDoorsOpenCalculatorEngine $keepsDoorsOpenCalculator,
        private UnarmedSecurityGuardServicesEngine $unarmedGuardServices,
        private BuyerFitIndexEngine $buyerFitIndex,
        private GasqDirectLaborBuildUpEngine $gasqDirectLaborBuildUp,
        private GasqAdditionalCostStackEngine $gasqAdditionalCostStack,
    ) {}

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(string $type, array $scenario): array
    {
        return match ($type) {
            'cost-analysis' => ['kpis' => $this->costAnalysis->compute($scenario)],
            'manpower-hours' => ['kpis' => $this->manpowerHours->compute($scenario)],
            'bill-rate-analysis' => ['kpis' => $this->billRateAnalysis->compute($scenario)],
            'economic-justification' => ['kpis' => $this->economicJustification->compute($scenario)],
            'hourly-pay-calculator' => ['kpis' => $this->hourlyPay->compute($scenario)],
            'budget-calculator' => ['kpis' => $this->budget->compute($scenario)],
            'mobile-patrol-analysis' => ['kpis' => $this->mobilePatrolAnalysis->compute($scenario)],
            'global-security-pricing' => ['kpis' => $this->globalSecurityPricing->compute($scenario)],
            'workforce-appraisal-report' => ['kpis' => $this->workforceAppraisalReport->compute($scenario)],
            'mobile-patrol-hit-calculator' => ['kpis' => $this->mobilePatrolHitCalculator->compute($scenario)],
            'gasq-tco-calculator' => ['kpis' => $this->gasqTcoCalculator->compute($scenario)],
            'absorbed-rate-calculator' => ['kpis' => $this->absorbedRateCalculator->compute($scenario)],
            'government-contract-calculator' => ['kpis' => $this->governmentContractCalculator->compute($scenario)],
            'keeps-doors-open-calculator' => ['kpis' => $this->keepsDoorsOpenCalculator->compute($scenario)],
            'unarmed-security-guard-services' => ['kpis' => $this->unarmedGuardServices->compute($scenario)],
            'buyer-fit-index' => ['kpis' => $this->buyerFitIndex->compute($scenario)],
            'gasq-direct-labor-build-up' => ['kpis' => $this->gasqDirectLaborBuildUp->compute($scenario)],
            'gasq-additional-cost-stack' => ['kpis' => $this->gasqAdditionalCostStack->compute($scenario)],
            default => throw ValidationException::withMessages([
                'type' => ['Unknown calculator type.'],
            ]),
        };
    }
}

