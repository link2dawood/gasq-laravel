<?php

namespace App\Services;

use App\Models\MasterInputProfile;
use App\Models\User;

class MasterInputsService
{
    /**
     * V28-aligned defaults for the Inputs tab.
     *
     * Percent fields are stored as decimals (e.g. 0.0765 for 7.65%).
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            // Core controls
            'directLaborWage' => 27.48,
            'annualPaidHoursPerFte' => 1456,
            'annualProductiveCoverageHoursPerFte' => 1456,
            'localityPayPct' => 0.0,
            'shiftDifferentialPct' => 0.0,
            'otHolidayPremiumPct' => 0.08,
            'laborMarketAdjPct' => 0.0,
            'hwCashPerHour' => 4.22,

            // Fringe / Employer burden
            'ficaMedicarePct' => 0.0765,
            'futaPct' => 0.006,
            'sutaPct' => 0.02,
            'workersCompPct' => 0.016,
            'healthWelfarePerHour' => 0.0,
            'vacationPct' => 0.02,
            'paidHolidaysPct' => 0.04,
            'sickLeavePct' => 0.027,
            'donDoffMinutesPerShift' => 15.0,

            // Operations / Support
            'recruitingHiringPct' => 0.0,
            'trainingCertificationPct' => 0.015,
            'uniformsEquipmentPct' => 0.001,
            'fieldSupervisionPct' => 0.0,
            'contractManagementPct' => 0.0,
            'qualityAssurancePct' => 0.0,
            'vehiclesPatrolPct' => 0.02,
            'technologySystemsPct' => 0.01,
            'generalLiabilityPct' => 0.0892,
            'umbrellaInsurancePct' => 0.0075,

            // Corporate & pricing controls
            'adminHrPayrollPct' => 0.0,
            'accountingLegalPct' => 0.0,
            'corporateOverheadPct' => 0.10,
            'gaPct' => 0.08,
            'profitFeePct' => 0.21,
            'vendorTcoFactorVsGovTco' => 0.70,
            'vendorFloorFactorVsVendorTco' => 0.70,
            'minWeeklyHoursForFloorEligibility' => 1500,
            'governmentFullBurdenLaborShare' => 0.70,
            'governmentWorkforceHoursBasis' => 3744,
            'governmentTcoMultiplierMin' => 3.1,
            'governmentTcoMultiplierMax' => 3.9,

            // Vehicle tab drivers (high level)
            'vehiclesRequired' => 1,
            'avgMilesPerVehiclePerDay' => 365,
            'fuelCostPerGallon' => 4.00,
            'customAnnualEscalationPct' => 0.03,
            'lowEscalationPct' => 0.02,
            'mediumEscalationPct' => 0.03,
            'highEscalationPct' => 0.05,
        ];
    }

    /**
     * Get or create the user's profile.
     */
    public function getOrCreate(User $user): MasterInputProfile
    {
        return MasterInputProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['inputs' => $this->defaults(), 'is_complete' => false],
        );
    }

    /**
     * Returns the saved inputs (merged with defaults).
     *
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $p = $this->getOrCreate($user);
        return array_replace($this->defaults(), (array) ($p->inputs ?? []));
    }
}

