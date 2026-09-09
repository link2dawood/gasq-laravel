<?php

namespace App\Services;

use App\Models\User;

/**
 * GASQ Cost to Protect™ estimate metrics.
 *
 * Single source of truth for the numbers behind the master estimate dashboard
 * (pdf.cost-to-protect-estimate) and the Workforce-to-Post allocation report
 * (pdf.workforce-bill-rate-breakdown). Both used to carry their own copy of this
 * math; keeping it here means a vendor-facing figure can never drift between the
 * two documents.
 */
class CostToProtectEstimate
{
    /** Fringe/burden factor: base wage is this share of the fully loaded wage. */
    private const EMPLOYER_FRINGE_FACTOR = 0.70;

    /** Paid hours behind one FTE per year (worked + non-worked). */
    private const PAID_HOURS_PER_FTE = 3744;

    /** Hours of that FTE that are actually billable to a post. */
    private const BILLABLE_HOURS_PER_FTE = 1456;

    private const OT_MULTIPLIER = 1.5;

    /**
     * Build every figure the estimate reports render.
     *
     * @param  array<string, mixed>  $scenario  Calculator scenario (session or CalculatorState).
     * @return array<string, mixed>
     */
    public function build(array $scenario, ?User $user = null): array
    {
        $meta = (array) data_get($scenario, 'meta', []);

        $baselineWage = (float) (data_get($meta, 'baselineWage')
            ?? data_get($meta, 'governmentShouldCostHourly')
            ?? 25.00);

        $scope = (array) data_get($meta, 'scope', []);
        $hoursPerDay = max(0.5, min(24, (float) (data_get($scope, 'hoursOfCoveragePerDay') ?? data_get($meta, 'hoursPerDay') ?? 24)));
        $daysPerWeek = max(1, min(7, (float) (data_get($scope, 'daysOfCoveragePerWeek') ?? data_get($meta, 'daysPerWeek') ?? 7)));
        $weeksPerYear = max(1, min(52, (float) (data_get($scope, 'weeksOfCoverage') ?? data_get($meta, 'weeksPerYear') ?? 52)));
        $staffPerShift = max(1, min(100, (float) (data_get($scope, 'staffPerShift') ?? data_get($meta, 'staffPerShift') ?? 1)));

        $vendorDiscountFactor = (float) config('budget_calculator.vendor_discount_factor', 0.70);

        // ---------- GASQ TCO formula ----------
        $loadedWage = $baselineWage / self::EMPLOYER_FRINGE_FACTOR;
        $annualWorkforceCost = $loadedWage * self::PAID_HOURS_PER_FTE;
        $internalTcoHourly = $annualWorkforceCost / self::BILLABLE_HOURS_PER_FTE;
        $vendorTcoHourly = $internalTcoHourly * $vendorDiscountFactor;

        // Weekly coverage = the operating-week hours across all staff on post, so
        // weekly × weeks-per-year = annual.
        $weeklyCoverageHours = $hoursPerDay * $daysPerWeek * $staffPerShift;
        $monthlyCoverageHours = (int) round(($weeklyCoverageHours * $weeksPerYear) / 12);
        $annualCoverageHours = $weeklyCoverageHours * $weeksPerYear;

        // Staff required = operating-week coverage hours ÷ a guard's weekly
        // billable hours (1456/52 = 28), rounded UP.
        $ftesRequired = max(1, (int) ceil($weeklyCoverageHours / (self::BILLABLE_HOURS_PER_FTE / 52)));

        $totalAnnualInternal = $internalTcoHourly * $annualCoverageHours;
        $totalAnnualVendor = $vendorTcoHourly * $annualCoverageHours;
        $totalMonthlyInternal = $totalAnnualInternal / 12;
        $totalMonthlyVendor = $totalAnnualVendor / 12;
        $annualCapitalRecovery = $totalAnnualInternal - $totalAnnualVendor;
        $combinedAnnual = $totalAnnualInternal + $totalAnnualVendor;

        return [
            // Inputs / assumptions
            'baselineWage' => $baselineWage,
            'hoursPerDay' => $hoursPerDay,
            'daysPerWeek' => $daysPerWeek,
            'weeksPerYear' => $weeksPerYear,
            'staffPerShift' => $staffPerShift,
            'vendorDiscountFactor' => $vendorDiscountFactor,
            // Budget as entered on the calculator (kept for the allocation report)
            'annualBudget' => (float) data_get($meta, 'annualBudget', 0),

            // Coverage
            'weeklyCoverageHours' => $weeklyCoverageHours,
            'monthlyCoverageHours' => $monthlyCoverageHours,
            'annualCoverageHours' => $annualCoverageHours,
            'monthsOfCoverage' => $weeksPerYear * 12 / 52,
            'ftesRequired' => $ftesRequired,

            // Rates
            'internalTcoHourly' => $internalTcoHourly,
            'vendorTcoHourly' => $vendorTcoHourly,
            'internalOtHourly' => $internalTcoHourly * self::OT_MULTIPLIER,
            'vendorOtHourly' => $vendorTcoHourly * self::OT_MULTIPLIER,
            'annualPerInternalFte' => $internalTcoHourly * self::BILLABLE_HOURS_PER_FTE,
            'annualPerVendorFte' => $vendorTcoHourly * self::BILLABLE_HOURS_PER_FTE,

            // Totals
            'totalWeeklyInternal' => $totalAnnualInternal / $weeksPerYear,
            'totalWeeklyVendor' => $totalAnnualVendor / $weeksPerYear,
            'totalMonthlyInternal' => $totalMonthlyInternal,
            'totalMonthlyVendor' => $totalMonthlyVendor,
            'totalAnnualInternal' => $totalAnnualInternal,
            'totalAnnualVendor' => $totalAnnualVendor,
            'combinedAnnual' => $combinedAnnual,

            // Recovery
            'annualCapitalRecovery' => $annualCapitalRecovery,
            'recoveryPct' => $totalAnnualInternal > 0 ? (int) round(100 * $annualCapitalRecovery / $totalAnnualInternal) : 0,
            'paybackMonths' => $totalMonthlyInternal > 0.01 ? round($totalAnnualVendor / $totalMonthlyInternal, 1) : 0.0,
            'internalSharePct' => $combinedAnnual > 0 ? 100 * $totalAnnualInternal / $combinedAnnual : 0.0,
            'vendorSharePct' => $combinedAnnual > 0 ? 100 * $totalAnnualVendor / $combinedAnnual : 0.0,

            // Identity: calculator contact first, signed-in vendor account as fallback.
            'contact' => $this->contact($scenario, $user),
        ];
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, string|null>
     */
    private function contact(array $scenario, ?User $user): array
    {
        $c = (array) data_get($scenario, 'meta.contact', []);

        $address = trim((string) ($c['contactAddress'] ?? '')) ?: ($user?->vendorProfile?->address
            ?? trim(implode(', ', array_filter([$user?->city, $user?->state, $user?->zip_code]))));

        return [
            'name' => trim((string) ($c['contactName'] ?? '')) ?: ($user?->name ?? null),
            'company' => trim((string) ($c['companyName'] ?? '')) ?: ($user?->company ?? ($user?->vendorProfile?->company_name ?? null)),
            'address' => $address ?: null,
            'email' => trim((string) ($c['contactEmail'] ?? '')) ?: ($user?->email ?? null),
            'phone' => trim((string) ($c['contactPhone'] ?? '')) ?: ($user?->phone ?? ($user?->vendorProfile?->phone ?? null)),
            'site' => trim((string) (data_get($scenario, 'meta.siteName') ?? ($c['siteName'] ?? ''))) ?: null,
        ];
    }
}
