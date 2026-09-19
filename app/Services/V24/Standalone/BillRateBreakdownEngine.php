<?php

namespace App\Services\V24\Standalone;

/**
 * GASQ Bill Rate Breakdown — seven-layer pricing and five-year forecast engine.
 *
 * Implements the "Final Developer Implementation Plan" (Sep 19, 2026):
 *   Layer 1 Direct Labor · 2 Employer Burden · 3 Workforce Maintenance (baseline
 *   Final Bill Rate) · 4 Other Direct Costs · 5 G&A · 6 Profit (margin gross-up)
 *   · 7 Five-Year Plan (forecast only, never an extra charge).
 *
 * Every value is returned at full precision; rounding is the UI/report's job.
 * Later layers are never computed from rounded display values.
 *
 * Pricing modes:
 *   approved — the Layer 3 rate (or an authorised override) IS the Final Bill Rate;
 *              Layers 4-6 are shown as embedded allocations, never added again.
 *   buildup  — Layer 3 rate is the labor subtotal; ODC and G&A are added, then the
 *              total is grossed up for profit margin.
 */
class BillRateBreakdownEngine
{
    public const HOURS_PER_WEEK_24_7 = 168;

    public const POSITION_TYPES = ['core', 'relief', 'supervision', 'specialized', 'support', 'custom'];

    public const ODC_BASES = ['hour', 'employee', 'post', 'month', 'year', 'contract'];

    public const GA_METHODS = ['pct', 'hourly', 'annual'];

