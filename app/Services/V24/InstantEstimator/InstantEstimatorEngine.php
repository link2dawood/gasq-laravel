<?php

namespace App\Services\V24\InstantEstimator;

use Illuminate\Support\Arr;

class InstantEstimatorEngine
{
    private const SERVICE_TYPES = [
        'unarmed' => ['label' => 'Unarmed Security Services', 'min' => 28.0, 'max' => 39.0, 'default' => 33.0],
        'armed' => ['label' => 'Armed Security Services', 'min' => 40.0, 'max' => 52.0, 'default' => 46.0],
        'supervisor' => ['label' => 'Security Site Supervisor', 'min' => 40.0, 'max' => 52.0, 'default' => 46.0],
        'mobile' => ['label' => 'Mobile Patrol Services', 'min' => 40.0, 'max' => 52.0, 'default' => 46.0],
        'loss' => ['label' => 'Loss / Crime Prevention Services', 'min' => 40.0, 'max' => 52.0, 'default' => 46.0],
        'executive' => ['label' => 'Executive Protection Agent', 'min' => 53.0, 'max' => 68.0, 'default' => 60.0],
        'offduty' => ['label' => 'Off Duty Police Officer', 'min' => 53.0, 'max' => 68.0, 'default' => 60.0],
        // Legacy aliases to avoid breaking old payloads.
        'patrol' => ['label' => 'Mobile Patrol Services', 'min' => 40.0, 'max' => 52.0, 'default' => 46.0],
    ];

