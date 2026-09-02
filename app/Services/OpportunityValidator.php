<?php

namespace App\Services;

/**
 * Final procurement validation gate for a security service opportunity (review spec §2).
 *
 * The review page is not a summary screen — it is the control point that decides whether an
 * opportunity may be released to the vendor network. The buyer must be able to answer one
 * question: is this complete, financially supportable, and ready for qualified vendors?
 *
 * Checks are returned as a list rather than a single boolean so the page can show exactly
 * what is blocking release (§31 — prevent errors, don't just report them at submit time).
 */
class OpportunityValidator
{
    public const STATUS_READY = 'ready';
    public const STATUS_ACTION_REQUIRED = 'action_required';

    /**
     * Validate a questionnaire payload.
     *
     * @param  array<string, mixed>  $q
     * @return array{
     *     status: string,
     *     checks: list<array{key:string,label:string,passed:bool,blocking:bool,message:?string}>,
     *     blocking_count: int,
     *     warning_count: int
     * }
     */
    public function validate(array $q): array
    {
        $checks = [
            $this->check('scope', 'Scope complete', $this->hasScope($q)),
            $this->check('coverage', 'Coverage schedule complete', $this->hasCoverage($q)),
            $this->check('coverage_math', 'Coverage hours reconcile', $this->coverageReconciles($q), blocking: false,
                message: 'The staffing entered may not sustain the requested coverage without overtime or relief personnel.'),
            $this->check('baseline_wage', 'Baseline wage established', $this->hasBaselineWage($q)),
            $this->check('baseline_wage_acknowledgement', 'Buyer baseline wage acknowledged', ($q['baseline_wage_acknowledged'] ?? false) === true || ($q['baseline_wage_acknowledged'] ?? '') === '1'),
            $this->check('baseline_wage_validation', 'Baseline wage approved', ($q['baseline_wage_validation_status'] ?? '') !== 'not_approved',
                message: 'The baseline wage is below the applicable GASQ minimum eligibility floor.'),
            $this->check('cost_to_protect', 'Cost to Protect validated', $this->hasCostToProtect($q),
                message: 'Validate GASQ Cost to Protect before inviting vendors, or explicitly mark it not required for this service.'),
            $this->check('budget', 'Approved budget confirmed', $this->hasApprovedBudget($q)),
            $this->check('budget_authority', 'Approved offer is within recorded authority', $this->budgetFitsAuthority($q),
                message: 'The approved buyer offer exceeds the buyer\'s recorded approval authority.'),
            $this->check('decision_maker', 'Purchasing authority confirmed', $this->hasDecisionMaker($q)),
            $this->check('start_date', 'Start date confirmed', filled($q['service_start_date'] ?? null)),
            $this->check('date_range', 'Start and end dates reconcile with the contract term', $this->datesReconcile($q),
                message: 'Add a valid end date and reconcile the coverage period with the selected contract term.'),
            $this->check('contract_term', 'Contract term confirmed', filled($q['desired_contract_term'] ?? null)),
            $this->check('category_property', 'Service category and property type reconcile', $this->categoryMatchesProperty($q),
                message: 'The selected service category conflicts with the property type.'),
            $this->check('pricing_method', 'Pricing method confirmed', filled($q['selection_method'] ?? null)),
            $this->check('vendor_requirements', 'Vendor requirements complete', $this->hasVendorRequirements($q)),
            $this->check('scope_quality', 'Scope entries are production-ready', $this->hasProductionReadyScope($q),
                message: 'Remove placeholder or incomplete scope entries before release.'),
        ];

        $blocking = array_filter($checks, fn ($c) => $c['blocking'] && ! $c['passed']);
        $warnings = array_filter($checks, fn ($c) => ! $c['blocking'] && ! $c['passed']);

        return [
            'status' => $blocking === [] ? self::STATUS_READY : self::STATUS_ACTION_REQUIRED,
            'checks' => $checks,
            'blocking_count' => count($blocking),
            'warning_count' => count($warnings),
        ];
    }

    /** Is the opportunity releasable? Warnings do not block; missing essentials do. */
    public function isReadyForRelease(array $q): bool
    {
        return $this->validate($q)['status'] === self::STATUS_READY;
    }

