<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

/**
 * GASQ Workforce-to-Post™ TCO derivation for the Budget / Workforce calculator.
 *
 * This owns the proprietary formula — the fringe/paid-hours/billable-hours
 * constants and the wage → loaded-wage → internal-TCO → vendor-TCO chain — so
 * none of it ships to the browser. The page posts the baseline wage + coverage
 * scope and renders only the returned figures.
 *
 * Mirrors the prior client-side math in calcBudget() and refreshAppraisal()
 * exactly so displayed numbers are unchanged.
 */
class BudgetTcoEngine
{
    private const EMPLOYER_FRINGE_FACTOR = 0.70;
    private const PAID_HOURS_PER_FTE = 3744;
    private const BILLABLE_HOURS_PER_FTE = 1456;
    private const VENDOR_DISCOUNT_FACTOR = 0.70;
    private const OT_MULTIPLIER = 1.5;

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, float>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);

        $baselineWage = max(0, (float) Arr::get($meta, 'baselineWage', 0));

        // Coverage scope → annual hours (clamped exactly as the UI did).
        $hoursPerDay   = min(24,  max(1, ((float) Arr::get($meta, 'hoursPerDay', 0))   ?: 24));
        $daysPerWeek   = min(7,   max(1, ((float) Arr::get($meta, 'daysPerWeek', 0))   ?: 7));
        $weeksPerYear  = min(52,  max(1, ((float) Arr::get($meta, 'weeksPerYear', 0))  ?: 52));
        $staffPerShift = min(100, max(1, ((float) Arr::get($meta, 'staffPerShift', 0)) ?: 1));
        $annualHours   = $hoursPerDay * $daysPerWeek * $weeksPerYear * $staffPerShift;

        // The GASQ formula: baseline wage → loaded wage → Vendor True Cost to
        // Deliver Protection (annual workforce cost / billable hours), then the
        // Buyer True Cost to Protect = vendor cost / fringe factor.
        // baseline wage → loaded wage → buyer internal TCO → vendor TCO (× discount).
        $loadedWage = $baselineWage > 0 ? $baselineWage / self::EMPLOYER_FRINGE_FACTOR : 0;
        $internalTcoHourly = $loadedWage > 0 ? ($loadedWage * self::PAID_HOURS_PER_FTE) / self::BILLABLE_HOURS_PER_FTE : 0;
        $vendorTcoHourly = $internalTcoHourly * self::VENDOR_DISCOUNT_FACTOR;
        $capitalRecoveryPerHour = $internalTcoHourly - $vendorTcoHourly;

        $total = $internalTcoHourly * $annualHours;               // buyer's annual TCO
        $vendorOfferTotal = $vendorTcoHourly * $annualHours;      // vendor annual
        $capitalRecoveryAnnual = $total - $vendorOfferTotal;

        // Appraisal-table extras. Staff required = operating-week coverage hours
        // ÷ a guard's weekly billable hours (1456/52 = 28), rounded UP.
        $weeklyHours = $hoursPerDay * $daysPerWeek * $staffPerShift;
        $ftesRequired = $weeklyHours > 0 ? max(1, (int) ceil($weeklyHours / (self::BILLABLE_HOURS_PER_FTE / 52))) : 0;
        $operationalCapitalPct = $total > 0 ? (int) round(100 * $capitalRecoveryAnnual / $total) : 0;
        $totalMonthlyInt = $total / 12;
        $paybackMonths = $totalMonthlyInt > 0.01 ? round($vendorOfferTotal / $totalMonthlyInt, 1) : 0;

        return [
            'annualHours' => round($annualHours, 2),
            'loadedWage' => round($loadedWage, 2),
            'internalTcoHourly' => round($internalTcoHourly, 2),
            'vendorTcoHourly' => round($vendorTcoHourly, 2),
            'capitalRecoveryPerHour' => round($capitalRecoveryPerHour, 2),
            'total' => round($total, 2),
            'vendorOfferTotal' => round($vendorOfferTotal, 2),
            'capitalRecoveryAnnual' => round($capitalRecoveryAnnual, 2),
            // appraisal extras
            'ftesRequired' => $ftesRequired,
            'operationalCapitalPct' => $operationalCapitalPct,
            'paybackMonths' => $paybackMonths,
            'internalOt' => round($internalTcoHourly * self::OT_MULTIPLIER, 2),
            'vendorOt' => round($vendorTcoHourly * self::OT_MULTIPLIER, 2),
            'annualPerInt' => round($internalTcoHourly * self::BILLABLE_HOURS_PER_FTE, 2),
            'annualPerVend' => round($vendorTcoHourly * self::BILLABLE_HOURS_PER_FTE, 2),
        ];
    }
}
