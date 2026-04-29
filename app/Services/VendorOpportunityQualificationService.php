<?php

namespace App\Services;

use App\Models\JobPosting;
use Carbon\Carbon;

class VendorOpportunityQualificationService
{
    /**
     * @return array{
     *     lead_tier: string,
     *     decision_maker_verified: bool,
     *     budget_confirmed: bool,
     *     scope_completed: bool,
     *     timeline_ready: bool,
     *     move_forward_confirmed: bool,
     *     estimated_annual_contract_value: float,
     *     vendor_target_count: int,
     *     missing_requirements: list<string>
     * }
     */
    public function qualify(JobPosting $job): array
    {
        $questionnaire = is_array($job->questionnaire_data) ? $job->questionnaire_data : [];

        $decisionMakerVerified = in_array(
            (string) data_get($questionnaire, 'final_decision_maker'),
            ['yes', 'authorized_representative'],
            true
        );

        $budgetConfirmed = in_array(
            (string) data_get($questionnaire, 'funds_approval_status'),
            ['flexible_budget', 'restrictive_budget'],
            true
        );

        $scopeCompleted = $this->scopeCompleted($job, $questionnaire);
        $timelineReady = $this->timelineReady($job, $questionnaire);
        $moveForwardConfirmed = in_array(
            (string) data_get($questionnaire, 'move_forward_if_accepted'),
            ['yes', 'ready_now', 'move_forward'],
            true
        );

        $requirements = [
            'decision_maker_verified' => $decisionMakerVerified,
            'budget_confirmed' => $budgetConfirmed,
            'scope_completed' => $scopeCompleted,
            'timeline_ready' => $timelineReady,
            'move_forward_confirmed' => $moveForwardConfirmed,
        ];

        $missing = array_keys(array_filter($requirements, static fn (bool $passed): bool => ! $passed));
        $leadTier = count($missing) === 0
            ? 'a'
            : (count($missing) === 1 ? 'b' : 'c');

        return [
            'lead_tier' => $leadTier,
            'decision_maker_verified' => $decisionMakerVerified,
            'budget_confirmed' => $budgetConfirmed,
            'scope_completed' => $scopeCompleted,
            'timeline_ready' => $timelineReady,
            'move_forward_confirmed' => $moveForwardConfirmed,
            'estimated_annual_contract_value' => $this->estimatedAnnualContractValue($job, $questionnaire),
            'vendor_target_count' => $leadTier === 'a' ? 5 : ($leadTier === 'b' ? 3 : 0),
            'missing_requirements' => array_values($missing),
        ];
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function scopeCompleted(JobPosting $job, array $questionnaire): bool
    {
        $hasCoverage = is_numeric(data_get($questionnaire, 'hours_per_day'))
            && is_numeric(data_get($questionnaire, 'days_per_week'))
            && is_numeric(data_get($questionnaire, 'weeks_per_year'));

        $hasScopeSummary = filled($job->title)
            && filled($job->location)
            && filled($job->category)
            && filled(data_get($questionnaire, 'service_types.0'))
            && filled(data_get($questionnaire, 'primary_reason'));

        return $hasCoverage && $hasScopeSummary;
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function timelineReady(JobPosting $job, array $questionnaire): bool
    {
        if ($job->service_start_date instanceof Carbon) {
            return $job->service_start_date->startOfDay()->diffInDays(now()->startOfDay(), false) <= 60;
        }

        return in_array(
            (string) data_get($questionnaire, 'service_start_timeline'),
            ['immediate', '0_30_days', '30_45_days', '30_60_days', 'within_60_days'],
            true
        );
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function estimatedAnnualContractValue(JobPosting $job, array $questionnaire): float
    {
        $annualBudget = $this->toFloat(data_get($questionnaire, 'annual_budget'));
        if ($annualBudget > 0) {
            return $annualBudget;
        }

        $budgetMax = $this->toFloat($job->budget_max);
        if ($budgetMax > 0) {
            return $budgetMax;
        }

        $monthlyBudget = $this->toFloat(data_get($questionnaire, 'monthly_budget'));
        if ($monthlyBudget > 0) {
            return $monthlyBudget * 12;
        }

        $hourlyBudget = $this->toFloat(data_get($questionnaire, 'hourly_budget'));
        $annualHours = $this->annualHours($questionnaire);
        if ($hourlyBudget > 0 && $annualHours > 0) {
            return $hourlyBudget * $annualHours;
        }

        $range = (string) data_get($questionnaire, 'budget_amount_range', '');
        if (preg_match_all('/\d[\d,]*/', $range, $matches) && isset($matches[0][0])) {
            $values = array_map(
                static fn (string $value): float => (float) str_replace(',', '', $value),
                $matches[0]
            );

            return (float) max($values);
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function annualHours(array $questionnaire): float
    {
        $hoursPerDay = $this->toFloat(data_get($questionnaire, 'hours_per_day'));
        $daysPerWeek = $this->toFloat(data_get($questionnaire, 'days_per_week'));
        $weeksPerYear = $this->toFloat(data_get($questionnaire, 'weeks_per_year'));

        return $hoursPerDay * $daysPerWeek * $weeksPerYear;
    }

    private function toFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