    /**
     * @return array{key:string,label:string,passed:bool,blocking:bool,message:?string}
     */
    private function check(string $key, string $label, bool $passed, bool $blocking = true, ?string $message = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'passed' => $passed,
            'blocking' => $blocking,
            'message' => $passed ? null : $message,
        ];
    }

    private function hasScope(array $q): bool
    {
        return ! empty($q['service_types']) && ! empty($q['duties_required']);
    }

    private function hasCoverage(array $q): bool
    {
        foreach (['hours_per_day', 'days_per_week', 'weeks_per_year', 'staff_per_shift'] as $field) {
            if (! is_numeric($q[$field] ?? null) || (float) $q[$field] <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Post hours are not the same as the workforce needed to hold them (§7).
     *
     * A single officer cannot sustain more than roughly 40 productive hours a week once
     * relief, leave and turnover are accounted for, so weekly coverage hours divided by
     * the staffing offered is a crude but useful reconciliation signal.
     */
    private function coverageReconciles(array $q): bool
    {
        if (! $this->hasCoverage($q)) {
            return false;
        }

        $weeklyCoverageHours = (float) $q['hours_per_day'] * (float) $q['days_per_week'] * (float) $q['staff_per_shift'];
        $officers = max(1.0, (float) $q['staff_per_shift']);

        return ($weeklyCoverageHours / $officers) <= 40.0;
    }

    private function hasBaselineWage(array $q): bool
    {
        return is_numeric($q['baseline_wage'] ?? null) && (float) $q['baseline_wage'] > 0;
    }

    private function hasCostToProtect(array $q): bool
    {
        $status = (string) ($q['cost_to_protect_status'] ?? '');

        return $status === 'validated'
            || ($status === 'not_required' && ($q['cost_to_protect_required'] ?? 'yes') === 'no');
    }

    private function hasApprovedBudget(array $q): bool
    {
        $status = (string) ($q['budget_approved_status'] ?? '');
        $amount = $q['approved_budget_amount'] ?? null;

        return in_array($status, ['yes', 'approved'], true)
            && is_numeric($amount)
            && (float) $amount > 0;
    }

    private function hasDecisionMaker(array $q): bool
    {
        $decisionMaker = (string) ($q['final_decision_maker'] ?? '');
        $approvalAuthority = (string) ($q['approval_authority'] ?? '');

        return in_array($decisionMaker, ['yes', 'authorized_representative'], true)
            && $approvalAuthority !== ''
            && $approvalAuthority !== 'no_authority';
    }

    private function hasVendorRequirements(array $q): bool
    {
        return filled($q['insurance_minimums_required'] ?? null);
    }

    private function budgetFitsAuthority(array $q): bool
    {
        $amount = is_numeric($q['approved_budget_amount'] ?? null) ? (float) $q['approved_budget_amount'] : 0.0;
        $authority = (string) ($q['approval_authority'] ?? '');
        $limits = ['under_1000' => 999.99, '1000_4999' => 4999.99, '5000_9999' => 9999.99, '10000_24999' => 24999.99, '25000_49999' => 49999.99];

        return ! isset($limits[$authority]) || $amount <= $limits[$authority];
    }

    private function datesReconcile(array $q): bool
    {
        $start = $this->date($q['service_start_date'] ?? null);
        $end = $this->date($q['service_end_date'] ?? null);
        if (! $start) return false;

        $term = strtolower((string) ($q['desired_contract_term'] ?? ''));
        $weeks = is_numeric($q['weeks_per_year'] ?? null) ? (int) $q['weeks_per_year'] : 0;
        if (str_contains($term, 'one-time') || str_contains($term, 'temporary')) {
            return $end !== null && $end->greaterThanOrEqualTo($start) && $weeks <= 12;
        }

        return $end === null || $end->greaterThan($start);
    }

    private function categoryMatchesProperty(array $q): bool
    {
        $category = strtolower((string) ($q['category'] ?? ''));
        $property = strtolower((string) ($q['property_type'] ?? ''));

        if ($category === '' || $property === '') return true;
        if (str_contains($category, 'school')) return str_contains($property, 'school') || str_contains($property, 'education');
        if (str_contains($category, 'event')) return str_contains($property, 'event');

        return true;
    }

    private function hasProductionReadyScope(array $q): bool
    {
        foreach (['primary_reason', 'known_site_risks', 'duties_other', 'service_type_other'] as $field) {
            $value = trim((string) ($q[$field] ?? ''));
            if ($value !== '' && preg_match('/^(?:test|\\d+test|n\\/?a|not provided)$/i', $value)) return false;
        }

        return true;
    }

    private function date(mixed $value): ?\Carbon\Carbon
    {
        if (! filled($value)) return null;
        try { return \Carbon\Carbon::parse($value)->startOfDay(); } catch (\Throwable) { return null; }
    }
}
