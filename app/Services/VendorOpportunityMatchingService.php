<?php

namespace App\Services;

use App\Models\JobPosting;
use App\Models\User;
use App\Models\VendorCapability;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VendorOpportunityMatchingService
{
    /**
     * @return Collection<int, array{vendor: User, score: float, reasons: list<string>}>
     */
    public function match(JobPosting $job, string $leadTier, int $targetCount): Collection
    {
        $questionnaire = is_array($job->questionnaire_data) ? $job->questionnaire_data : [];
        $requestedServiceTypes = collect(data_get($questionnaire, 'service_types', []))
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->map(fn (string $value): string => Str::lower($value))
            ->push(Str::lower((string) $job->category))
            ->filter()
            ->values();

        $serviceAreaNeedles = collect([
            Str::lower((string) $job->location),
            Str::lower((string) $job->zip_code),
            Str::lower((string) $job->user?->state),
        ])->filter()->values();

        $vendors = User::query()
            ->where('user_type', 'vendor')
            ->whereNotNull('email_verified_at')
            ->with(['vendorProfile', 'vendorCapability', 'vendorOpportunityInvitations'])
            ->get();

        $matches = $vendors
            ->map(function (User $vendor) use ($job, $requestedServiceTypes, $serviceAreaNeedles): ?array {
                $capability = $vendor->vendorCapability;
                if (! $capability instanceof VendorCapability) {
                    return null;
                }

                $reasons = [];
                $score = 0.0;

                if ($this->matchesServiceArea($capability, $serviceAreaNeedles)) {
                    $score += 30;
                    $reasons[] = 'Service area matched';
                } else {
                    return null;
                }

                if ($this->matchesCompetency($capability, $requestedServiceTypes, (string) $job->category)) {
                    $score += 25;
                    $reasons[] = 'Service capability matched';
                } else {
                    return null;
                }

                if ($this->meetsComplianceNeeds($capability, $questionnaire)) {
                    $score += 15;
                    $reasons[] = 'Compliance requirements matched';
                }

                $capacityScore = $this->capacityScore($capability, $questionnaire);
                $score += $capacityScore;
                if ($capacityScore > 0) {
                    $reasons[] = 'Capacity aligned';
                }

                $speedScore = $this->responseSpeedScore($vendor, $capability);
                $score += $speedScore;
                if ($speedScore > 0) {
                    $reasons[] = 'Responsive vendor';
                }

                $qualityScore = $this->qualityScore($vendor, $capability);
                $score += $qualityScore;
                if ($qualityScore > 0) {
                    $reasons[] = 'Strong profile quality';
                }

                return [
                    'vendor' => $vendor,
                    'score' => round($score, 2),
                    'reasons' => $reasons,
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->values();

        $poolLimit = $leadTier === 'a' ? 5 : 3;

        return $matches->take(min($poolLimit, max(0, $targetCount)));
    }

    /**
     * @param  Collection<int, string>  $needles
     */
    private function matchesServiceArea(VendorCapability $capability, Collection $needles): bool
    {
        $areas = collect($capability->service_areas ?? [])
            ->merge($capability->states_licensed ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->map(fn (string $value): string => Str::lower($value))
            ->values();

        if ($areas->isEmpty()) {
            return false;
        }

        foreach ($needles as $needle) {
            if ($areas->contains(fn (string $area): bool => Str::contains($area, (string) $needle) || Str::contains((string) $needle, $area))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, string>  $requestedServiceTypes
     */
    private function matchesCompetency(VendorCapability $capability, Collection $requestedServiceTypes, string $category): bool
    {
        $competencies = collect($capability->core_competencies ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->map(fn (string $value): string => Str::lower($value))
            ->values();

        if ($requestedServiceTypes->isEmpty()) {
            return $competencies->isNotEmpty();
        }

        foreach ($requestedServiceTypes as $serviceType) {
            if ($competencies->contains(fn (string $competency): bool => Str::contains($competency, $serviceType) || Str::contains($serviceType, $competency))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function meetsComplianceNeeds(VendorCapability $capability, array $questionnaire): bool
    {
        $needsLicense = data_get($questionnaire, 'officer_licensing_required') === 'yes';
        $needsInsurance = data_get($questionnaire, 'insurance_minimums_required') === 'yes';
        $needsBackground = data_get($questionnaire, 'background_checks_required') === 'yes';

        if ($needsLicense && ! $capability->license_verified) {
            return false;
        }
        if ($needsInsurance && ! $capability->insurance_verified) {
            return false;
        }
        if ($needsBackground && ! $capability->background_check_verified) {
            return false;
        }

        return $needsLicense || $needsInsurance || $needsBackground;
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function capacityScore(VendorCapability $capability, array $questionnaire): float
    {
        $weeklyHours = $this->toFloat(data_get($questionnaire, 'hours_per_day'))
            * $this->toFloat(data_get($questionnaire, 'days_per_week'));

        if ($weeklyHours <= 0) {
            return 0.0;
        }

        $teamSize = (string) ($capability->team_size ?? '');

        return match (true) {
            Str::contains(Str::lower($teamSize), '100') => 15.0,
            Str::contains(Str::lower($teamSize), '50') => 12.0,
            Str::contains(Str::lower($teamSize), '25') => 9.0,
            Str::contains(Str::lower($teamSize), '10') => 6.0,
            default => 3.0,
        };
    }

    private function responseSpeedScore(User $vendor, VendorCapability $capability): float
    {
        $acceptedAvgHours = $vendor->vendorOpportunityInvitations
            ->filter(fn ($invitation): bool => $invitation->opened_at !== null && $invitation->accepted_at !== null)
            ->map(fn ($invitation): float => $invitation->opened_at->diffInHours($invitation->accepted_at))
            ->avg();

        if (is_numeric($acceptedAvgHours)) {
            return $acceptedAvgHours <= 6 ? 10.0 : ($acceptedAvgHours <= 24 ? 7.0 : 4.0);
        }

        return Str::contains(Str::lower((string) $capability->response_time), '24') ? 5.0 : 2.0;
    }

    private function qualityScore(User $vendor, VendorCapability $capability): float
    {
        $wins = $vendor->vendorOpportunityInvitations->where('status', 'awarded')->count();
        $submissions = $vendor->vendorOpportunityInvitations
            ->whereIn('status', ['bid_submitted', 'under_review', 'awarded', 'not_selected'])
            ->count();
        $winRateScore = $submissions > 0 ? min(10.0, ($wins / $submissions) * 10.0) : 0.0;

        $verifiedBonus = ($vendor->vendorProfile?->is_verified ? 4.0 : 0.0)
            + min(6.0, ((int) ($capability->profile_completion_score ?? 0)) / 20);

        return $winRateScore + $verifiedBonus;
    }

    private function toFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
