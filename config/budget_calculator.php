<?php

return [
    'default_government_should_cost_hourly' => 86.75,
    'default_annual_billable_hours' => 8736,
    'default_total' => 757848,

    'groups' => [
        [
            'key' => 'directLabor',
            'label' => 'Direct Labor',
            'description' => 'Core wage, premium, and direct-pay labor drivers.',
            'benchmarked' => true,
            'items' => [
                ['key' => 'baseDirectLaborWage', 'label' => 'Base Consolidated Blended Direct Labor Wage', 'annual' => 442644.72, 'color' => '#2563eb'],
                ['key' => 'localityPay', 'label' => 'Locality Pay', 'annual' => 0.0, 'color' => '#3b82f6'],
                ['key' => 'laborMarketAdjustment', 'label' => 'Labor Market Adjustment', 'annual' => 0.0, 'color' => '#60a5fa'],
                ['key' => 'hwCash', 'label' => 'H&W (Cash)', 'annual' => 89978.84, 'color' => '#38bdf8'],
                ['key' => 'shiftDifferential', 'label' => 'Shift Differential', 'annual' => 0.0, 'color' => '#0ea5e9'],
                ['key' => 'otHolidayPremium', 'label' => 'OT/Holiday Premium', 'annual' => 18723.871656, 'color' => '#0284c7'],
                ['key' => 'donDoff', 'label' => 'DON/DOFF', 'annual' => 17229.60723925, 'color' => '#1d4ed8'],
            ],
        ],
        [
            'key' => 'fringeBurden',
            'label' => 'Fringe & Employer Burden',
            'description' => 'Payroll taxes, benefits, leave, and statutory burden.',
            'benchmarked' => true,
            'items' => [
                ['key' => 'ficaMedicare', 'label' => 'FICA / Medicare', 'annual' => 43496.14347548662, 'color' => '#14b8a6'],
                ['key' => 'futa', 'label' => 'FUTA', 'annual' => 3411.4622333715006, 'color' => '#2dd4bf'],
                ['key' => 'suta', 'label' => 'SUTA', 'annual' => 11371.540777905002, 'color' => '#5eead4'],
                ['key' => 'workersCompensation', 'label' => 'Workers Compensation', 'annual' => 9097.2326223239997, 'color' => '#10b981'],
                ['key' => 'healthWelfare', 'label' => 'Health & Welfare', 'annual' => 0.0, 'color' => '#34d399'],
                ['key' => 'vacation', 'label' => 'Vacation', 'annual' => 5685.770388952501, 'color' => '#22c55e'],
                ['key' => 'paidHolidays', 'label' => 'Paid Holidays', 'annual' => 6083.7743161791741, 'color' => '#84cc16'],
                ['key' => 'sickLeave', 'label' => 'Sick Leave', 'annual' => 1137.1540777905, 'color' => '#a3e635'],
            ],
        ],
        [
            'key' => 'operationsSupport',
            'label' => 'Operations & Contract Support',
            'description' => 'Field support, equipment, patrol, systems, and insurance.',
            'benchmarked' => false,
            'items' => [
                ['key' => 'recruitingHiring', 'label' => 'Recruiting / Hiring', 'annual' => 0.0, 'color' => '#f97316'],
                ['key' => 'trainingCertification', 'label' => 'Training / Certification', 'annual' => 8528.6555834287501, 'color' => '#fb923c'],
                ['key' => 'uniformsEquipment', 'label' => 'Uniforms / Equipment', 'annual' => 24179.924999999999, 'color' => '#f59e0b'],
                ['key' => 'fieldSupervision', 'label' => 'Field Supervision', 'annual' => 0.0, 'color' => '#facc15'],
                ['key' => 'contractManagement', 'label' => 'Contract Management', 'annual' => 0.0, 'color' => '#fbbf24'],
                ['key' => 'qualityAssurance', 'label' => 'Quality Assurance', 'annual' => 0.0, 'color' => '#fde047'],
                ['key' => 'vehiclesPatrol', 'label' => 'Vehicles / Patrol', 'annual' => 27953.127272727274, 'color' => '#c084fc'],
                ['key' => 'technologySystems', 'label' => 'Technology / Systems', 'annual' => 5685.770388952501, 'color' => '#a78bfa'],
                ['key' => 'generalLiabilityInsurance', 'label' => 'General Liability Insurance', 'annual' => 50717.071869456297, 'color' => '#ef4444'],
                ['key' => 'umbrellaOtherInsurance', 'label' => 'Umbrella / Other Insurance', 'annual' => 4264.3277917143751, 'color' => '#f87171'],
            ],
        ],
        [
            'key' => 'overheadProfit',
            'label' => 'Overhead, G&A & Profit',
            'description' => 'Corporate overhead, administrative support, and fee structure.',
            'benchmarked' => false,
            'items' => [
                ['key' => 'administrativeHrPayroll', 'label' => 'Administrative / HR / Payroll', 'annual' => 0.0, 'color' => '#6366f1'],
                ['key' => 'accountingLegal', 'label' => 'Accounting / Legal', 'annual' => 0.0, 'color' => '#818cf8'],
                ['key' => 'corporateOverhead', 'label' => 'Corporate Overhead', 'annual' => 68229.244667430001, 'color' => '#8b5cf6'],
                ['key' => 'ga', 'label' => 'G&A', 'annual' => 28428.851944762504, 'color' => '#a855f7'],
                ['key' => 'profitFee', 'label' => 'Profit / Fee', 'annual' => 64237.15156193867, 'color' => '#d946ef'],
            ],
        ],
    ],
];
