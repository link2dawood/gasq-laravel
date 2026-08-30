<?php

namespace App\Support;

/**
 * Keeps two financial measurements apart that the industry routinely conflates
 * (review spec §15/§16, workflow spec RULE 3/4/5).
 *
 *   PRICE VARIANCE            the difference between two vendor prices
 *   CAPITAL RECOVERY          the opportunity found by comparing the BUYER'S own
 *   OPPORTUNITY(tm)           True Cost to Protect against a qualified outsourced
 *                             cost to deliver
 *
 * Vendor A at $40 and Vendor B at $35 is a $5 price variance. It is NOT $5 of buyer
 * savings, and it is NOT capital recovery — neither figure says anything about what the
 * buyer would have spent performing the function itself.
 *
 * Presenting a vendor-to-vendor delta as "savings" is the single easiest way for GASQ to
 * lose credibility with a CFO, so the wrong comparison is refused here rather than left to
 * each caller's discretion.
 */
class PricingComparison
{
    public const LABEL_PRICE_VARIANCE = 'Price Variance';
    public const LABEL_CAPITAL_RECOVERY = 'Capital Recovery Opportunity';

    /**
     * The difference between two vendor prices. Never call this savings (RULE 4).
     *
     * @return array{label:string, amount:float, is_savings:false, explanation:string}
     */
    public static function priceVariance(float $vendorA, float $vendorB): array
    {
        return [
            'label' => self::LABEL_PRICE_VARIANCE,
            'amount' => round(abs($vendorA - $vendorB), 2),
            'is_savings' => false,
            'explanation' => 'The difference between two vendor prices. A vendor-to-vendor '
                . 'comparison does not establish buyer savings or capital recovery, because '
                . 'neither price reflects what this function costs the buyer to perform.',
        ];
    }

    /**
     * Capital Recovery Opportunity, valid only against the buyer's own True Cost to
     * Protect (RULE 5).
     *
     * Returns null when the comparison is not the buyer-side one — a caller holding two
     * vendor prices gets nothing back rather than a plausible-looking wrong number.
     *
     * @param  float  $trueCostToProtect        buyer's own economic cost, per hour
     * @param  float  $qualifiedOutsourcedCost  qualified vendor cost to deliver, per hour
     * @param  float  $annualHours              annual coverage hours, for the annualised figure
     * @return array{label:string, hourly:float, monthly:float, annual:float, explanation:string}|null
     */
    public static function capitalRecovery(
        float $trueCostToProtect,
        float $qualifiedOutsourcedCost,
        float $annualHours = 0.0,
    ): ?array {
        // No buyer-side benchmark means no basis for the claim.
        if ($trueCostToProtect <= 0 || $qualifiedOutsourcedCost <= 0) {
            return null;
        }

        // Outsourcing costs more than doing it in-house: there is no recovery to report.
        if ($qualifiedOutsourcedCost >= $trueCostToProtect) {
            return null;
        }

        $hourly = round($trueCostToProtect - $qualifiedOutsourcedCost, 2);
        $annual = round($hourly * max(0.0, $annualHours), 2);

        return [
            'label' => self::LABEL_CAPITAL_RECOVERY,
            'hourly' => $hourly,
            'monthly' => round($annual / 12, 2),
            'annual' => $annual,
            'explanation' => 'The potential economic opportunity identified by comparing the '
                . 'buyer\'s True Cost to Protect with the qualified outsourced cost of delivering '
                . 'the required protection.',
        ];
    }
}
