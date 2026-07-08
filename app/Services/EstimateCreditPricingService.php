<?php

namespace App\Services;

/**
 * GASQ Procurement Credit™ pricing for estimate submissions.
 *
 * The credit cost of submitting an estimate scales with the project (contract)
 * value shown on the estimate, per the Procurement Credit Program ladder:
 *
 *   < $100K      → 1      $1M – $2.5M  → 15
 *   $100K–$250K  → 2      $2.5M – $5M  → 25
 *   $250K–$500K  → 4      $5M – $10M   → 40
 *   $500K – $1M  → 8      $10M+        → 60
 */
class EstimateCreditPricingService
{
    /**
     * Credits required to submit an estimate for a given project value.
     */
    public function creditsFor(?float $projectValue): int
    {
        $value = max(0.0, (float) ($projectValue ?? 0));

        return match (true) {
            $value >= 10_000_000 => 60,
            $value >= 5_000_000 => 40,
            $value >= 2_500_000 => 25,
            $value >= 1_000_000 => 15,
            $value >= 500_000 => 8,
            $value >= 250_000 => 4,
            $value >= 100_000 => 2,
            default => 1,
        };
    }

    /**
     * Pull the headline "project value" out of an estimate snapshot's totals,
     * using the same selection rule the estimate PDF/preview use so the charge
     * matches the figure the buyer sees.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public function projectValueFromSnapshot(array $snapshot): float
    {
        $totals = $snapshot['totals'] ?? [];
        if (! is_array($totals) || $totals === []) {
            return 0.0;
        }

        $headline = null;
        foreach ($totals as $row) {
            $label = strtolower((string) (is_array($row) ? ($row['label'] ?? '') : ''));
            if (in_array($label, ['total', 'annual total', 'total annual cost', 'grand total'], true)) {
                $headline = is_array($row) ? ($row['value'] ?? null) : null;
                break;
            }
        }

        if ($headline === null) {
            $last = end($totals);
            $headline = is_array($last) ? ($last['value'] ?? null) : null;
        }

        return $this->parseAmount($headline);
    }

    /**
     * Parse a totals value ("$650,000.00", "650000", 650000) into a float.
     */
    private function parseAmount(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return 0.0;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
