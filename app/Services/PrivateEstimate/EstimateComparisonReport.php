<?php

namespace App\Services\PrivateEstimate;

use App\Models\EstimatePricing;
use App\Models\EstimateScope;
use App\Models\PrivateEstimate;
use App\Services\CostToProtectEstimate;
use RuntimeException;

/**
 * Figures behind the buyer-facing side-by-side comparison report, the single
 * deliverable of the Private Estimate by Invitation flow.
 *
 * Three rules separate this from the master Cost to Protect estimate:
 *
 *  1. The vendor column is the rate the platform calculated and both sides
 *     agreed, taken from the estimate's pricing row (final_bill_rate). It is
 *     never internal cost multiplied by a discount factor. A modelled vendor
 *     column makes capital recovery a constant 30% and payback a constant 8.4
 *     months on every report, which is what the generic calculators print.
 *  2. The internal column is the buyer's own stated current cost when they gave
 *     one at intake, and the GASQ should-cost model only when they did not. The
 *     report says which of the two it is showing; they are different claims.
 *  3. The totals are reconciled against the budget amount the buyer had
 *     approved, so the document cannot present a price the buyer never had
 *     funding for without saying so on its face.
 */
class EstimateComparisonReport
{
    /** Overtime / holiday multiplier, as the master estimate uses. */
    private const OT_MULTIPLIER = 1.5;

    /** Billable hours behind one FTE per year, as the GASQ model defines it. */
    private const BILLABLE_HOURS_PER_FTE = 1456;

    public function __construct(private CostToProtectEstimate $costToProtect) {}

