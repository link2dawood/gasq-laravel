<?php

namespace App\Services\V24\Standalone;

/**
 * GASQ Seven Layer Pricing Model — calculation engine.
 *
 * Implements the "Developer Implementation Specification", Version 1.0
 * (September 2026):
 *   Layer 1 Direct Labor · 2 Employer Labor Burden · 3 Workforce Maintenance
 *   (baseline Final Bill Rate) · 4 Other Direct Costs · 5 G&A · 6 Profit
 *   (margin gross-up) · 7 Five Year Pricing Plan (forecast only).
 *
 * Every value is returned at full precision; rounding is the UI/report's job,
 * and annual totals are calculated from raw hourly values (spec 11.2).
 *
 * Pricing modes (spec 1.3):
 *   approved — the Layer 3 rate (or an authorised override) IS the Final Bill
 *              Rate; Layers 4-6 are analysed as embedded allocations and can
 *              never be added again.
 *   buildup  — labor, then Layers 4 and 5, then a Layer 6 margin gross-up.
 *
 * Validation codes VAL001-VAL018 follow spec section 14.
 */
class BillRateBreakdownEngine
{
    public const HOURS_PER_WEEK_24_7 = 168;

    public const POSITION_TYPES = ['core', 'relief', 'supervision', 'specialized', 'support', 'custom'];

    public const ODC_BASES = ['hour', 'employee', 'post', 'month', 'year', 'contract'];

    public const GA_METHODS = ['pct', 'hourly', 'annual'];

    /** Layer 4 recovery status: only a recovered vendor item can raise the rate (VAL007). */
    public const RECOVERY_STATUSES = ['recovered', 'absorbed', 'buyer_provided', 'not_applicable'];

