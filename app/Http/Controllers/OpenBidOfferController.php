<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OpenBidOfferController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $bidId = $request->integer('bid');

        if ($bidId) {
            $this->authorizeBidAccess($user?->id, $user?->user_type, $bidId);
        }

        $bid = $this->resolveBid($user?->id, $user?->user_type, $bidId);
        $job = $bid?->jobPosting;
        $buyer = $job?->user;
        $questionnaire = is_array($job?->questionnaire_data) ? $job->questionnaire_data : [];

        $allBidsCount = 0;
        $acceptedBidsCount = 0;
        if ($job) {
            $allBidsCount = (int) $job->bids()->count();
            $acceptedBidsCount = (int) $job->bids()->where('status', 'accepted')->count();
        }

        $hoursPerDay = $this->questionnaireNumber($questionnaire, 'hours_per_day')
            ?? $this->deriveHoursPerDay($job?->daily_start_time, $job?->daily_end_time);
        $daysPerWeek = $this->questionnaireNumber($questionnaire, 'days_per_week')
            ?? (is_array($job?->coverage_days) ? count($job->coverage_days) : null);
        $weeklyHours = ($hoursPerDay !== null && $daysPerWeek !== null) ? (int) ($hoursPerDay * $daysPerWeek) : null;

        $totalWeeks = $this->questionnaireNumber($questionnaire, 'weeks_per_year')
            ?? $this->deriveTotalWeeks($job?->service_start_date, $job?->service_end_date);
        $monthlyHours = ($weeklyHours !== null && $totalWeeks !== null) ? (int) round(($weeklyHours * $totalWeeks) / 12) : null;
        $annualHours = ($weeklyHours !== null && $totalWeeks !== null) ? (int) ($weeklyHours * $totalWeeks) : null;

        [$city, $state] = $this->extractCityState((string) ($job?->location ?? ''));

        $bidOfferValue = (float) ($bid?->amount ?? 0);
        $creditsToRespond = $bidOfferValue > 0 ? (int) floor($bidOfferValue / 100) : null;
        $serviceTypeSummary = $this->serviceTypeSummary($job, $questionnaire);

        return view('pages.open-bid-offer', [
            'bid' => $bid,
            'job' => $job,
            'buyer' => $buyer,
            'vendor' => $bid?->user,
            'serviceTypeSummary' => $serviceTypeSummary,
            'maskedBuyerEmail' => $this->maskEmail((string) ($buyer?->email ?? '')),
            'maskedBuyerPhone' => $this->maskPhone((string) ($buyer?->phone ?? '')),
            'phoneVerified' => (bool) data_get($questionnaire, 'phone_verified', $buyer?->phone_verified ?? false),
            'decisionMakerValidated' => in_array(data_get($questionnaire, 'final_decision_maker'), ['yes', 'authorized_representative'], true),
            'budgetValidated' => in_array(data_get($questionnaire, 'funds_approval_status'), ['flexible_budget', 'restrictive_budget'], true),
            'bidOfferValue' => $bidOfferValue > 0 ? $bidOfferValue : null,
            'city' => $city,
            'state' => $state ?: $buyer?->state,
            'zipCode' => $job?->zip_code ?: $buyer?->zip_code,
            'creditsToRespond' => $creditsToRespond,
            'acceptedBidsCount' => $acceptedBidsCount,
            'responseDenominator' => 5,
            'projectDetails' => $this->projectDetails($questionnaire),
            'hoursPerDay' => $hoursPerDay,
            'daysPerWeek' => $daysPerWeek,
            'weeklyHours' => $weeklyHours,
            'monthlyHours' => $monthlyHours,
            'totalWeeks' => $totalWeeks,
            'totalMonths' => $totalWeeks ? (int) round($totalWeeks / 4.333) : null,
            'staffRequired' => $this->questionnaireNumber($questionnaire, 'guards_per_shift') ?? $job?->guards_per_shift,
            'annualHours' => $annualHours,
            'pageHasData' => $bid !== null,
        ]);
    }

    private function resolveBid(?int $userId, ?string $userType, ?int $bidId): ?Bid
    {
        $query = Bid::query()->with(['jobPosting.user', 'user'])->latest();

        if ($bidId) {
            $query->whereKey($bidId);
        }

        if (! $userId) {
            return $bidId ? $query->first() : null;
        }

        if ($userType === 'buyer') {
            $query->whereHas('jobPosting', fn ($q) => $q->where('user_id', $userId));
        } elseif ($userType === 'vendor') {
            $query->where('user_id', $userId);
        }

        return $query->first();
    }

    private function authorizeBidAccess(?int $userId, ?string $userType, int $bidId): void
    {
        $bid = Bid::query()->with('jobPosting:id,user_id')->find($bidId);
        if (! $bid) {
            abort(404);
        }

        if (! $userId) {
            abort(403);
        }

        if ($userType === 'admin') {
            return;
        }

        $isVendorOwner = (int) $bid->user_id === $userId;
        $isBuyerOwner = (int) ($bid->jobPosting?->user_id ?? 0) === $userId;

        if (! $isVendorOwner && ! $isBuyerOwner) {
            abort(403);
        }
    }

    private function deriveHoursPerDay(mixed $startTime, mixed $endTime): ?int
    {
        if (! is_string($startTime) || ! is_string($endTime) || $startTime === '' || $endTime === '') {
            return null;
        }

        try {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            return max(0, (int) round($start->diffInMinutes($end) / 60));
        } catch (\Throwable) {
            return null;
        }
    }

    private function deriveTotalWeeks(mixed $startDate, mixed $endDate): ?int
    {
        if (! $startDate || ! $endDate) {
            return null;
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();
            if ($end->lessThan($start)) {
                return null;
            }

            return max(1, (int) round($start->diffInDays($end) / 7));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function extractCityState(string $location): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $location))));
        $city = $parts[0] ?? null;
        $state = $parts[1] ?? null;

        return [$city, $state];
    }

    private function maskEmail(string $email): string
    {
        if ($email === '' || ! str_contains($email, '@')) {
            return 'Not provided';
        }

        [$name, $domain] = explode('@', $email, 2);
        $maskedName = substr($name, 0, 1) . str_repeat('*', max(4, strlen($name) - 1));

        $domainParts = explode('.', $domain);
        $topLevelDomain = count($domainParts) > 1 ? array_pop($domainParts) : null;
        $domainLabel = implode('.', $domainParts);
        if ($domainLabel === '') {
            $domainLabel = $topLevelDomain ?? $domain;
            $topLevelDomain = null;
        }

        $maskedDomain = match (strlen($domainLabel)) {
            0 => '***',
            1 => $domainLabel . '***',
            2 => substr($domainLabel, 0, 1) . '***',
            default => substr($domainLabel, 0, 1)
                . str_repeat('*', max(3, strlen($domainLabel) - 2))
                . substr($domainLabel, -1),
        };

        return $maskedName
            . '@'
            . $maskedDomain
            . ($topLevelDomain ? '.' . $topLevelDomain : '');
    }

    private function maskPhone(string $phone): string
    {
        if ($phone === '') {
            return 'Not provided';
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) < 10) {
            return '***';
        }

        return sprintf('(%s) ***-****', substr($digits, 0, 3));
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function questionnaireNumber(array $questionnaire, string $key): ?int
    {
        $value = data_get($questionnaire, $key);

        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function serviceTypeSummary(?\App\Models\JobPosting $job, array $questionnaire): string
    {
        if ($job?->category) {
            return $job->category;
        }

        return $this->requestedServiceTypesText($job, $questionnaire);
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function requestedServiceTypesText(?\App\Models\JobPosting $job, array $questionnaire): string
    {
        $services = $this->questionnaireList($questionnaire, 'service_types');

        if ($services !== []) {
            return implode(', ', $services);
        }

        return $job?->category ?: ($job?->title ?: 'Not provided');
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function questionnaireList(array $questionnaire, string $key): array
    {
        return array_values(array_filter(array_map(
            fn ($value) => is_scalar($value) ? (string) $value : null,
            (array) data_get($questionnaire, $key, [])
        )));
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function moneyText(mixed $value): string
    {
        if (! is_numeric($value)) {
            return 'Not provided';
        }

        return '$' . number_format((float) $value, 2);
    }

    private function valueText(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Not provided';
        }

        return (string) $value;
    }

    private function humanizeTimeline(?string $timeline): string
    {
        return match ($timeline) {
            'immediate' => 'Immediate',
            '15_days_or_less' => '15 days or less',
            '30_days_or_less' => '30 days or less',
            '30_60_days' => '30-60 days',
            'future_planning' => 'Future planning',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $timeline)),
        };
    }

    private function humanizeDecisionMaker(?string $value): string
    {
        return match ($value) {
            'yes' => 'Yes',
            'no' => 'No',
            'authorized_representative' => 'I am an authorized representative',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }

    private function humanizeApprovalAuthority(?string $value): string
    {
        return match ($value) {
            'under_1000' => 'Under $1,000',
            '1000_4999' => '$1,000 to $4,999',
            '5000_9999' => '$5,000 to $9,999',
            '10000_24999' => '$10,000 to $24,999',
            '25000_49999' => '$25,000 to $49,999',
            '50000_plus' => '$50,000+',
            'no_authority' => 'I do not have approval authority',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }

    private function humanizeBudgetApproval(?string $value): string
    {
        return match ($value) {
            'flexible_budget', 'restrictive_budget' => 'Yes',
            'pending' => 'Pending approval',
            'no_approved_budget' => 'No',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }

    private function humanizeMoveForward(?string $value): string
    {
        return match ($value) {
            'yes' => 'Yes',
            'no' => 'No',
            'need_internal_review' => 'Need internal review first',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }

    private function humanizeRequestType(?string $value): string
    {
        return match ($value) {
            'new_service' => 'New Service',
            'replace_current_provider' => 'Replace Current Provider',
            'expand_existing_coverage' => 'Expand Existing Coverage',
            'temporary_emergency_coverage' => 'Temporary / Emergency Coverage',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }

    private function humanizeBudgetFormat(?string $value): string
    {
        return match ($value) {
            'hourly_budget' => 'Hourly Budget',
            'monthly_budget' => 'Monthly Budget',
            'annual_budget' => 'Annual Budget',
            'need_gasq_estimate' => 'Need GASQ to help estimate',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     * @return array<int, array{label: string, value: string}>
     */
    private function projectDetails(array $questionnaire): array
    {
        return [
            [
                'label' => 'What is this service for?',
                'value' => $this->serviceForText(data_get($questionnaire, 'request_type')),
            ],
            [
                'label' => 'Are you the person authorized to make a final buying commitment with the vendor or approve payment for the proposed services?',
                'value' => $this->authorizedDecisionMakerText(data_get($questionnaire, 'final_decision_maker')),
            ],
            [
                'label' => 'Are you price shopping?',
                'value' => $this->priceShoppingText($questionnaire),
            ],
            [
                'label' => 'How likely are you to make a hiring decision?',
                'value' => $this->hiringDecisionLikelihoodText($questionnaire),
            ],
            [
                'label' => 'What is your sense of urgency for hiring a security vendor to start this security project?',
                'value' => $this->hiringUrgencyText($questionnaire),
            ],
        ];
    }

    private function serviceForText(?string $requestType): string
    {
        return match ($requestType) {
            'new_service' => 'New Purchase of Services',
            'replace_current_provider' => 'Replacement of Current Services',
            'expand_existing_coverage' => 'Expanded Purchase of Services',
            'temporary_emergency_coverage' => 'Emergency or Temporary Services',
            null, '' => 'Not provided',
            default => ucwords(str_replace('_', ' ', $requestType)),
        };
    }

    private function authorizedDecisionMakerText(?string $value): string
    {
        return match ($value) {
            'yes', 'authorized_representative' => 'Yes',
            'no' => 'No',
            null, '' => 'Not provided',
            default => $this->humanizeDecisionMaker($value),
        };
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function priceShoppingText(array $questionnaire): string
    {
        $moveForward = data_get($questionnaire, 'move_forward_if_accepted');
        $allowScopeAdjustment = data_get($questionnaire, 'allow_scope_adjustment');

        if ($moveForward === 'yes') {
            return "No, I'm ready to purchase at a fair & reasonable price";
        }

        if ($allowScopeAdjustment === 'maybe_after_review') {
            return "I'm still comparing pricing and scope options";
        }

        if ($allowScopeAdjustment === 'yes') {
            return 'Yes, I may still adjust the scope or pricing';
        }

        if ($allowScopeAdjustment === 'no') {
            return 'No, I am not price shopping';
        }

        return 'Not provided';
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function hiringDecisionLikelihoodText(array $questionnaire): string
    {
        return match (data_get($questionnaire, 'move_forward_if_accepted')) {
            'yes' => "I'm ready to hire right now",
            'need_internal_review' => 'Likely after internal review',
            'no' => 'Not ready to hire yet',
            default => 'Not provided',
        };
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function hiringUrgencyText(array $questionnaire): string
    {
        return match (data_get($questionnaire, 'service_start_timeline')) {
            'immediate' => 'Immediately',
            '15_days_or_less' => 'Within 15 days',
            '30_days_or_less' => 'Within 30 days',
            '30_60_days' => 'Within 30-45 days',
            'future_planning' => 'Future planning',
            default => 'Not provided',
        };
    }
}
