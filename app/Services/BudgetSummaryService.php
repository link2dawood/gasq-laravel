<?php

namespace App\Services;

use App\Models\User;
use App\Services\V24\Standalone\BudgetTcoEngine;
use Illuminate\Validation\ValidationException;

/** Read the user's saved budget inputs; never trust cached browser totals. */
class BudgetSummaryService
{
    public function forUser(User $user): ?array
    {
        $state = $user->calculatorStates()->where('calculator_type', 'budget-calculator')->first();

        if (! $state || ! is_array($state->scenario) || empty($state->scenario['meta'])) {
            return null;
        }

        return $this->fromScenario($state->scenario);
    }

    public function fromScenario(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);
        $scope = (array) ($meta['scope'] ?? []);
        $inputs = [
            'baselineWage' => max(0, (float) ($meta['baselineWage'] ?? $meta['governmentShouldCostHourly'] ?? 18)),
            'hoursPerDay' => min(24, max(1, (float) ($scope['hoursOfCoveragePerDay'] ?? $meta['hoursPerDay'] ?? 24))),
            'daysPerWeek' => min(7, max(1, (float) ($scope['daysOfCoveragePerWeek'] ?? $meta['daysPerWeek'] ?? 7))),
            'weeksPerYear' => min(52, max(1, (float) ($scope['weeksOfCoverage'] ?? $meta['weeksPerYear'] ?? 52))),
            'staffPerShift' => min(100, max(1, (float) ($scope['staffPerShift'] ?? $meta['staffPerShift'] ?? 1))),
        ];
        $tco = app(BudgetTcoEngine::class)->compute(['meta' => $inputs]);
        $weeks = $inputs['weeksPerYear'];
        $annual = $tco['total'];
        $vendorAnnual = $tco['vendorOfferTotal'];

        return [
            'budgetScenario' => ['meta' => $inputs],
            'baselineHourly' => $inputs['baselineWage'],
            'weeksInYear' => $weeks,
            'monthsInYear' => 12,
            'totalWeeklyHours' => $tco['annualHours'] / $weeks,
            'totalMonthlyHours' => $tco['annualHours'] / 12,
            'totalAnnualHours' => $tco['annualHours'],
            'totalStaffRequired' => $tco['ftesRequired'],
            'inHouseHourly' => $tco['internalTcoHourly'],
            'vendorHourly' => $tco['vendorTcoHourly'],
            'inHouseWeekly' => $annual / $weeks,
            'vendorWeekly' => $vendorAnnual / $weeks,
            'inHouseMonthly' => $annual / 12,
            'vendorMonthly' => $vendorAnnual / 12,
            'inHouseAnnual' => $annual,
            'vendorAnnual' => $vendorAnnual,
            'recoveryPerHour' => $tco['capitalRecoveryPerHour'],
            'costSavings' => $tco['capitalRecoveryAnnual'],
            'roiPct' => $tco['operationalCapitalPct'],
            'paybackMonths' => $tco['paybackMonths'],
            'dollarForDollarReturn' => $vendorAnnual > 0 ? $tco['capitalRecoveryAnnual'] / $vendorAnnual : 0,
        ];
    }

    public function applyToMainMenu(User $user, array $scenario): array
    {
        $summary = $this->forUser($user);
        if (! $summary) {
            throw ValidationException::withMessages([
                'budget' => ['Save your Budget Calculator inputs before viewing the budget-based ROI.'],
            ]);
        }
        $scenario['meta'] = array_replace((array) ($scenario['meta'] ?? []), [
            'roiCostBasis' => 'budget',
            'budgetScenario' => $summary['budgetScenario'],
            'employeeTrueHourlyCost' => $summary['inHouseHourly'],
            'weeklyHoursPerformed' => $summary['totalWeeklyHours'],
            'weeksInYear' => $summary['weeksInYear'],
            'monthsInYear' => 12,
        ]);

        return $scenario;
    }
}