    /** @return array<string, mixed> the approved GASQ baseline scenario (spec 2, 3.2, 4.1, 5.1) */
    public static function defaults(): array
    {
        // Spec 4.1 — default 30% employer burden allocation, as a share of the
        // employer full-burden cost. The shares total exactly 30.00%.
        $burdenItems = [
            ['key' => 'socialSecurity', 'label' => 'Employer Social Security', 'method' => 'pct', 'value' => 4.33],
            ['key' => 'medicare', 'label' => 'Employer Medicare', 'method' => 'pct', 'value' => 1.02],
            ['key' => 'futa', 'label' => 'Federal Unemployment Tax', 'method' => 'pct', 'value' => 0.41],
            ['key' => 'suta', 'label' => 'State Unemployment Tax', 'method' => 'pct', 'value' => 1.40],
            ['key' => 'workersComp', 'label' => 'Workers Compensation', 'method' => 'pct', 'value' => 2.09],
            ['key' => 'medical', 'label' => 'Medical Insurance', 'method' => 'pct', 'value' => 12.73, 'healthWelfare' => true],
            ['key' => 'dental', 'label' => 'Dental Insurance', 'method' => 'pct', 'value' => 1.02, 'healthWelfare' => true],
            ['key' => 'vision', 'label' => 'Vision Insurance', 'method' => 'pct', 'value' => 0.31, 'healthWelfare' => true],
            ['key' => 'retirement', 'label' => 'Retirement Contribution', 'method' => 'pct', 'value' => 3.49],
            ['key' => 'lifeInsurance', 'label' => 'Employer Paid Life Insurance', 'method' => 'pct', 'value' => 0.31],
            ['key' => 'disability', 'label' => 'Short and Long Term Disability', 'method' => 'pct', 'value' => 0.99],
            ['key' => 'eapWellness', 'label' => 'Employee Assistance and Wellness', 'method' => 'pct', 'value' => 0.31],
            ['key' => 'hsaFsa', 'label' => 'Employer HSA or FSA Contribution', 'method' => 'pct', 'value' => 1.02, 'healthWelfare' => true],
            ['key' => 'otherBenefit', 'label' => 'Other Documented Benefits', 'method' => 'pct', 'value' => 0.57],
        ];

        // Spec 5.1 — default 624-hour workforce maintenance allocation.
        $wmcCategories = [
            ['key' => 'vacation', 'label' => 'Vacation and Paid Time Off', 'hours' => 120, 'treatment' => 'Replacement capacity'],
            ['key' => 'sick', 'label' => 'Sick Leave', 'hours' => 40, 'treatment' => 'Replacement capacity'],
            ['key' => 'holidays', 'label' => 'Paid Holidays', 'hours' => 80, 'treatment' => 'Replacement capacity'],
            ['key' => 'training', 'label' => 'Training and Recertification', 'hours' => 40, 'treatment' => 'Training replacement'],
            ['key' => 'breaks', 'label' => 'Paid Lunch and Rest Breaks', 'hours' => 104, 'treatment' => 'Relief requirement'],
            ['key' => 'absenteeism', 'label' => 'Absenteeism and Call Offs', 'hours' => 80, 'treatment' => 'Emergency coverage'],
            ['key' => 'turnover', 'label' => 'Turnover Recruiting and Replacement', 'hours' => 80, 'treatment' => 'Continuity capacity'],
            ['key' => 'unbillableOt', 'label' => 'Unbillable Overtime and Coverage Inefficiency', 'hours' => 80, 'treatment' => 'Coverage inefficiency'],
        ];

        // Spec 6 — Layer 4 catalogue. duplicateKey feeds cross-layer duplicate detection (spec 14.1).
        $odc = [
            ['key' => 'uniforms', 'label' => 'Uniforms and Replacements', 'basis' => 'employee', 'optional' => false],
            ['key' => 'licensing', 'label' => 'Licensing and Permits', 'basis' => 'employee', 'optional' => false, 'duplicateKey' => 'permits'],
            ['key' => 'backgroundChecks', 'label' => 'Background Checks', 'basis' => 'employee', 'optional' => false],
            ['key' => 'drugScreening', 'label' => 'Drug Screening', 'basis' => 'employee', 'optional' => false],
            ['key' => 'trainingCerts', 'label' => 'Training and Certifications', 'basis' => 'employee', 'optional' => false],
            ['key' => 'radios', 'label' => 'Radios and Communications', 'basis' => 'month', 'optional' => false, 'duplicateKey' => 'siteTechnology'],
            ['key' => 'mobileDevices', 'label' => 'Mobile Devices and Cellular', 'basis' => 'month', 'optional' => true, 'duplicateKey' => 'siteTechnology'],
            ['key' => 'reportingTech', 'label' => 'Reporting and Guard Tour Technology', 'basis' => 'month', 'optional' => false, 'duplicateKey' => 'siteTechnology'],
            ['key' => 'dedicatedSupervisor', 'label' => 'Dedicated Field Supervision', 'basis' => 'month', 'optional' => false, 'duplicateKey' => 'supervision'],
            ['key' => 'siteSupplies', 'label' => 'Site Supplies', 'basis' => 'month', 'optional' => false],
            ['key' => 'vehicle', 'label' => 'Vehicle Lease or Depreciation', 'basis' => 'month', 'optional' => true],
            ['key' => 'fuel', 'label' => 'Fuel', 'basis' => 'month', 'optional' => true],
            ['key' => 'vehicleMaintenance', 'label' => 'Vehicle Maintenance', 'basis' => 'month', 'optional' => true],
            ['key' => 'vehicleInsurance', 'label' => 'Vehicle Insurance and Registration', 'basis' => 'month', 'optional' => true, 'duplicateKey' => 'insurance'],
            ['key' => 'firearms', 'label' => 'Firearms', 'basis' => 'employee', 'optional' => true],
            ['key' => 'ammunition', 'label' => 'Ammunition and Qualification', 'basis' => 'employee', 'optional' => true],
            ['key' => 'bodyArmor', 'label' => 'Body Armor', 'basis' => 'employee', 'optional' => true],
            ['key' => 'k9', 'label' => 'K9 Services', 'basis' => 'month', 'optional' => true],
            ['key' => 'siteEquipment', 'label' => 'Site Specific Equipment', 'basis' => 'year', 'optional' => true],
            ['key' => 'otherOdc', 'label' => 'Other Direct Cost', 'basis' => 'year', 'optional' => true],
        ];

        // Spec 7 — Layer 5 catalogue.
        $ga = [
            ['key' => 'executive', 'label' => 'Executive and Corporate Management'],
            ['key' => 'hr', 'label' => 'Human Resources'],
            ['key' => 'recruitingAdmin', 'label' => 'Recruiting Administration'],
            ['key' => 'payrollAdmin', 'label' => 'Payroll Administration'],
            ['key' => 'accounting', 'label' => 'Accounting and Finance'],
            ['key' => 'legal', 'label' => 'Legal and Compliance', 'duplicateKey' => 'permits'],
            ['key' => 'corporateTech', 'label' => 'Corporate Technology and Cybersecurity', 'duplicateKey' => 'siteTechnology'],
            ['key' => 'office', 'label' => 'Office and Branch Expense'],
            ['key' => 'branch', 'label' => 'Branch Management', 'duplicateKey' => 'supervision'],
            ['key' => 'corporateInsurance', 'label' => 'Corporate Insurance', 'duplicateKey' => 'insurance'],
            ['key' => 'qa', 'label' => 'Quality Assurance'],
            ['key' => 'contractAdmin', 'label' => 'Contract Administration'],
            ['key' => 'salesMarketing', 'label' => 'Sales and Marketing'],
            ['key' => 'contingency', 'label' => 'Operating Contingency'],
            ['key' => 'otherGa', 'label' => 'Other General and Administrative Cost'],
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
            // Spec 3.1 — cash compensation is carried per position.
            'positions' => [
                ['name' => 'Primary Post Officers', 'type' => 'core', 'employees' => 18, 'weeklyPaidHours' => 720, 'hourlyWage' => 25.20, 'localityPay' => 0, 'healthWelfareCash' => 0, 'shiftDifferential' => 0, 'positionPremium' => 0, 'countsTowardCoverage' => true, 'status' => 'active', 'notes' => ''],
                ['name' => 'Lead Officers', 'type' => 'supervision', 'employees' => 5, 'weeklyPaidHours' => 200, 'hourlyWage' => 28.14, 'localityPay' => 0, 'healthWelfareCash' => 0, 'shiftDifferential' => 0, 'positionPremium' => 0, 'countsTowardCoverage' => true, 'status' => 'active', 'notes' => ''],
                ['name' => 'Relief Officers', 'type' => 'relief', 'employees' => 5, 'weeklyPaidHours' => 200, 'hourlyWage' => 28.14, 'localityPay' => 0, 'healthWelfareCash' => 0, 'shiftDifferential' => 0, 'positionPremium' => 0, 'countsTowardCoverage' => true, 'status' => 'active', 'notes' => ''],
            ],
            'burden' => [
                'method' => 'ratio',
                'wageSharePct' => 70,
                'items' => $burdenItems,
            ],
            'wmcCategories' => $wmcCategories,
            'wmcCategoryOverrideReason' => '',
            'finalRateOverride' => ['value' => null, 'reason' => ''],
            'odc' => array_map(fn ($o) => $o + ['enabled' => false, 'amount' => 0, 'provider' => 'vendor', 'recoveryStatus' => 'recovered'], $odc),
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
        $v = [];

        // ── Coverage ────────────────────────────────────────────────────────
        $cov = $in['coverage'];
        $weeks = $this->pos($cov['weeksPerYear'], 'coverage.weeksPerYear', 'Weeks per year', $v) ?: 52.0;
        $scheduleHours = $this->pos($cov['shiftLength'], 'coverage.shiftLength', 'Shift length', $v)
            * $this->pos($cov['guardsPerShift'], 'coverage.guardsPerShift', 'Guards per shift', $v)
            * $this->pos($cov['shiftsPerDay'], 'coverage.shiftsPerDay', 'Shifts per day', $v)
            * $this->pos($cov['daysPerWeek'], 'coverage.daysPerWeek', 'Days per week', $v)
            * $weeks;
        $annualHoursInput = $this->pos($cov['annualHours'], 'coverage.annualHours', 'Annual protective hours', $v);
        $scheduleMode = $cov['mode'] === 'schedule';
        $annualHours = $scheduleMode ? $scheduleHours : $annualHoursInput;
        if ($scheduleMode && $annualHoursInput > 0 && abs($annualHoursInput - $scheduleHours) > 0.5) {
            // VAL017 — annual hours do not reconcile to the active schedule.
            $v[] = $this->msg('VAL017', 'error', 'coverage.annualHours',
                'Annual hours entered (' . number_format($annualHoursInput) . ') do not reconcile to the active shift schedule ('
                . number_format($scheduleHours) . ' hours).');
        }

        // ── Workforce availability & staffing ──────────────────────────────
        $wf = $in['workforce'];
        $paid = $this->pos($wf['paidHoursPerEmployee'], 'workforce.paidHoursPerEmployee', 'Paid hours per employee', $v);
        $available = $this->pos($wf['availableHoursPerEmployee'], 'workforce.availableHoursPerEmployee', 'Available protective hours per employee', $v);
        $wmcCalculated = max(0.0, $paid - $available);
        $wmcOverridden = is_numeric($wf['wmcHoursOverride']) && (float) $wf['wmcHoursOverride'] >= 0;
        $wmcHours = $wmcOverridden ? (float) $wf['wmcHoursOverride'] : $wmcCalculated;
        $wmcReason = trim((string) $wf['wmcOverrideReason']);

        if ($available > $paid && $paid > 0) {
            $v[] = $this->msg('VAL006', 'error', 'workforce.availableHoursPerEmployee',
                'Available protective hours per employee cannot exceed paid hours per employee.');
        } elseif (abs($paid - ($available + $wmcHours)) > 0.001) {
            // VAL006 — paid hours must equal available + WMC unless an override is authorised.
            $v[] = $wmcOverridden && $wmcReason !== ''
                ? $this->msg('VAL006', 'warning', 'workforce.wmcHoursOverride',
                    'Workforce maintenance hours are overridden to ' . $this->num($wmcHours) . ', so paid hours no longer equal available hours plus maintenance hours.')
                : $this->msg('VAL006', 'error', 'workforce.wmcHoursOverride',
                    'Paid hours (' . $this->num($paid) . ') must equal available protective hours plus workforce maintenance hours ('
                    . $this->num($available + $wmcHours) . ') unless an authorised override is recorded.');
        }
        if ($wmcOverridden && $wmcReason === '') {
            // VAL016 — a manual override lacks a source or explanation.
            $v[] = $this->msg('VAL016', 'warning', 'workforce.wmcOverrideReason',
                'The workforce maintenance hours override needs a reason before it counts as authorised.');
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
        $hwCashEntered = false;
        foreach ($in['positions'] as $i => $p) {
            if (($p['status'] ?? 'active') === 'inactive') {
                continue;
            }
            $label = trim((string) ($p['name'] ?? '')) ?: 'Position ' . ($i + 1);
            $path = 'positions.' . $i;
            $employees = $this->pos($p['employees'] ?? 0, $path . '.employees', $label . ' employees', $v);
            $weekly = $this->pos($p['weeklyPaidHours'] ?? 0, $path . '.weeklyPaidHours', $label . ' weekly hours', $v);
            $wage = $this->pos($p['hourlyWage'] ?? 0, $path . '.hourlyWage', $label . ' wage', $v);
            $locality = $this->pos($p['localityPay'] ?? 0, $path . '.localityPay', $label . ' locality pay', $v);
            $hwCash = $this->pos($p['healthWelfareCash'] ?? 0, $path . '.healthWelfareCash', $label . ' cash health and welfare', $v);
            $shiftDiff = $this->pos($p['shiftDifferential'] ?? 0, $path . '.shiftDifferential', $label . ' shift differential', $v);
            $premium = $this->pos($p['positionPremium'] ?? 0, $path . '.positionPremium', $label . ' position premium', $v);

            // Spec 3.3 — payroll uses total cash wage, not the base wage alone.
            $cashWage = $wage + $locality + $hwCash + $shiftDiff + $premium;
            $annualPaidHours = $weekly * $weeks;
            $payroll = $annualPaidHours * $cashWage;
            $type = in_array($p['type'] ?? '', self::POSITION_TYPES, true) ? $p['type'] : 'custom';
            $counts = (bool) ($p['countsTowardCoverage'] ?? ($type !== 'support'));
            if ($type === 'supervision') {
                $hasSupervisionPosition = true;
            }
            if ($hwCash > 0) {
                $hwCashEntered = true;
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
                'localityPay' => $locality,
                'healthWelfareCash' => $hwCash,
                'shiftDifferential' => $shiftDiff,
                'positionPremium' => $premium,
                'cashWage' => $cashWage,
                'countsTowardCoverage' => $counts,
                'annualPaidHours' => $annualPaidHours,
                'annualPayroll' => $payroll,
                'protectiveCapacity' => $counts ? $employees * $available : 0.0,
            ];
        }
        $weightedWage = $totalAnnualPaid > 0 ? $totalPayroll / $totalAnnualPaid : 0.0;

        $positionCapacity = $coverageEmployees * $available;
        if ($positions && $positionCapacity + 0.0001 < $annualHours) {
            // VAL010 — calculated staffing capacity is below required annual protective hours.
            $v[] = $this->msg('VAL010', 'warning', 'positions',
                'Protective capacity from positions (' . number_format($positionCapacity) . ' hrs) is below the required '
                . number_format($annualHours) . ' contract hours.');
        }
        if ($positions && $coverageEmployees < $staffing['roundedHeadcount']) {
            // VAL011 — entered headcount is below the calculated minimum.
            $v[] = $this->msg('VAL011', 'warning', 'positions',
                'Coverage headcount (' . $this->num($coverageEmployees) . ') is below the calculated minimum of '
                . $staffing['roundedHeadcount'] . ' employees.');
        }

        // ── Layer 2 — Employer labor burden ────────────────────────────────
        $burden = $in['burden'];
        $method = $burden['method'] === 'detailed' ? 'detailed' : 'ratio';
        $wageSharePct = (float) $burden['wageSharePct'];
        if ($wageSharePct <= 0 || $wageSharePct > 100) {
            // VAL002 — wage share must be greater than zero and no more than one.
            $v[] = $this->msg('VAL002', 'error', 'burden.wageSharePct', 'Employer wage share must be greater than 0% and no more than 100%.');
            $wageSharePct = 70.0;
        }
        $configuredBurdenShare = 1 - $wageSharePct / 100;

        // Percentage items are shares OF the employer full-burden cost, so the cost
        // solves as (wage + fixed amounts) / (1 - share total). Spec 4.1.
        $sharePct = 0.0;
        $fixedHourly = 0.0;
        foreach ($burden['items'] as $j => $item) {
            $value = $this->pos($item['value'] ?? 0, 'burden.items.' . $j . '.value', ($item['label'] ?? 'Burden item'), $v);
            if (($item['method'] ?? 'pct') === 'hourly') {
                $fixedHourly += $value;
            } else {
                $sharePct += $value;
            }
        }
        if ($method === 'detailed' && $sharePct >= 100) {
            $v[] = $this->msg('VAL003', 'error', 'burden.items', 'Employer burden percentages cannot total 100% or more of the employer cost.');
            $sharePct = 0.0;
        }

        $fullBurden = $method === 'detailed'
            ? ($weightedWage + $fixedHourly) / (1 - $sharePct / 100)
            : $weightedWage / ($wageSharePct / 100);

        $burdenLines = [];
        $healthBenefitHourly = 0.0;
        $detailedTotal = 0.0;
        foreach ($burden['items'] as $item) {
            $value = max(0.0, (float) ($item['value'] ?? 0));
            $hourly = ($item['method'] ?? 'pct') === 'hourly' ? $value : $fullBurden * $value / 100;
            $detailedTotal += $hourly;
            if (! empty($item['healthWelfare'])) {
                $healthBenefitHourly += $hourly;
            }
            $burdenLines[] = [
                'key' => $item['key'] ?? '',
                'label' => $item['label'] ?? '',
                'method' => ($item['method'] ?? 'pct') === 'hourly' ? 'hourly' : 'pct',
                'value' => $value,
                'share' => $fullBurden > 0 ? $hourly / $fullBurden : 0.0,
                'hourly' => $hourly,
            ];
        }
        $incrementalBurden = $fullBurden - $weightedWage;
        $impliedWageShare = $fullBurden > 0 ? $weightedWage / $fullBurden : 0.0;
        $impliedBurdenShare = 1 - $impliedWageShare;

        if ($method === 'detailed' && abs($impliedBurdenShare - $configuredBurdenShare) > 0.0001) {
            // VAL003 — detailed percentages must reconcile to the configured burden share.
            $v[] = $this->msg('VAL003', 'error', 'burden.items',
                'Layer 2 line items total ' . $this->pct($impliedBurdenShare) . ' of the employer cost, not the configured '
                . $this->pct($configuredBurdenShare) . ' burden share.');
        }
        if ($method === 'ratio' && abs($detailedTotal - $incrementalBurden) > 0.005) {
            // VAL012 — a displayed layer share does not reconcile.
            $v[] = $this->msg('VAL012', 'warning', 'burden.items',
                'The Layer 2 breakdown totals ' . $this->money($detailedTotal) . '/hr against an employer burden of '
                . $this->money($incrementalBurden) . '/hr. The breakdown is shown for analysis only.');
        }
        if ($hwCashEntered && $healthBenefitHourly > 0) {
            // VAL004 — cash and employer benefit cannot duplicate the same health and welfare amount.
            $v[] = $this->msg('VAL004', 'error', 'burden.items',
                'Health and welfare is entered as cash in Layer 1 and as an employer health benefit in Layer 2. Remove one unless a documented split exists.');
        }

        // ── Layer 3 — Workforce maintenance & baseline Final Bill Rate ──────
        $wmcPerPost = $wmcHours * $manpowerPerPost;
        $wmcValue = $fullBurden * $wmcPerPost;
        $layer3Rate = $paid > 0 ? $wmcValue / $paid : 0.0;
        $costToProtect = $available > 0 ? $wmcValue / $available : 0.0;
        // The maintenance share of the rate: $67.50 - $37.50 = $30.00 at baseline.
        $wmcRateAllocation = $layer3Rate - $fullBurden;

        $wmcCategoryTotal = 0.0;
        foreach ($in['wmcCategories'] as $k => $c) {
            $wmcCategoryTotal += $this->pos($c['hours'] ?? 0, 'wmcCategories.' . $k . '.hours', ($c['label'] ?? 'WMC category'), $v);
        }
        $wmcCategories = [];
        foreach ($in['wmcCategories'] as $c) {
            $hours = max(0.0, (float) ($c['hours'] ?? 0));
            $share = $wmcCategoryTotal > 0 ? $hours / $wmcCategoryTotal : 0.0;
            $wmcCategories[] = [
                'key' => $c['key'] ?? '',
                'label' => $c['label'] ?? '',
                'treatment' => $c['treatment'] ?? '',
                'hours' => $hours,
                'share' => $share,
                'hourly' => $share * $wmcRateAllocation,
            ];
        }
        $categoryReason = trim((string) $in['wmcCategoryOverrideReason']);
        if (abs($wmcCategoryTotal - $wmcHours) > 0.01) {
            // VAL005 — category hours must equal the configured WMC hours unless overridden.
            $v[] = $categoryReason !== ''
                ? $this->msg('VAL005', 'warning', 'wmcCategories',
                    'Workforce maintenance categories total ' . $this->num($wmcCategoryTotal) . ' hours against ' . $this->num($wmcHours)
                    . ' configured hours, under an authorised override.')
                : $this->msg('VAL005', 'error', 'wmcCategories',
                    'Workforce maintenance categories total ' . $this->num($wmcCategoryTotal) . ' hours and must equal the configured '
                    . $this->num($wmcHours) . ' hours, or record an authorised override.');
        }

        // ── Layer 6 inputs (Layer 5 percentages need the margin context) ────
        $margin = (float) $in['profit']['marginPct'] / 100;
        if ($margin < 0 || $margin >= 1) {
            // VAL008 — profit margin must be at least zero and less than one.
            $v[] = $this->msg('VAL008', 'error', 'profit.marginPct', 'Profit margin must be at least 0% and less than 100%.');
            $margin = min(max($margin, 0.0), 0.99);
        }

        $mode = $in['pricingMode'] === 'buildup' ? 'buildup' : 'approved';
        $overrideValue = $in['finalRateOverride']['value'];
        $overrideReason = trim((string) $in['finalRateOverride']['reason']);
        $rateOverridden = $mode === 'approved' && is_numeric($overrideValue) && (float) $overrideValue > 0;
        if ($rateOverridden && $overrideReason === '') {
            $v[] = $this->msg('VAL016', 'warning', 'finalRateOverride.reason',
                'The Final Bill Rate override needs a reason before it counts as authorised.');
        }

        $laborBase = $mode === 'buildup' ? $layer3Rate : $fullBurden;
        $termYears = max(1, min(10, (int) $in['forecast']['termYears']));

        // ── Layer 4 — Other direct costs, normalised to $/hr (spec 6.1) ─────
        $odcLines = [];
        $odcHourly = 0.0;
        $odcAnnual = 0.0;
        $activeDuplicateKeys = [];
        foreach ($in['odc'] as $k => $o) {
            if (empty($o['enabled'])) {
                continue;
            }
            $amount = $this->pos($o['amount'] ?? 0, 'odc.' . $k . '.amount', ($o['label'] ?? 'Other direct cost'), $v);
            $basis = in_array($o['basis'] ?? '', self::ODC_BASES, true) ? $o['basis'] : 'hour';
            $annual = match ($basis) {
                'hour' => $amount * $annualHours,
                'employee' => $amount * $staffing['roundedHeadcount'],
                'post' => $amount * $staffing['equivalentPosts'],
                'month' => $amount * 12,
                'year' => $amount,
                'contract' => $amount / $termYears,
            };
            $status = in_array($o['recoveryStatus'] ?? '', self::RECOVERY_STATUSES, true) ? $o['recoveryStatus'] : 'recovered';
            $provider = ($o['provider'] ?? 'vendor') === 'buyer' ? 'buyer' : 'vendor';
            // VAL007 — only a recovered vendor item raises the recoverable rate.
            $recoverable = $provider === 'vendor' && $status === 'recovered';
            $hourly = $annualHours > 0 ? $annual / $annualHours : 0.0;
            if ($recoverable) {
                $odcHourly += $hourly;
                $odcAnnual += $annual;
                if (! empty($o['duplicateKey'])) {
                    $activeDuplicateKeys[$o['duplicateKey']][] = 'Layer 4 — ' . ($o['label'] ?? '');
                }
            }
            $odcLines[] = [
                'key' => $o['key'] ?? '', 'label' => $o['label'] ?? '', 'basis' => $basis, 'amount' => $amount,
                'provider' => $provider, 'recoveryStatus' => $status, 'recoverable' => $recoverable,
                'annual' => $annual, 'hourly' => $hourly,
                'contributedHourly' => $recoverable ? $hourly : 0.0,
            ];
        }
        if ($hasSupervisionPosition && isset($activeDuplicateKeys['supervision'])) {
            // VAL013 — planned supervision cannot contribute in Layer 1 and Layer 4.
            $v[] = $this->msg('VAL013', 'error', 'odc',
                'Planned supervision is carried as a Layer 1 position and as dedicated field supervision in Layer 4. Remove one.');
        }

        // ── Layer 5 — G&A, normalised to $/hr (spec 7.1) ────────────────────
        $gaLines = [];
        $gaHourly = 0.0;
        $gaBase = $laborBase + $odcHourly;
        foreach ($in['ga'] as $k => $g) {
            if (empty($g['enabled'])) {
                continue;
            }
            $value = $this->pos($g['value'] ?? 0, 'ga.' . $k . '.value', ($g['label'] ?? 'G&A item'), $v);
            $gaMethod = in_array($g['method'] ?? '', self::GA_METHODS, true) ? $g['method'] : 'pct';
            $hourly = match ($gaMethod) {
                'pct' => $gaBase * $value / 100,
                'hourly' => $value,
                'annual' => $annualHours > 0 ? $value / $annualHours : 0.0,
            };
            $gaHourly += $hourly;
            if ($hourly > 0 && ! empty($g['duplicateKey'])) {
                $activeDuplicateKeys[$g['duplicateKey']][] = 'Layer 5 — ' . ($g['label'] ?? '');
            }
            if ($hourly > 0 && preg_match('/\bprofit\b|\bmargin\b/i', (string) ($g['label'] ?? '')) === 1) {
                // VAL015 — profit cannot be included in Layer 5 or another cost layer.
                $v[] = $this->msg('VAL015', 'error', 'ga.' . $k,
                    'Profit belongs in Layer 6 and cannot be carried as the G&A line "' . ($g['label'] ?? '') . '".');
            }
            $gaLines[] = ['key' => $g['key'] ?? '', 'label' => $g['label'] ?? '', 'method' => $gaMethod, 'value' => $value, 'hourly' => $hourly];
        }
        foreach ($activeDuplicateKeys as $dupKey => $sources) {
            if (count($sources) < 2) {
                continue;
            }
            // VAL014 — contract and corporate costs cannot share a duplicate key.
            $v[] = $this->msg('VAL014', 'error', 'odc',
                'The same cost is carried twice under "' . $dupKey . '": ' . implode(' and ', $sources) . '.');
        }

        // ── Layer 6 & the pricing-mode rule (spec 8, 1.3) ───────────────────
        if ($mode === 'buildup') {
            $preProfit = $laborBase + $odcHourly + $gaHourly;
            $finalRate = $preProfit / (1 - $margin);
            $profitHourly = $finalRate - $preProfit;
        } else {
            // VAL009 — approved mode never adds Layers 4-6 to the approved rate.
            $finalRate = $rateOverridden ? (float) $overrideValue : $layer3Rate;
            $profitHourly = $finalRate * $margin;
            $preProfit = $finalRate - $profitHourly;
        }
        if ($profitHourly < 0) {
            $v[] = $this->msg('VAL008', 'warning', 'profit.marginPct',
                'The profit result is negative. This needs an authorised override before approval.');
        }
        $markup = $preProfit > 0 ? $profitHourly / $preProfit : 0.0;

        $reconLabor = $mode === 'buildup' ? $laborBase : $fullBurden;
        $embeddedTotal = $reconLabor + $odcHourly + $gaHourly + $profitHourly;
        $remaining = $finalRate - $embeddedTotal;
        if ($mode === 'approved' && $remaining < -0.000001) {
            // VAL018 — embedded allocations exceed the approved final bill rate.
            $v[] = $this->msg('VAL018', 'warning', 'reconciliation',
                'Embedded allocations exceed the approved Final Bill Rate by ' . $this->money(-$remaining)
                . '/hr. Reduce Layer 4-6 allocations or switch to Full Cost Build-Up Mode.');
        }

        $rateMultiplier = $weightedWage > 0 ? $finalRate / $weightedWage : 0.0;
        foreach ($positions as &$p) {
            $p['wageShare'] = $totalPayroll > 0 ? $p['annualPayroll'] / $totalPayroll : 0.0;
            $p['positionBillRate'] = $p['cashWage'] * $rateMultiplier;
        }
        unset($p);

        // ── Buyer benchmark & capital recovery (spec 10) ─────────────────────
        $croHourly = max($costToProtect - $finalRate, 0.0);
        $premiumHourly = max($finalRate - $costToProtect, 0.0);

        // ── Layer 7 — Five year plan ────────────────────────────────────────
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
                'annualContractValue' => $finalRate * $annualHours,
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
                'weightedWage' => $weightedWage,
                'rateMultiplier' => $rateMultiplier,
            ],
            'layer2' => [
                'method' => $method,
                'fullBurden' => $fullBurden,
                'incrementalBurden' => $incrementalBurden,
                'wageShare' => $impliedWageShare,
                'burdenShare' => $impliedBurdenShare,
                'configuredBurdenShare' => $configuredBurdenShare,
                'lineTotal' => $detailedTotal,
                'reconciles' => abs($impliedBurdenShare - $configuredBurdenShare) <= 0.0001,
                'lines' => $burdenLines,
            ],
            'layer3' => [
                'wmcHoursCalculated' => $wmcCalculated,
                'wmcHours' => $wmcHours,
                'wmcOverridden' => $wmcOverridden,
                'wmcPerPost' => $wmcPerPost,
                'wmcValue' => $wmcValue,
                'finalBillRate' => $layer3Rate,
                'rateAllocation' => $wmcRateAllocation,
                'categories' => $wmcCategories,
                'categoryTotal' => $wmcCategoryTotal,
                'categoriesReconcile' => abs($wmcCategoryTotal - $wmcHours) <= 0.01,
            ],
            'layer4' => ['hourly' => $odcHourly, 'annual' => $odcAnnual, 'lines' => $odcLines, 'treatment' => $mode === 'buildup' ? 'additive' : 'embedded'],
            'layer5' => ['hourly' => $gaHourly, 'annual' => $gaHourly * $annualHours, 'base' => $gaBase, 'lines' => $gaLines, 'treatment' => $mode === 'buildup' ? 'additive' : 'embedded'],
            'layer6' => [
                'marginPct' => $margin,
                'marginDivisor' => 1 - $margin,
                'preProfit' => $preProfit,
                'profitHourly' => $profitHourly,
                'annualProfit' => $profitHourly * $annualHours,
                'markup' => $markup,
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
            'validations' => $v,
            // Kept so existing consumers of the previous shape keep working.
            'warnings' => array_map(fn ($m) => ['code' => $m['code'], 'level' => $m['severity'], 'message' => $m['message']], $v),
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
        foreach (['coverage', 'workforce', 'finalRateOverride', 'profit'] as $group) {
            $out[$group] = array_merge($d[$group], (array) ($raw[$group] ?? []));
        }
        $out['pricingMode'] = (string) ($raw['pricingMode'] ?? $d['pricingMode']);
        $out['wmcCategoryOverrideReason'] = (string) ($raw['wmcCategoryOverrideReason'] ?? '');
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

    /** VAL001 — costs, hours, quantities and factors must be numeric and nonnegative. */
    private function pos(mixed $value, string $field, string $label, array &$v): float
    {
        $number = is_numeric($value) ? (float) $value : 0.0;
        if ($number < 0) {
            $v[] = $this->msg('VAL001', 'error', $field, $label . ' cannot be negative; it was treated as 0.');

            return 0.0;
        }

        return $number;
    }

    /** @return array{code: string, severity: string, field: string, message: string, blocking: bool} */
    private function msg(string $code, string $severity, string $field, string $message): array
    {
        return [
            'code' => $code,
            'severity' => $severity,
            'field' => $field,
            'message' => $message,
            'blocking' => $severity === 'error',
        ];
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2), '0'), '.');
    }

    private function money(float $value): string
    {
        return '$' . number_format($value, 2);
    }

    private function pct(float $ratio): string
    {
        return number_format($ratio * 100, 2) . '%';
    }
}