    /**
     * True when the estimate has everything the report needs: a confirmed scope
     * and a platform-calculated rate for that same scope version.
     */
    public function ready(PrivateEstimate $estimate): bool
    {
        $scope = $estimate->currentScope();
        $pricing = $estimate->currentPricing();

        return $scope !== null
            && $pricing !== null
            && (float) $pricing->final_bill_rate > 0;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException when the estimate has not been priced yet
     */
    public function build(PrivateEstimate $estimate): array
    {
        $scope = $estimate->currentScope();
        $pricing = $estimate->currentPricing();

        if ($scope === null) {
            throw new RuntimeException("Private estimate {$estimate->public_id} has no confirmed scope to report on.");
        }

        if ($pricing === null || (float) $pricing->final_bill_rate <= 0) {
            throw new RuntimeException("Private estimate {$estimate->public_id} has no calculated rate for scope version {$estimate->current_scope_version}.");
        }

        $coverage = $this->coverage($scope);
        $internal = $this->internal($estimate, $scope, $coverage);
        $vendor = $this->vendor($pricing, $coverage);
        $recovery = $this->recovery($internal, $vendor, $coverage);

        return [
            'estimate' => $estimate,
            'scope' => $scope,
            'pricing' => $pricing,
            'coverage' => $coverage,
            'internal' => $internal,
            'vendor' => $vendor,
            'recovery' => $recovery,
            'budget' => $this->budget($estimate, $vendor['annual'], $coverage),
            'agreement' => $this->agreement($estimate, $scope, $pricing),
            'parties' => $this->parties($estimate),
        ];
    }

    /**
     * Hours on post. Derived from what the buyer answered, with the mirrored
     * weekly/annual columns as the fallback when the breakdown is absent.
     *
     * @return array<string, float|int>
     */
    private function coverage(EstimateScope $scope): array
    {
        $posts = max(1, (int) ($scope->posts ?: 1));
        $weeksPerYear = $this->clamp((float) ($scope->weeks_per_year ?: 52), 1, 52);

        $hoursPerDay = (float) $scope->hours_per_day;
        $daysPerWeek = (float) $scope->days_per_week;

        if ($hoursPerDay > 0 && $daysPerWeek > 0) {
            $hoursPerDay = $this->clamp($hoursPerDay, 0.5, 24);
            $daysPerWeek = $this->clamp($daysPerWeek, 1, 7);
            $weeklyHours = $hoursPerDay * $daysPerWeek * $posts;
        } else {
            // No breakdown captured: fall back to the mirrored totals.
            $weeklyHours = (float) ($scope->weekly_hours ?: 0);
            if ($weeklyHours <= 0 && (float) $scope->annual_hours > 0) {
                $weeklyHours = (float) $scope->annual_hours / $weeksPerYear;
            }
        }

        if ($weeklyHours <= 0) {
            throw new RuntimeException('The scope carries no hours of coverage, so there is nothing to compare.');
        }

        $annualHours = $weeklyHours * $weeksPerYear;

        return [
            'posts' => $posts,
            'locations' => max(1, (int) ($scope->locations ?: 1)),
            'hoursPerDay' => $hoursPerDay,
            'daysPerWeek' => $daysPerWeek,
            'weeksPerYear' => $weeksPerYear,
            'weeklyHours' => $weeklyHours,
            'monthlyHours' => $annualHours / 12,
            'annualHours' => $annualHours,
            'monthsOfCoverage' => $weeksPerYear * 12 / 52,
            // Staff required = weekly hours on post ÷ a guard's weekly billable
            // hours (1456 ÷ 52 = 28), rounded up. The scope's own figure wins
            // when the vendor set one during review.
            'ftesRequired' => (float) $scope->guards_required > 0
                ? (int) ceil((float) $scope->guards_required)
                : max(1, (int) ceil($weeklyHours / (self::BILLABLE_HOURS_PER_FTE / 52))),
        ];
    }

    /**
     * The buyer's side. Their stated current cost when intake captured one,
     * otherwise the GASQ should-cost model off the baseline wage.
     *
     * @param  array<string, float|int>  $coverage
     * @return array<string, mixed>
     */
    private function internal(PrivateEstimate $estimate, EstimateScope $scope, array $coverage): array
    {
        $annualHours = (float) $coverage['annualHours'];
        $statedHourly = (float) $estimate->current_bill_rate;
        $statedAnnual = (float) $estimate->current_annual_cost;

        if ($statedHourly > 0 || $statedAnnual > 0) {
            $hourly = $statedHourly > 0 ? $statedHourly : $statedAnnual / $annualHours;
            $annual = $statedAnnual > 0 ? $statedAnnual : $hourly * $annualHours;
            $source = 'stated';
            $label = 'Your current cost to protect';
            $basis = 'As provided by the buyer at intake.';
        } else {
            // One source of truth for the should-cost model: the same service
            // the master estimate and the allocation report read.
            $modelled = $this->costToProtect->build([
                'meta' => [
                    'baselineWage' => (float) ($scope->baseline_wage ?: $estimate->baseline_wage ?: 0) ?: null,
                    'scope' => [
                        'hoursOfCoveragePerDay' => $coverage['hoursPerDay'] ?: null,
                        'daysOfCoveragePerWeek' => $coverage['daysPerWeek'] ?: null,
                        'weeksOfCoverage' => $coverage['weeksPerYear'],
                        'staffPerShift' => $coverage['posts'],
                    ],
                ],
            ]);

            $hourly = (float) $modelled['internalTcoHourly'];
            $annual = $hourly * $annualHours;
            $source = 'modelled';
            $label = 'GASQ modelled in-house should-cost';
            $basis = 'Modelled from the baseline wage using the GASQ Cost to Protect method; the buyer did not state a current cost.';
        }

        return [
            'source' => $source,
            'label' => $label,
            'basis' => $basis,
            'baselineWage' => (float) ($scope->baseline_wage ?: $estimate->baseline_wage ?: 0),
            'hourly' => $hourly,
            'otHourly' => $hourly * self::OT_MULTIPLIER,
            'weekly' => $annual / (float) $coverage['weeksPerYear'],
            'monthly' => $annual / 12,
            'annual' => $annual,
            'annualPerFte' => $hourly * self::BILLABLE_HOURS_PER_FTE,
        ];
    }

    /**
     * The vendor's side: the platform-calculated rate both parties agreed. Never
     * derived from the internal column.
     *
     * @param  array<string, float|int>  $coverage
     * @return array<string, mixed>
     */
    private function vendor(EstimatePricing $pricing, array $coverage): array
    {
        $annualHours = (float) $coverage['annualHours'];
        $hourly = (float) $pricing->final_bill_rate;

        // Every vendor figure is the calculated rate across the agreed hours.
        // The stored period costs are NOT preferred over this, deliberately: a
        // figure written straight into the row would let a vendor move the
        // quoted total away from the rate the formula produced. They are kept
        // only as a cross-check, and a disagreement is surfaced rather than
        // printed.
        $annual = $hourly * $annualHours;
        $stored = (float) $pricing->annual_cost;
        $storedDiffers = $stored > 0 && abs($stored - $annual) > max(1.0, $annual * 0.005);

        return [
            'source' => 'platform',
            'label' => 'Agreed vendor cost to protect',
            'basis' => 'Calculated by the GASQ platform from the vendor\'s workforce build-up and agreed by both parties.',
            'hourly' => $hourly,
            'otHourly' => $hourly * self::OT_MULTIPLIER,
            'weekly' => $annual / (float) $coverage['weeksPerYear'],
            'monthly' => $annual / 12,
            'annual' => $annual,
            'storedAnnual' => $stored > 0 ? $stored : null,
            'storedAnnualDiffers' => $storedDiffers,
            'annualPerFte' => $hourly * self::BILLABLE_HOURS_PER_FTE,
            // Build-up behind the agreed rate, for the methodology page.
            'baseWage' => (float) $pricing->base_wage,
            'employerBurden' => (float) $pricing->employer_burden,
            'workforceMaintenanceCost' => (float) $pricing->workforce_maintenance_cost,
            'costToDeliver' => (float) $pricing->cost_to_deliver,
            'profitMargin' => (float) $pricing->profit_margin,
            'calculationVersion' => $pricing->calculation_version,
        ];
    }

    /**
     * Capital recovery from the two real columns. Negative when the agreed price
     * sits above the buyer's internal cost, which the report states plainly
     * rather than hiding behind a floor at zero.
     *
     * @param  array<string, mixed>  $internal
     * @param  array<string, mixed>  $vendor
     * @param  array<string, float|int>  $coverage
     * @return array<string, float|int>
     */
    private function recovery(array $internal, array $vendor, array $coverage): array
    {
        $internalAnnual = (float) $internal['annual'];
        $vendorAnnual = (float) $vendor['annual'];
        $annual = $internalAnnual - $vendorAnnual;
        $internalMonthly = $internalAnnual / 12;

        return [
            'perHour' => (float) $internal['hourly'] - (float) $vendor['hourly'],
            'annual' => $annual,
            'monthly' => $annual / 12,
            'pct' => $internalAnnual > 0 ? 100 * $annual / $internalAnnual : 0.0,
            // Months of the buyer's own run rate that the agreed annual price
            // consumes: the payback figure the master estimate reports.
            'paybackMonths' => $internalMonthly > 0.01 ? $vendorAnnual / $internalMonthly : 0.0,
            'positive' => $annual > 0,
        ];
    }

    /**
     * Reconciliation against the approved budget. The report must agree with the
     * figure the buyer had funding for, or say by how much it does not.
     *
     * @param  array<string, float|int>  $coverage
     * @return array<string, mixed>
     */
    private function budget(PrivateEstimate $estimate, float $vendorAnnual, array $coverage): array
    {
        $amount = (float) ($estimate->budget_amount ?? 0);
        $basis = $estimate->budget_basis ?: 'annual';
        $status = $estimate->budget_status ?: 'unknown';

        $annual = match ($basis) {
            'hourly' => $amount * (float) $coverage['annualHours'],
            'weekly' => $amount * (float) $coverage['weeksPerYear'],
            'monthly' => $amount * 12,
            default => $amount,
        };

        $known = $amount > 0;
        $variance = $annual - $vendorAnnual;

        return [
            'known' => $known,
            'status' => $status,
            'statusLabel' => match ($status) {
                'approved' => 'Budget approved',
                'pending' => 'Budget pending approval',
                'not_approved' => 'Budget not approved',
                default => 'Budget not stated',
            },
            'approved' => $status === 'approved',
            'amount' => $amount,
            'basis' => $basis,
            'basisLabel' => match ($basis) {
                'hourly' => 'per hour',
                'weekly' => 'per week',
                'monthly' => 'per month',
                default => 'per year',
            },
            'annual' => $annual,
            'approvedAt' => $estimate->budget_approved_at,
            'variance' => $known ? $variance : null,
            'variancePct' => $known && $annual > 0 ? 100 * $variance / $annual : null,
            'withinBudget' => $known ? $variance >= 0 : null,
            'reconciles' => $known && $variance >= 0,
            'statement' => $this->budgetStatement($known, $status, $annual, $vendorAnnual, $variance),
        ];
    }

    private function budgetStatement(bool $known, string $status, float $annual, float $vendorAnnual, float $variance): string
    {
        if (! $known) {
            return 'No approved budget amount was recorded at intake, so this estimate is presented without a budget reconciliation.';
        }

        $noun = $status === 'approved' ? 'approved budget' : 'stated budget';

        if ($variance >= 0) {
            return 'The agreed annual cost sits within the '.$noun.', leaving '.$this->plain($variance).' of the '.$this->plain($annual).' unspent.';
        }

        return 'The agreed annual cost exceeds the '.$noun.' of '.$this->plain($annual).' by '.$this->plain(abs($variance)).'. The scope or the budget has to move before this estimate can be accepted.';
    }

    /**
     * Who approved what. The report is only a statement of an agreed rate when
     * both sides signed off the scope it was priced from.
     *
     * @return array<string, mixed>
     */
    private function agreement(PrivateEstimate $estimate, EstimateScope $scope, EstimatePricing $pricing): array
    {
        $buyerApproved = $scope->buyer_approved_at;
        $vendorApproved = $scope->vendor_approved_at;

        return [
            'scopeVersion' => (int) $scope->version,
            'buyerApprovedAt' => $buyerApproved,
            'vendorApprovedAt' => $vendorApproved,
            'pricedAt' => $estimate->priced_at ?? $pricing->created_at,
            'validUntil' => $estimate->valid_until,
            'agreed' => $buyerApproved !== null && $vendorApproved !== null,
            'statement' => $buyerApproved !== null && $vendorApproved !== null
                ? 'Scope version '.$scope->version.' was approved by both the buyer and the vendor. The rate below is the agreed rate for that scope.'
                : 'Scope version '.$scope->version.' is not yet approved by both parties. The rate below is provisional until it is.',
        ];
    }

    /** @return array<string, mixed> */
    private function parties(PrivateEstimate $estimate): array
    {
        $vendor = $estimate->vendor;

        return [
            'vendorName' => $vendor?->company
                ?: $vendor?->vendorProfile?->company_name
                ?: ($vendor?->name ?? 'Security vendor'),
            'vendorContact' => $vendor?->name,
            'vendorEmail' => $vendor?->email,
            'vendorPhone' => $vendor?->phone,
            'buyerCompany' => $estimate->company_name,
            'buyerContact' => $estimate->buyer_name,
            'buyerTitle' => $estimate->buyer_title,
            'buyerEmail' => $estimate->buyer_email,
            'siteName' => $estimate->site_name,
            'serviceAddress' => $estimate->service_address,
            'propertyType' => $estimate->property_type,
        ];
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /** Plain money for prose, independent of the PDF's formatter. */
    private function plain(float $value): string
    {
        return \App\Support\Currency::format($value, 0);
    }
}
