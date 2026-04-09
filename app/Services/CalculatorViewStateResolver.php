<?php

namespace App\Services;

use App\Models\CalculatorState;
use App\Models\User;

class CalculatorViewStateResolver
{
    /**
     * @var array<string, string>
     */
    private const ROUTE_TO_TYPE = [
        'main-menu-calculator.index' => 'main-menu',
        'main-menu-calculator.post' => 'main-menu',
        'security-billing.index' => 'security-billing',
        'security-billing.post' => 'security-billing',
        'mobile-patrol-calculator' => 'mobile-patrol',
        'mobile-patrol-calculator.post' => 'mobile-patrol',
        'mobile-patrol-comparison' => 'mobile-patrol-comparison',
        'mobile-patrol-comparison.post' => 'mobile-patrol-comparison',
        'bill-rate-analysis.index' => 'bill-rate-analysis',
        'economic-justification.index' => 'economic-justification',
        'budget-calculator.index' => 'budget-calculator',
        'mobile-patrol-analysis.index' => 'mobile-patrol-analysis',
        'mobile-patrol-hit-calculator.index' => 'mobile-patrol-hit-calculator',
        'buyer-fit-index.index' => 'buyer-fit-index',
        'gasq-direct-labor-build-up.index' => 'gasq-direct-labor-build-up',
        'gasq-additional-cost-stack.index' => 'gasq-additional-cost-stack',
        'workforce-appraisal-report.index' => 'workforce-appraisal-report',
        'cfo-bill-rate-breakdown.index' => 'workforce-appraisal-report',
        'post-position-summary.index' => 'workforce-appraisal-report',
        'appraisal-comparison-summary.index' => 'workforce-appraisal-report',
        'gasq-tco-calculator.index' => 'gasq-tco-calculator',
        'government-contract-calculator.index' => 'government-contract-calculator',
        'backend.instant-estimator.index' => 'instant-estimator',
        'backend.instant-estimator.post' => 'instant-estimator',
        'backend.main-menu-calculator.index' => 'main-menu',
        'backend.main-menu-calculator.post' => 'main-menu',
        'backend.contract-analysis.index' => 'contract-analysis',
        'backend.contract-analysis.post' => 'contract-analysis',
        'backend.security-billing.index' => 'security-billing',
        'backend.mobile-patrol.calculator' => 'mobile-patrol',
        'backend.mobile-patrol.calculator.post' => 'mobile-patrol',
        'backend.mobile-patrol.comparison' => 'mobile-patrol-comparison',
        'backend.mobile-patrol.comparison.post' => 'mobile-patrol-comparison',
    ];

    public function resolveType(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        return self::ROUTE_TO_TYPE[$routeName] ?? null;
    }

    /**
     * @return array{type:?string,state:?CalculatorState,scenario:array<string,mixed>|null,result:array<string,mixed>|null}
     */
    public function resolve(?User $user, ?string $routeName): array
    {
        $type = $this->resolveType($routeName);

        if (! $user || ! $type) {
            return [
                'type' => $type,
                'state' => null,
                'scenario' => null,
                'result' => null,
            ];
        }

        /** @var CalculatorState|null $state */
        $state = $user->calculatorStates()
            ->where('calculator_type', $type)
            ->latest('last_ran_at')
            ->first();

        return [
            'type' => $type,
            'state' => $state,
            'scenario' => $state?->scenario,
            'result' => $state?->result,
        ];
    }
}