    private const CHECK_OPTIONS = [
        21 => [
            'checks' => 21,
            'definition' => '1 check every 8 hours; 3 checks per day or 1 check per 8 hour shift; 7 days per week',
            'visitsPerWeek' => 21,
        ],
        28 => [
            'checks' => 28,
            'definition' => '1 check every 6 hours; 4 checks per day; 7 days per week',
            'visitsPerWeek' => 28,
        ],
        42 => [
            'checks' => 42,
            'definition' => '1 check every 4 hours; 6 checks per day or 2 checks per 8 hour shift; 7 days per week',
            'visitsPerWeek' => 42,
        ],
        56 => [
            'checks' => 56,
            'definition' => '1 check every 3 hours; 8 checks per day; 7 days per week',
            'visitsPerWeek' => 56,
        ],
        84 => [
            'checks' => 84,
            'definition' => '1 check every 2 hours; 12 checks per day or 4 checks per 8 hour shift; 7 days per week',
            'visitsPerWeek' => 84,
        ],
    ];

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);
        $posts = (array) ($scenario['posts'] ?? []);

        $serviceType = strtolower((string) (Arr::get($meta, 'serviceType') ?? 'unarmed'));
        $service = self::SERVICE_TYPES[$serviceType] ?? self::SERVICE_TYPES['unarmed'];
        $coverageModel = (string) (Arr::get($meta, 'coverageModel') ?? 'hours');

        $legacyHoursPerWeek = (float) (Arr::get($meta, 'hoursPerWeek') ?? Arr::get($meta, 'hours') ?? 40);
        $legacyGuards = (float) (Arr::get($meta, 'guards') ?? 1);
        $guessedDays = $legacyHoursPerWeek >= 56 ? 7.0 : 5.0;
        $guessedHoursPerDay = $this->clamp($legacyHoursPerWeek / max($guessedDays, 1), 8.0, 24.0);

        $hoursPerDay = $this->clamp((float) (Arr::get($meta, 'hoursPerDay') ?? $guessedHoursPerDay), 8.0, 24.0);
        $daysPerWeek = $this->clamp((float) (Arr::get($meta, 'daysPerWeek') ?? $guessedDays), 1.0, 7.0);
        $weeks = $this->clamp((float) (Arr::get($meta, 'weeks') ?? 52.0), 1.0, 52.0);
        $staffPerShift = $this->clamp((float) (Arr::get($meta, 'staffPerShift') ?? $legacyGuards), 1.0, 1000.0);
        $staffPerCheck = $this->clamp((float) (Arr::get($meta, 'staffPerCheck') ?? Arr::get($meta, 'staffPerShift') ?? $legacyGuards), 1.0, 1000.0);
        $minutesPerCheck = $this->clamp((float) (Arr::get($meta, 'minutesPerCheck') ?? 15.0), 8.0, 60.0);
        $weeklyChecks = (int) (Arr::get($meta, 'weeklyChecks') ?? 21);
        $selectedRate = max(
            0.0,
            (float) (Arr::get($meta, 'selectedRate')
                ?? Arr::get($meta, "payRates.$serviceType")
                ?? $service['default'])
        );

        if ($posts !== []) {
            $firstPost = (array) ($posts[0] ?? []);
            $staffPerShift = $this->clamp((float) (Arr::get($firstPost, 'qtyRequired') ?? $staffPerShift), 1.0, 1000.0);

            if (Arr::has($firstPost, 'weeklyHours')) {
                $legacyHoursPerWeek = max(0.0, (float) Arr::get($firstPost, 'weeklyHours'));
                $guessedDays = $legacyHoursPerWeek >= 56 ? 7.0 : 5.0;
                $hoursPerDay = $this->clamp($legacyHoursPerWeek / max($guessedDays, 1), 8.0, 24.0);
                $daysPerWeek = $this->clamp((float) (Arr::get($meta, 'daysPerWeek') ?? $guessedDays), 1.0, 7.0);
            }
        }

        $selectedCheckOption = self::CHECK_OPTIONS[$weeklyChecks] ?? self::CHECK_OPTIONS[21];
        $weeklyCoverageHours = $coverageModel === 'checks'
            ? ($selectedCheckOption['visitsPerWeek'] * $minutesPerCheck * $staffPerCheck) / 60
            : $hoursPerDay * $daysPerWeek * $staffPerShift;

        $monthlyCoverageHours = $weeklyCoverageHours * 52 / 12;
        $annualCoverageHours = $weeklyCoverageHours * 52;
        $termCoverageHours = $weeklyCoverageHours * $weeks;
        $monthsOfCoverageRaw = $weeks / 4.3333333333;
        $monthsOfCoverageRounded = (float) ceil($monthsOfCoverageRaw);
        $weeksCoveredRounded = (float) ceil($weeks);

        $productiveHoursPerOfficer = 1456.0;
        $annualizedFte = $productiveHoursPerOfficer > 0 ? $annualCoverageHours / $productiveHoursPerOfficer : 0.0;
        $totalWorkforceRequired = (float) ceil($annualizedFte);

        $directLabor = $selectedRate;
        $employerCost = $directLabor > 0 ? $directLabor / 0.70 : 0.0;
        $annualEmployerCost = $employerCost * 3744;
        $internalTrueHourly = $annualEmployerCost > 0 ? $annualEmployerCost / 1456 : 0.0;
        $outsourcedHourly = $internalTrueHourly * 0.70;

        $outsourcedWeekly = $outsourcedHourly * $weeklyCoverageHours;
        $outsourcedMonthly = $outsourcedHourly * $monthlyCoverageHours;
        $outsourcedAnnual = $outsourcedHourly * $annualCoverageHours;
        $outsourcedTerm = $outsourcedHourly * $termCoverageHours;

        $internalWeekly = $internalTrueHourly * $weeklyCoverageHours;
        $internalMonthly = $internalTrueHourly * $monthlyCoverageHours;
        $internalAnnual = $internalTrueHourly * $annualCoverageHours;
        $internalTerm = $internalTrueHourly * $termCoverageHours;

        $annualCostPerProfessionalOut = $totalWorkforceRequired > 0 ? $outsourcedAnnual / $totalWorkforceRequired : 0.0;
        $annualCostPerProfessionalIn = $totalWorkforceRequired > 0 ? $internalAnnual / $totalWorkforceRequired : 0.0;
        $hourlyPerProfessionalOut = $totalWorkforceRequired > 0 ? $outsourcedHourly / $totalWorkforceRequired : 0.0;
        $hourlyPerProfessionalIn = $totalWorkforceRequired > 0 ? $internalTrueHourly / $totalWorkforceRequired : 0.0;
        $costPerMinuteOut = $outsourcedHourly / 60;
        $costPerMinuteIn = $internalTrueHourly / 60;

        $recoveredCapitalAnnual = max(0.0, $internalAnnual - $outsourcedAnnual);
        $recoveredCapitalTerm = max(0.0, $internalTerm - $outsourcedTerm);
        $appraisalFee = $recoveredCapitalAnnual * 0.01;
        $efficiencyGain = $appraisalFee > 0 ? $recoveredCapitalAnnual / $appraisalFee : 0.0;
        $breakevenMonths = $internalMonthly > 0 ? $outsourcedTerm / $internalMonthly : 0.0;

        return [
            'serviceType' => $serviceType,
            'serviceLabel' => $service['label'],
            'recommendedMin' => round((float) $service['min'], 2),
            'recommendedMax' => round((float) $service['max'], 2),
            'coverageModel' => $coverageModel,
            'selectedCheckDefinition' => $selectedCheckOption['definition'],
            'weeksCoveredRounded' => round($weeksCoveredRounded, 2),
            'monthsOfCoverage' => round($monthsOfCoverageRaw, 2),
            'monthsOfCoverageRounded' => round($monthsOfCoverageRounded, 2),
            'weeklyCoverageHours' => round($weeklyCoverageHours, 2),
            'monthlyCoverageHours' => round($monthlyCoverageHours, 2),
            'annualCoverageHours' => round($annualCoverageHours, 2),
            'termCoverageHours' => round($termCoverageHours, 2),
            'totalWorkforceRequired' => round($totalWorkforceRequired, 2),
            'annualizedFte' => round($annualizedFte, 2),
            'directLabor' => round($directLabor, 2),
            'employerCost' => round($employerCost, 2),
            'annualEmployerCost' => round($annualEmployerCost, 2),
            'internalTrueHourly' => round($internalTrueHourly, 2),
            'outsourcedHourly' => round($outsourcedHourly, 2),
            'outsourcedWeekly' => round($outsourcedWeekly, 2),
            'outsourcedMonthly' => round($outsourcedMonthly, 2),
            'outsourcedAnnual' => round($outsourcedAnnual, 2),
            'outsourcedTerm' => round($outsourcedTerm, 2),
            'internalWeekly' => round($internalWeekly, 2),
            'internalMonthly' => round($internalMonthly, 2),
            'internalAnnual' => round($internalAnnual, 2),
            'internalTerm' => round($internalTerm, 2),
            'annualCostPerProfessionalOut' => round($annualCostPerProfessionalOut, 2),
            'annualCostPerProfessionalIn' => round($annualCostPerProfessionalIn, 2),
            'hourlyPerProfessionalOut' => round($hourlyPerProfessionalOut, 2),
            'hourlyPerProfessionalIn' => round($hourlyPerProfessionalIn, 2),
            'costPerMinuteOut' => round($costPerMinuteOut, 2),
            'costPerMinuteIn' => round($costPerMinuteIn, 2),
            'recoveredCapitalAnnual' => round($recoveredCapitalAnnual, 2),
            'recoveredCapitalTerm' => round($recoveredCapitalTerm, 2),
            'appraisalFee' => round($appraisalFee, 2),
            'efficiencyGain' => round($efficiencyGain, 2),
            'breakevenMonths' => round($breakevenMonths, 2),
            'differenceHourly' => round($internalTrueHourly - $outsourcedHourly, 2),
            'differenceWeekly' => round($internalWeekly - $outsourcedWeekly, 2),
            'differenceMonthly' => round($internalMonthly - $outsourcedMonthly, 2),
            'differenceAnnual' => round($internalAnnual - $outsourcedAnnual, 2),
            'differencePerProfessionalAnnual' => round($annualCostPerProfessionalIn - $annualCostPerProfessionalOut, 2),
            'differencePerProfessionalHourly' => round($hourlyPerProfessionalIn - $hourlyPerProfessionalOut, 2),
            'overtimeInHouse' => round($internalTrueHourly * 1.5, 2),
            'overtimeOutsourced' => round($outsourcedHourly * 1.5, 2),
            // Legacy keys kept for compatibility with older views and consumers.
            'estimatedHourlyRate' => round($outsourcedHourly, 2),
            'estimatedWeeklyTotal' => round($outsourcedWeekly, 2),
            'estimatedMonthlyTotal' => round($outsourcedMonthly, 2),
            'estimatedAnnualTotal' => round($outsourcedAnnual, 2),
            'livingWageBase' => round($directLabor, 2),
            'withOverheadHourly' => round($employerCost, 2),
            'serviceMultiplier' => round($directLabor > 0 ? $internalTrueHourly / max($directLabor, 0.01) : 0.0, 2),
        ];
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return min(max($value, $min), $max);
    }
}
