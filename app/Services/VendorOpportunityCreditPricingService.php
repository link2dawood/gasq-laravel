<?php

namespace App\Services;

class VendorOpportunityCreditPricingService
{
    public function creditsFor(?float $estimatedAnnualValue, string $leadTier): int
    {
        $value = max(0, (float) ($estimatedAnnualValue ?? 0));
        $base = match (true) {
            $value >= 500000 => 300,
            $value >= 250000 => 150,
            $value >= 100000 => 75,
            default => 25,
        };

        if ($leadTier === 'a' && $value < 100000) {
            return 50;
        }

        return $base;
    }
}
