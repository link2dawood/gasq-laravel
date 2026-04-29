<?php

namespace App\Services;

use App\Models\JobPosting;

class VendorOpportunityBidScoringService
{
    /**
     * @return array{score: int, label: string, flagged: bool, recommended_min: float, recommended_max: float}
     */
    public function score(JobPosting $job, float $hourlyBillRate, float $annualPrice = 0.0): array
    {
        $annualHours = $this->annualHours($job);
        $recommendedMin = 0.0;
        $recommendedMax = 0.0;

        if ($annualHours > 0 && $job->budget_min !== null && $job->budget_max !== null) {
            $recommendedMin = round(((float) $job->budget_min) / $annualHours, 2);
            $recommendedMax = round(((float) $job->budget_max) / $annualHours, 2);
        } elseif ($annualHours > 0 && $annualPrice > 0.0) {
            $recommendedMin = round($annualPrice / $annualHours, 2);
            $recommendedMax = round($recommendedMin * 1.15, 2);
        }

        if ($recommendedMin <= 0.0) {
            return [
                'score' => 80,
                'label' => 'competitive',
                'flagged' => false,
                'recommended_min' => $recommendedMin,
                'recommended_max' => $recommendedMax,
            ];
        }

        $ratio = $hourlyBillRate / max($recommendedMin, 0.01);
        $score = match (true) {
            $ratio >= 1.0 && ($recommendedMax <= 0.0 || $hourlyBillRate <= $recommendedMax) => 95,
            $ratio >= 0.9 => 82,
            $ratio >= 0.8 => 68,
            default => 50,
        };

        $label = match (true) {
            $score >= 90 => 'strong',
            $score >= 75 => 'competitive',
            $score >= 60 => 'aggressive',
            default => 'unsustainable',
        };

        return [
            'score' => $score,
            'label' => $label,
            'flagged' => $score < 60,
            'recommended_min' => $recommendedMin,
            'recommended_max' => $recommendedMax,
        ];
    }

    private function annualHours(JobPosting $job): float
    {
        $questionnaire = is_array($job->questionnaire_data) ? $job->questionnaire_data : [];
        $hoursPerDay = is_numeric(data_get($questionnaire, 'hours_per_day')) ? (float) data_get($questionnaire, 'hours_per_day') : 0.0;
        $daysPerWeek = is_numeric(data_get($questionnaire, 'days_per_week')) ? (float) data_get($questionnaire, 'days_per_week') : 0.0;
        $weeksPerYear = is_numeric(data_get($questionnaire, 'weeks_per_year')) ? (float) data_get($questionnaire, 'weeks_per_year') : 0.0;

        return $hoursPerDay * $daysPerWeek * $weeksPerYear;
    }
}