    /** @return array<string, mixed> the approved GASQ baseline scenario */
    public static function defaults(): array
    {
        $burdenItems = [
            ['key' => 'socialSecurity', 'label' => 'Social Security (FICA)'],
            ['key' => 'medicare', 'label' => 'Medicare'],
            ['key' => 'futa', 'label' => 'FUTA'],
            ['key' => 'suta', 'label' => 'SUTA'],
            ['key' => 'workersComp', 'label' => 'Workers Compensation'],
            ['key' => 'healthBenefits', 'label' => 'Employer-paid Health Benefits'],
            ['key' => 'retirement', 'label' => 'Retirement Contribution'],
            ['key' => 'lifeDisability', 'label' => 'Life / Disability Benefits'],
            ['key' => 'vacation', 'label' => 'Paid Vacation / PTO Cost'],
            ['key' => 'holidays', 'label' => 'Paid Holidays'],
            ['key' => 'sickLeave', 'label' => 'Paid Sick Leave'],
            ['key' => 'trainingComp', 'label' => 'Training Compensation'],
            ['key' => 'breakComp', 'label' => 'Paid Lunch / Rest-Break Compensation'],
            ['key' => 'otherBenefit', 'label' => 'Other Employer-Paid Labor Benefit'],
        ];

        $wmcCategories = [
            ['key' => 'vacation', 'label' => 'Vacation / PTO replacement coverage'],
            ['key' => 'sick', 'label' => 'Sick / call-off coverage'],
            ['key' => 'holiday', 'label' => 'Holiday coverage'],
            ['key' => 'training', 'label' => 'Training replacement coverage'],
            ['key' => 'absenteeism', 'label' => 'Absenteeism'],
            ['key' => 'turnover', 'label' => 'Turnover / vacancy coverage'],
            ['key' => 'breaks', 'label' => 'Paid lunch / rest-break coverage'],
            ['key' => 'emergency', 'label' => 'Emergency replacement coverage'],
            ['key' => 'unbillableOt', 'label' => 'Unbillable overtime'],
            ['key' => 'other', 'label' => 'Other unavailable / non-protective time'],
        ];

        $odc = [
            ['key' => 'uniforms', 'label' => 'Uniforms', 'optional' => false],
            ['key' => 'licensing', 'label' => 'Licensing / Permits', 'optional' => false],
            ['key' => 'backgroundChecks', 'label' => 'Background Checks', 'optional' => false],
            ['key' => 'drugScreening', 'label' => 'Drug Screening', 'optional' => false],
            ['key' => 'trainingCerts', 'label' => 'Training / Certifications', 'optional' => false],
            ['key' => 'radios', 'label' => 'Radios / Communications', 'optional' => false],
            ['key' => 'mobileDevices', 'label' => 'Mobile Devices', 'optional' => false],
            ['key' => 'reportingTech', 'label' => 'Reporting Technology', 'optional' => false],
            ['key' => 'dedicatedSupervisor', 'label' => 'Dedicated Supervisor', 'optional' => false],
            ['key' => 'vehicle', 'label' => 'Vehicle', 'optional' => true],
            ['key' => 'fuel', 'label' => 'Fuel', 'optional' => true],
            ['key' => 'vehicleMaintenance', 'label' => 'Vehicle Maintenance', 'optional' => true],
            ['key' => 'firearms', 'label' => 'Firearms', 'optional' => true],
            ['key' => 'ammunition', 'label' => 'Ammunition', 'optional' => true],
            ['key' => 'bodyArmor', 'label' => 'Body Armor', 'optional' => true],
            ['key' => 'k9', 'label' => 'K-9 Services', 'optional' => true],
            ['key' => 'siteEquipment', 'label' => 'Site-Specific Equipment', 'optional' => true],
            ['key' => 'otherOdc', 'label' => 'Other Direct Cost', 'optional' => true],
        ];

        $ga = [
            ['key' => 'corporateAdmin', 'label' => 'Corporate administration'],
            ['key' => 'hr', 'label' => 'Human resources'],
            ['key' => 'recruitingAdmin', 'label' => 'Recruiting administration'],
            ['key' => 'payrollAdmin', 'label' => 'Payroll administration'],
            ['key' => 'accounting', 'label' => 'Accounting / finance'],
            ['key' => 'legal', 'label' => 'Legal / compliance'],
            ['key' => 'corporateTech', 'label' => 'Corporate technology'],
            ['key' => 'office', 'label' => 'Office expense'],
            ['key' => 'branch', 'label' => 'Branch management'],
            ['key' => 'corporateInsurance', 'label' => 'Corporate insurance not already included elsewhere'],
            ['key' => 'qa', 'label' => 'Quality assurance'],
            ['key' => 'contractAdmin', 'label' => 'Contract administration'],
            ['key' => 'contingency', 'label' => 'Operating contingency'],
            ['key' => 'otherGa', 'label' => 'Other G&A'],
        ];

        return [
            'pricingMode' => 'approved',
            'coverage' => [
                'mode' => 'annual',
                'annualHours' => 40500,
                'shiftLength' => 8,
                'guardsPerShift' => 1,
                'shiftsPerDay' => 3,
                'daysPerWeek' => 7,
                'weeksPerYear' => 52,
            ],
            'workforce' => [
                'paidHoursPerEmployee' => 2080,
                'availableHoursPerEmployee' => 1456,
                'wmcHoursOverride' => null,
                'wmcOverrideReason' => '',
            ],
            'positions' => [
                ['name' => 'Primary Post Officers', 'type' => 'core', 'employees' => 18, 'weeklyPaidHours' => 720, 'hourlyWage' => 25.20, 'countsTowardCoverage' => true, 'status' => 'active', 'notes' => ''],
                ['name' => 'Lead Officers / Site Supervisors', 'type' => 'supervision', 'employees' => 5, 'weeklyPaidHours' => 200, 'hourlyWage' => 28.14, 'countsTowardCoverage' => true, 'status' => 'active', 'notes' => ''],
                ['name' => 'Relief / Float Officers', 'type' => 'relief', 'employees' => 5, 'weeklyPaidHours' => 200, 'hourlyWage' => 28.14, 'countsTowardCoverage' => true, 'status' => 'active', 'notes' => ''],
            ],
            'compensation' => ['localityPay' => 0, 'healthWelfareCash' => 0, 'shiftDifferential' => 0],
            'burden' => [
                'method' => 'ratio',
                'wageSharePct' => 70,
                'items' => array_map(fn ($i) => $i + ['method' => 'pct', 'value' => 0], $burdenItems),
            ],
            'wmcCategories' => array_map(fn ($c) => $c + ['hours' => 0], $wmcCategories),
            'finalRateOverride' => ['value' => null, 'reason' => ''],
            'odc' => array_map(fn ($o) => $o + ['enabled' => false, 'basis' => 'hour', 'amount' => 0], $odc),
            'ga' => array_map(fn ($g) => $g + ['enabled' => false, 'method' => 'pct', 'value' => 0], $ga),
            'profit' => ['marginPct' => 15],
            'forecast' => [
                'termYears' => 5,
                'escalationMode' => 'fixed',
                'wageEscPct' => 3,
                'rateEscPct' => 3,
                'years' => [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $scenario  full scenario; inputs live under meta.brb
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $in = $this->resolveInputs((array) data_get($scenario, 'meta.brb', []));
        $warnings = [];

        // ── Coverage ────────────────────────────────────────────────────────
        $cov = $in['coverage'];
        $weeks = $this->pos($cov['weeksPerYear'], 'Weeks per year', $warnings) ?: 52.0;
        $scheduleHours = $this->pos($cov['shiftLength'], 'Shift length', $warnings)
            * $this->pos($cov['guardsPerShift'], 'Guards per shift', $warnings)
            * $this->pos($cov['shiftsPerDay'], 'Shifts per day', $warnings)
            * $this->pos($cov['daysPerWeek'], 'Days per week', $warnings)
            * $weeks;
        $annualHoursInput = $this->pos($cov['annualHours'], 'Annual protective hours', $warnings);
        $scheduleMode = $cov['mode'] === 'schedule';
        $annualHours = $scheduleMode ? $scheduleHours : $annualHoursInput;
        if ($scheduleMode && $annualHoursInput > 0 && abs($annualHoursInput - $scheduleHours) > 0.5) {
            $warnings[] = $this->warn('schedule_mismatch', 'warning',
                'Annual hours entered (' . number_format($annualHoursInput) . ') differ from the selected shift schedule ('
                . number_format($scheduleHours) . '). The schedule total is being used.');
        }

        // ── Workforce availability & staffing ──────────────────────────────
        $wf = $in['workforce'];
        $paid = $this->pos($wf['paidHoursPerEmployee'], 'Paid hours per employee', $warnings);
        $available = $this->pos($wf['availableHoursPerEmployee'], 'Available protective hours per employee', $warnings);
        if ($available > $paid && $paid > 0) {
            $warnings[] = $this->warn('available_exceeds_paid', 'error', 'Available protective hours per employee cannot exceed paid hours per employee.');
        }
        $wmcCalculated = max(0.0, $paid - $available);
        $wmcOverridden = is_numeric($wf['wmcHoursOverride']) && (float) $wf['wmcHoursOverride'] >= 0;
        $wmcHours = $wmcOverridden ? (float) $wf['wmcHoursOverride'] : $wmcCalculated;
        if ($wmcOverridden && trim((string) $wf['wmcOverrideReason']) === '') {
            $warnings[] = $this->warn('override_reason', 'warning', 'WMC hours override needs a reason before it can be treated as authorised.');
        }

        $availablePerWeek = $weeks > 0 ? $available / $weeks : 0.0;
        $manpowerPerPost = $availablePerWeek > 0 ? self::HOURS_PER_WEEK_24_7 / $availablePerWeek : 0.0;
        $staffing = $this->staffing($annualHours, $available, $weeks);

        // ── Layer 1 — Direct labor ──────────────────────────────────────────
        $positions = [];
        $totalPayroll = 0.0;
        $totalAnnualPaid = 0.0;
        $totalEmployees = 0.0;
        $totalWeekly = 0.0;
        $coverageEmployees = 0.0;
        $hasSupervisionPosition = false;
        foreach ($in['positions'] as $i => $p) {
            if (($p['status'] ?? 'active') === 'inactive') {
                continue;
            }
            $label = trim((string) ($p['name'] ?? '')) ?: 'Position ' . ($i + 1);
            $employees = $this->pos($p['employees'] ?? 0, $label . ' employees', $warnings);
            $weekly = $this->pos($p['weeklyPaidHours'] ?? 0, $label . ' weekly hours', $warnings);
            $wage = $this->pos($p['hourlyWage'] ?? 0, $label . ' wage', $warnings);
            $annualPaidHours = $weekly * $weeks;
            $payroll = $annualPaidHours * $wage;
            $type = in_array($p['type'] ?? '', self::POSITION_TYPES, true) ? $p['type'] : 'custom';
            $counts = (bool) ($p['countsTowardCoverage'] ?? ($type !== 'support'));
            if ($type === 'supervision') {
                $hasSupervisionPosition = true;
            }

            $totalPayroll += $payroll;
            $totalAnnualPaid += $annualPaidHours;
            $totalEmployees += $employees;
            $totalWeekly += $weekly;
            if ($counts) {
                $coverageEmployees += $employees;
            }
            $positions[] = [
                'name' => $label,
                'type' => $type,
                'employees' => $employees,
                'weeklyPaidHours' => $weekly,
                'hourlyWage' => $wage,
                'countsTowardCoverage' => $counts,
                'annualPaidHours' => $annualPaidHours,
                'annualPayroll' => $payroll,
                'protectiveCapacity' => $counts ? $employees * $available : 0.0,
            ];
        }
        $baseWeightedWage = $totalAnnualPaid > 0 ? $totalPayroll / $totalAnnualPaid : 0.0;

        $comp = $in['compensation'];
        $locality = $this->pos($comp['localityPay'], 'Locality pay', $warnings);
        $hwCash = $this->pos($comp['healthWelfareCash'], 'Health & Welfare cash', $warnings);
        $shiftDiff = $this->pos($comp['shiftDifferential'], 'Shift differential', $warnings);
        $compensationAdders = $locality + $hwCash + $shiftDiff;
        $weightedWage = $baseWeightedWage + $compensationAdders;

        $positionCapacity = $coverageEmployees * $available;
        if ($positions && $positionCapacity + 0.0001 < $annualHours) {
            $warnings[] = $this->warn('capacity_shortage', 'error',
                'Protective capacity from positions (' . number_format($positionCapacity) . ' hrs) is below the required '
                . number_format($annualHours) . ' contract hours.');
        }
        if ($positions && $coverageEmployees < $staffing['roundedHeadcount']) {
            $warnings[] = $this->warn('headcount_below_minimum', 'error',
                'Coverage headcount (' . $this->num($coverageEmployees) . ') is below the calculated minimum of '
                . $staffing['roundedHeadcount'] . ' employees.');
        }

        // ── Layer 2 — Employer burden ──────────────────────────────────────
        $burden = $in['burden'];
        $burdenLines = [];
        $healthBenefitsHourly = 0.0;
        if ($burden['method'] === 'lineItems') {
            $burdenTotal = 0.0;
            foreach ($burden['items'] as $item) {
                $value = $this->pos($item['value'] ?? 0, $item['label'] ?? 'Burden item', $warnings);
                $hourly = ($item['method'] ?? 'pct') === 'hourly' ? $value : $weightedWage * $value / 100;
                $burdenTotal += $hourly;
                if (($item['key'] ?? '') === 'healthBenefits') {
                    $healthBenefitsHourly = $hourly;
                }
                if ($hourly > 0) {
                    $burdenLines[] = ['key' => $item['key'] ?? '', 'label' => $item['label'] ?? '', 'hourly' => $hourly];
                }
            }
            $fullBurden = $weightedWage + $burdenTotal;
        } else {
            $share = (float) $burden['wageSharePct'];
            if ($share <= 0 || $share > 100) {
                $warnings[] = $this->warn('invalid_wage_share', 'error', 'Wage share must be greater than 0% and no more than 100%.');
                $share = 70.0;
            }
            $fullBurden = $weightedWage / ($share / 100);
        }
        $incrementalBurden = $fullBurden - $weightedWage;
        $impliedWageShare = $fullBurden > 0 ? $weightedWage / $fullBurden : 0.0;

        if ($hwCash > 0 && $healthBenefitsHourly > 0) {
            $warnings[] = $this->warn('duplicate_health_welfare', 'error',
                'Health & Welfare is entered as cash in Layer 1 and as employer-paid health benefits in Layer 2. Remove one so it is not charged twice.');
        }

        // ── Layer 3 — Workforce maintenance & baseline Final Bill Rate ──────
        $wmcPerPost = $wmcHours * $manpowerPerPost;
        $wmcValue = $fullBurden * $wmcPerPost;
        $layer3Rate = $paid > 0 ? $wmcValue / $paid : 0.0;
        $costToProtect = $available > 0 ? $wmcValue / $available : 0.0;

        $wmcCategoryTotal = 0.0;
        $wmcCategories = [];
        foreach ($in['wmcCategories'] as $c) {
            $hours = $this->pos($c['hours'] ?? 0, $c['label'] ?? 'WMC category', $warnings);
            $wmcCategoryTotal += $hours;
            $wmcCategories[] = ['key' => $c['key'] ?? '', 'label' => $c['label'] ?? '', 'hours' => $hours];
        }
        if ($wmcCategoryTotal > 0 && abs($wmcCategoryTotal - $wmcHours) > 0.01) {
            $warnings[] = $this->warn('wmc_unreconciled', 'warning',
                'WMC category hours total ' . $this->num($wmcCategoryTotal) . ', not the ' . $this->num($wmcHours)
                . ' WMC hours per employee. Adjust the categories or save an authorised override.');
        }

        // ── Layer 6 inputs (needed by 4-5 when G&A is % of subtotal) ────────
        $margin = (float) $in['profit']['marginPct'] / 100;
        if ($margin < 0 || $margin >= 1) {
            $warnings[] = $this->warn('invalid_margin', 'error', 'Profit margin must be at least 0% and less than 100%.');
            $margin = min(max($margin, 0.0), 0.99);
        }

        $mode = $in['pricingMode'] === 'buildup' ? 'buildup' : 'approved';
        $overrideValue = $in['finalRateOverride']['value'];
        $rateOverridden = $mode === 'approved' && is_numeric($overrideValue) && (float) $overrideValue > 0;
        if ($rateOverridden && trim((string) $in['finalRateOverride']['reason']) === '') {
            $warnings[] = $this->warn('override_reason', 'warning', 'Final Bill Rate override needs a reason before it can be treated as authorised.');
        }

        // Labor base the embedded / additive layers stack on.
        $laborBase = $mode === 'buildup' ? $layer3Rate : $fullBurden;
        $termYears = max(1, min(10, (int) $in['forecast']['termYears']));

        // ── Layer 4 — Other direct costs, normalised to $/hr ────────────────
        $odcLines = [];
        $odcHourly = 0.0;
        $hasSupervisorOdc = false;
        $insuranceOdc = false;
        foreach ($in['odc'] as $o) {
            if (empty($o['enabled'])) {
                continue;
            }
            $amount = $this->pos($o['amount'] ?? 0, $o['label'] ?? 'Other direct cost', $warnings);
            $basis = in_array($o['basis'] ?? '', self::ODC_BASES, true) ? $o['basis'] : 'hour';
            $annual = match ($basis) {
                'hour' => $amount * $annualHours,
                'employee' => $amount * $staffing['roundedHeadcount'],
                'post' => $amount * $staffing['equivalentPosts'],
                'month' => $amount * 12,
                'year' => $amount,
                'contract' => $amount / $termYears,
            };
            $hourly = $annualHours > 0 ? $annual / $annualHours : 0.0;
            $odcHourly += $hourly;
            if (($o['key'] ?? '') === 'dedicatedSupervisor' && $hourly > 0) {
                $hasSupervisorOdc = true;
            }
            if (stripos((string) ($o['label'] ?? ''), 'insurance') !== false && $hourly > 0) {
                $insuranceOdc = true;
            }
            $odcLines[] = ['key' => $o['key'] ?? '', 'label' => $o['label'] ?? '', 'basis' => $basis, 'amount' => $amount, 'annual' => $annual, 'hourly' => $hourly];
        }
        if ($hasSupervisionPosition && $hasSupervisorOdc) {
            $warnings[] = $this->warn('duplicate_supervision', 'error',
                'Supervision is carried as a Layer 1 position and as a Dedicated Supervisor direct cost. Remove one so it is not charged twice.');
        }

        // ── Layer 5 — G&A, normalised to $/hr ───────────────────────────────
        $gaLines = [];
        $gaHourly = 0.0;
        $gaBase = $laborBase + $odcHourly;
        $corporateInsurance = false;
        foreach ($in['ga'] as $g) {
            if (empty($g['enabled'])) {
                continue;
            }
            $value = $this->pos($g['value'] ?? 0, $g['label'] ?? 'G&A item', $warnings);
            $method = in_array($g['method'] ?? '', self::GA_METHODS, true) ? $g['method'] : 'pct';
            $hourly = match ($method) {
                'pct' => $gaBase * $value / 100,
                'hourly' => $value,
                'annual' => $annualHours > 0 ? $value / $annualHours : 0.0,
            };
            $gaHourly += $hourly;
            if (($g['key'] ?? '') === 'corporateInsurance' && $hourly > 0) {
                $corporateInsurance = true;
            }
            $gaLines[] = ['key' => $g['key'] ?? '', 'label' => $g['label'] ?? '', 'method' => $method, 'value' => $value, 'hourly' => $hourly];
        }
        if ($corporateInsurance && $insuranceOdc) {
            $warnings[] = $this->warn('duplicate_insurance', 'warning',
                'Insurance appears both as a contract-specific direct cost and as corporate insurance in G&A. Confirm they cover different policies.');
        }

        // ── Layer 6 & pricing-mode rule ─────────────────────────────────────
        if ($mode === 'buildup') {
            $preProfit = $laborBase + $odcHourly + $gaHourly;
            $finalRate = $preProfit / (1 - $margin);
            $profitHourly = $finalRate - $preProfit;
        } else {
            $finalRate = $rateOverridden ? (float) $overrideValue : $layer3Rate;
            $profitHourly = $finalRate * $margin;
            $preProfit = $finalRate - $profitHourly;
        }
        // Approved: labor is the full-burden cost and Layers 4-6 are carved out of the
        // rate. Build-up: labor is the whole Layer 3 subtotal, so nothing remains.
        $reconLabor = $mode === 'buildup' ? $laborBase : $fullBurden;
        $embeddedTotal = $reconLabor + $odcHourly + $gaHourly + $profitHourly;
        $remaining = $finalRate - $embeddedTotal;
        if ($mode === 'approved' && $remaining < -0.000001) {
            $warnings[] = $this->warn('over_allocated', 'error',
                'Embedded allocations exceed the approved Final Bill Rate. Reduce Layer 4-6 allocations or switch to Full Cost Build-Up Mode.');
        }

        $rateMultiplier = $baseWeightedWage > 0 ? $finalRate / $baseWeightedWage : 0.0;
        foreach ($positions as &$p) {
            $p['wageShare'] = $totalPayroll > 0 ? $p['annualPayroll'] / $totalPayroll : 0.0;
            $p['positionBillRate'] = $p['hourlyWage'] * $rateMultiplier;
        }
        unset($p);

        // ── Capital recovery & contract value ───────────────────────────────
        $croHourly = max($costToProtect - $finalRate, 0.0);
        $premiumHourly = max($finalRate - $costToProtect, 0.0);
        $annualContractValue = $finalRate * $annualHours;

        // ── Layer 7 — Five-year plan ────────────────────────────────────────
        $forecast = $this->forecast($in['forecast'], $termYears, [
            'wage' => $weightedWage,
            'fullBurden' => $fullBurden,
            'finalRate' => $finalRate,
            'costToProtect' => $costToProtect,
            'annualHours' => $annualHours,
            'available' => $available,
            'weeks' => $weeks,
            'margin' => $margin,
        ]);

        return [
            'pricingMode' => $mode,
            'summary' => [
                'weightedWage' => $weightedWage,
                'fullBurden' => $fullBurden,
                'finalBillRate' => $finalRate,
                'costToProtect' => $costToProtect,
                'croHourly' => $croHourly,
                'annualCro' => $croHourly * $annualHours,
                'premiumAboveCtp' => $premiumHourly,
                'annualPremiumAboveCtp' => $premiumHourly * $annualHours,
                'annualContractValue' => $annualContractValue,
                'requiredManpower' => $staffing['roundedHeadcount'],
                'equivalentPosts' => $staffing['equivalentPosts'],
                'manpowerPerPost' => $manpowerPerPost,
                'staffingReserveCapacity' => $staffing['staffingReserveCapacity'],
            ],
            'coverage' => [
                'mode' => $scheduleMode ? 'schedule' : 'annual',
                'annualHours' => $annualHours,
                'scheduleHours' => $scheduleHours,
                'weeksPerYear' => $weeks,
            ],
            'staffing' => $staffing + [
                'paidHoursPerEmployee' => $paid,
                'availableHoursPerEmployee' => $available,
                'availablePerWeek' => $availablePerWeek,
                'manpowerPerPost' => $manpowerPerPost,
            ],
            'layer1' => [
                'positions' => $positions,
                'totalEmployees' => $totalEmployees,
                'coverageEmployees' => $coverageEmployees,
                'positionCapacity' => $positionCapacity,
                'totalWeeklyPaidHours' => $totalWeekly,
                'totalAnnualPaidHours' => $totalAnnualPaid,
                'totalPayroll' => $totalPayroll,
                'baseWeightedWage' => $baseWeightedWage,
                'compensationAdders' => $compensationAdders,
                'weightedWage' => $weightedWage,
                'rateMultiplier' => $rateMultiplier,
            ],
            'layer2' => [
                'method' => $burden['method'] === 'lineItems' ? 'lineItems' : 'ratio',
                'fullBurden' => $fullBurden,
                'incrementalBurden' => $incrementalBurden,
                'wageShare' => $impliedWageShare,
                'lines' => $burdenLines,
            ],
            'layer3' => [
                'wmcHoursCalculated' => $wmcCalculated,
                'wmcHours' => $wmcHours,
                'wmcOverridden' => $wmcOverridden,
                'wmcPerPost' => $wmcPerPost,
                'wmcValue' => $wmcValue,
                'finalBillRate' => $layer3Rate,
                'categories' => $wmcCategories,
                'categoryTotal' => $wmcCategoryTotal,
            ],
            'layer4' => ['hourly' => $odcHourly, 'annual' => $odcHourly * $annualHours, 'lines' => $odcLines, 'treatment' => $mode === 'buildup' ? 'additive' : 'embedded'],
            'layer5' => ['hourly' => $gaHourly, 'annual' => $gaHourly * $annualHours, 'base' => $gaBase, 'lines' => $gaLines, 'treatment' => $mode === 'buildup' ? 'additive' : 'embedded'],
            'layer6' => [
                'marginPct' => $margin,
                'marginDivisor' => 1 - $margin,
                'preProfit' => $preProfit,
                'profitHourly' => $profitHourly,
                'annualProfit' => $profitHourly * $annualHours,
                'treatment' => $mode === 'buildup' ? 'additive' : 'embedded',
            ],
            'reconciliation' => [
                'laborBase' => $reconLabor,
                'odc' => $odcHourly,
                'ga' => $gaHourly,
                'profit' => $profitHourly,
                'embeddedTotal' => $embeddedTotal,
                'finalBillRate' => $finalRate,
                'remaining' => $remaining,
                'rateOverridden' => $rateOverridden,
                'calculatedRate' => $mode === 'buildup' ? $finalRate : $layer3Rate,
            ],
            'forecast' => $forecast,
            'warnings' => $warnings,
        ];
    }

    /** @return array<string, float|int> */
    private function staffing(float $annualHours, float $available, float $weeks): array
    {
        $requiredFte = $available > 0 ? $annualHours / $available : 0.0;
        // Tolerate float noise so exactly-whole FTEs don't round up a person.
        $headcount = (int) ceil($requiredFte - 1e-9);
        $capacity = $headcount * $available;

        return [
            'annualHours' => $annualHours,
            'equivalentPosts' => $weeks > 0 ? $annualHours / (self::HOURS_PER_WEEK_24_7 * $weeks) : 0.0,
            'requiredFte' => $requiredFte,
            'roundedHeadcount' => $headcount,
            'availableCapacity' => $capacity,
            'staffingReserveCapacity' => $capacity - $annualHours,
        ];
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @param  array<string, float>  $y1
     * @return array<string, mixed>
     */
    private function forecast(array $cfg, int $term, array $y1): array
    {
        $custom = ($cfg['escalationMode'] ?? 'fixed') === 'custom';
        $yearsCfg = [];
        foreach ((array) ($cfg['years'] ?? []) as $row) {
            if (isset($row['year'])) {
                $yearsCfg[(int) $row['year']] = (array) $row;
            }
        }

        $rows = [];
        $prev = null;
        for ($year = 1; $year <= $term; $year++) {
            $row = $yearsCfg[$year] ?? [];
            $priceLock = (bool) ($row['priceLock'] ?? false);
            $wageEsc = $year === 1 ? 0.0 : (float) ($custom && isset($row['wageEscPct']) && is_numeric($row['wageEscPct']) ? $row['wageEscPct'] : ($cfg['wageEscPct'] ?? 0)) / 100;
            $rateEsc = $year === 1 || $priceLock ? 0.0 : (float) ($custom && isset($row['rateEscPct']) && is_numeric($row['rateEscPct']) ? $row['rateEscPct'] : ($cfg['rateEscPct'] ?? 0)) / 100;

            if ($prev === null) {
                $wage = $y1['wage'];
                $fullBurden = $y1['fullBurden'];
                $ctp = $y1['costToProtect'];
                $calculatedRate = $y1['finalRate'];
            } else {
                $wage = $prev['wage'] * (1 + $wageEsc);
                $fullBurden = $prev['fullBurden'] * (1 + $wageEsc);
                $ctp = $prev['costToProtect'] * (1 + $wageEsc);
                $calculatedRate = $prev['finalBillRate'] * (1 + $rateEsc);
            }

            $override = $year > 1 && isset($row['rateOverride']) && is_numeric($row['rateOverride']) && (float) $row['rateOverride'] > 0
                ? (float) $row['rateOverride'] : null;
            $rate = $override ?? $calculatedRate;
            $hours = isset($row['annualHours']) && is_numeric($row['annualHours']) && (float) $row['annualHours'] > 0
                ? (float) $row['annualHours'] : $y1['annualHours'];
            $cro = max($ctp - $rate, 0.0);
            $staffing = $this->staffing($hours, $y1['available'], $y1['weeks']);

            $rows[] = $prev = [
                'year' => $year,
                'wageEscalation' => $wageEsc,
                'rateEscalation' => $rateEsc,
                'priceLock' => $priceLock,
                'status' => $override !== null ? 'overridden' : 'calculated',
                'wage' => $wage,
                'fullBurden' => $fullBurden,
                'calculatedRate' => $calculatedRate,
                'finalBillRate' => $rate,
                'annualHours' => $hours,
                'annualContractValue' => $rate * $hours,
                'costToProtect' => $ctp,
                'croHourly' => $cro,
                'annualCro' => $cro * $hours,
                'premiumAboveCtp' => max($rate - $ctp, 0.0),
                'embeddedProfit' => $rate * $y1['margin'] * $hours,
                'staffing' => $staffing,
            ];
        }

        $first = $rows[0];
        $last = $rows[count($rows) - 1];
        $totalHours = array_sum(array_column($rows, 'annualHours'));
        $totalValue = array_sum(array_column($rows, 'annualContractValue'));

        return [
            'termYears' => $term,
            'years' => $rows,
            'totals' => [
                'contractValue' => $totalValue,
                'annualCro' => array_sum(array_column($rows, 'annualCro')),
                'protectiveHours' => $totalHours,
                'averageBillRate' => $totalHours > 0 ? $totalValue / $totalHours : 0.0,
                'embeddedProfit' => array_sum(array_column($rows, 'embeddedProfit')),
                'wageGrowth' => $first['wage'] > 0 ? $last['wage'] / $first['wage'] - 1 : 0.0,
                'rateGrowth' => $first['finalBillRate'] > 0 ? $last['finalBillRate'] / $first['finalBillRate'] - 1 : 0.0,
            ],
        ];
    }

    /**
     * Merge submitted inputs over the approved baseline so partial payloads work.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function resolveInputs(array $raw): array
    {
        $d = self::defaults();
        $out = $d;
        foreach (['coverage', 'workforce', 'compensation', 'finalRateOverride', 'profit'] as $group) {
            $out[$group] = array_merge($d[$group], (array) ($raw[$group] ?? []));
        }
        $out['pricingMode'] = (string) ($raw['pricingMode'] ?? $d['pricingMode']);
        $out['burden'] = array_merge($d['burden'], (array) ($raw['burden'] ?? []));
        $out['forecast'] = array_merge($d['forecast'], (array) ($raw['forecast'] ?? []));
        // Lists are replaced wholesale when supplied (the user may add or remove rows).
        foreach (['positions', 'wmcCategories', 'odc', 'ga'] as $list) {
            if (array_key_exists($list, $raw) && is_array($raw[$list])) {
                $out[$list] = array_values($raw[$list]);
            }
        }
        if (array_key_exists('items', (array) ($raw['burden'] ?? [])) && is_array($raw['burden']['items'])) {
            $out['burden']['items'] = array_values($raw['burden']['items']);
        }

        return $out;
    }

    /** Non-negative number; negative input is rejected (treated as 0) with a warning. */
    private function pos(mixed $value, string $label, array &$warnings): float
    {
        $v = is_numeric($value) ? (float) $value : 0.0;
        if ($v < 0) {
            $warnings[] = $this->warn('negative_value', 'error', $label . ' cannot be negative; it was treated as 0.');

            return 0.0;
        }

        return $v;
    }

    /** @return array{code: string, level: string, message: string} */
    private function warn(string $code, string $level, string $message): array
    {
        return ['code' => $code, 'level' => $level, 'message' => $message];
    }

    private function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2), '0'), '.');
    }
}
