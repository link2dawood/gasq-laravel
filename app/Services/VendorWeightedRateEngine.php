<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

class VendorWeightedRateEngine
{
    public const MODEL_MULTIPLIER = 18 / 7;

    public const CLASSIFICATIONS = [
        'unarmed' => 'Unarmed officer', 'armed' => 'Armed officer',
        'mobile_patrol' => 'Mobile patrol', 'video_surveillance' => 'Video surveillance',
        'security_console' => 'Security console', 'badging_clerk' => 'Badging clerk',
        'lead' => 'Lead officer', 'supervisor' => 'Supervisor',
        'management' => 'Management', 'custom' => 'Custom classification',
    ];

    public static function presets(): array
    {
        $sample = [
            ['unarmed', 'Unarmed officer — prime', 21, 39.29],
            ['unarmed', 'Unarmed officer — subcontractors', 72, 39.29],
            ['video_surveillance', 'Video surveillance', 14, 40.58],
            ['security_console', 'Security console', 16, 40.58],
            ['mobile_patrol', 'Mobile patrol', 11, 40.58],
            ['armed', 'Armed officer', 9, 42.86],
            ['badging_clerk', 'Badging clerk', 1, 43.70],
            ['supervisor', 'Armed supervisor', 1, 45.37],
        ];

        return [
            'sample' => [
                'mode' => 'current', 'weeklyHoursPerEmployee' => 40, 'weeksPerYear' => 52,
                'roles' => array_map(fn ($p) => [
                    'classification' => $p[0], 'label' => $p[1], 'headcount' => $p[2],
                    'actualWage' => null, 'billRate' => $p[3],
                    'weeklyHoursPerEmployee' => null, 'weeksPerYear' => null,
                ], $sample),
            ],
            'six_person' => [
                'mode' => 'wage_based', 'weeklyHoursPerEmployee' => 40, 'weeksPerYear' => 52,
                'roles' => array_map(fn ($p) => [
                    'classification' => $p[0], 'label' => $p[1], 'headcount' => $p[2],
                    'actualWage' => $p[3], 'billRate' => null,
                    'weeklyHoursPerEmployee' => null, 'weeksPerYear' => null,
                ], [
                    ['unarmed', 'Security Officers', 3, 5.54],
                    ['lead', 'Lead/Supervisor', 2, 10.54],
                    ['management', 'Management', 1, 13.58],
                ]),
            ],
        ];
    }

    public function compute(array $inputs): array
    {
        if (is_array($inputs['roles'] ?? null)) {
            foreach ($inputs['roles'] as &$role) {
                if (! is_array($role)) {
                    continue;
                }
                foreach (['actualWage', 'billRate', 'weeklyHoursPerEmployee', 'weeksPerYear'] as $field) {
                    if (($role[$field] ?? null) === '') {
                        $role[$field] = null;
                    }
                }
            }
            unset($role);
        }
        $rules = [
            'mode' => 'required|in:current,wage_based',
            'weeklyHoursPerEmployee' => 'required|numeric|min:0|max:168',
            'weeksPerYear' => 'required|numeric|min:0|max:53',
            'roles' => 'required|array|list|min:1|max:200',
            'roles.*' => 'required|array',
            'roles.*.classification' => 'required|in:'.implode(',', array_keys(self::CLASSIFICATIONS)),
            'roles.*.label' => 'nullable|string|max:120',
            'roles.*.headcount' => 'required|integer|min:0|max:1000000',
            'roles.*.actualWage' => 'nullable|numeric|min:0|max:1000000',
            'roles.*.billRate' => 'nullable|numeric|min:0|max:1000000',
            'roles.*.weeklyHoursPerEmployee' => 'nullable|numeric|min:0|max:168',
            'roles.*.weeksPerYear' => 'nullable|numeric|min:0|max:53',
        ];
        $labels = [];
        foreach ((array) ($inputs['roles'] ?? []) as $i => $role) {
            $prefix = 'roles.'.$i;
            if (data_get($role, 'classification') === 'custom') {
                $rules[$prefix.'.label'] = 'required|string|max:120';
            }
            $required = ($inputs['mode'] ?? '') === 'current' ? 'billRate' : 'actualWage';
            $rules[$prefix.'.'.$required] = 'required|numeric|min:0|max:1000000';
            foreach (['classification', 'label', 'headcount', 'actualWage', 'billRate', 'weeklyHoursPerEmployee', 'weeksPerYear'] as $field) {
                $labels[$prefix.'.'.$field] = 'role '.($i + 1).' '.match ($field) {
                    'actualWage' => 'actual pay wage', 'billRate' => 'current bill rate',
                    'weeklyHoursPerEmployee' => 'weekly hours per employee', 'weeksPerYear' => 'weeks per year',
                    default => $field,
                };
            }
        }
        $validator = Validator::make($inputs, $rules, [], $labels);
        if ($validator->fails()) {
            return $this->invalid($validator->errors()->messages());
        }

        $roles = [];
        $totals = array_fill_keys(['headcount', 'combinedHourlyWages', 'weeklyHours', 'monthlyHours', 'annualRoleHours', 'annualWages', 'annualBillCost'], 0.0);
        $sources = [];
        foreach ($inputs['roles'] as $role) {
            $actual = isset($role['actualWage']) && $role['actualWage'] !== '';
            $bill = $inputs['mode'] === 'current' ? (float) $role['billRate'] : (float) $role['actualWage'] * self::MODEL_MULTIPLIER;
            $wage = $actual ? (float) $role['actualWage'] : $bill / self::MODEL_MULTIPLIER;
            $weeklyPerEmployee = (float) ($role['weeklyHoursPerEmployee'] ?? $inputs['weeklyHoursPerEmployee']);
            $weeks = (float) ($role['weeksPerYear'] ?? $inputs['weeksPerYear']);
            $weekly = (int) $role['headcount'] * $weeklyPerEmployee;
            $annual = $weekly * $weeks;
            $source = $actual ? 'actual' : 'implied';
            $sources[] = $source;
            $row = [
                'classification' => $role['classification'],
                'label' => trim($role['label'] ?? '') ?: self::CLASSIFICATIONS[$role['classification']],
                'headcount' => (int) $role['headcount'],
                'payRate' => $wage, 'paySource' => $source,
                'paySourceLabel' => $actual ? 'Actual wage — entered by vendor' : 'Implied pay wage — model estimate',
                'combinedHourlyWages' => $role['headcount'] * $wage,
                'wageShareOfBillRate' => $bill > 0 ? $wage / $bill : null,
                'weeklyHoursPerEmployee' => $weeklyPerEmployee, 'weeksPerYear' => $weeks,
                'weeklyHours' => $weekly, 'monthlyHours' => $annual / 12,
                'annualRoleHours' => $annual, 'billRate' => $bill,
                'annualWages' => $wage * $annual, 'annualBillCost' => $bill * $annual,
            ];
            foreach (array_keys($totals) as $key) {
                $totals[$key] += $row[$key];
            }
            $roles[] = $row;
        }
        if ($totals['annualRoleHours'] <= 0) {
            return $this->invalid(['roles' => ['Enter staffing and hour assumptions that produce positive total annual role hours.']]);
        }
        $totals['weightedPayWage'] = $totals['annualWages'] / $totals['annualRoleHours'];
        $totals['weightedBillRate'] = $totals['annualBillCost'] / $totals['annualRoleHours'];
        $totals['overallWageShare'] = $totals['annualBillCost'] > 0 ? $totals['annualWages'] / $totals['annualBillCost'] : null;

        return [
            'isValid' => true, 'fieldErrors' => [], 'errors' => [],
            'mode' => $inputs['mode'],
            'modeLabel' => $inputs['mode'] === 'current' ? 'Current Bill Rate Analysis' : 'GASQ Wage-Based Pricing',
            'rateSource' => $inputs['mode'] === 'current' ? 'Vendor-entered current bill rates' : 'GASQ 6:1 model rate',
            'wageSource' => count(array_unique($sources)) > 1 ? 'Mixed actual wages and model estimates'
                : ($sources[0] === 'actual' ? 'Actual wages — entered by vendor' : 'Implied pay wage — model estimate'),
            'model' => ['name' => 'GASQ 6:1 model rate', 'wageShare' => 0.70, 'modelHours' => 3744, 'annualHoursBasis' => 2080, 'multiplier' => self::MODEL_MULTIPLIER],
            'roles' => $roles, 'totals' => $totals,
        ];
    }

    private function invalid(array $fields): array
    {
        return ['isValid' => false, 'fieldErrors' => $fields, 'errors' => array_merge(...array_values($fields)), 'roles' => [], 'totals' => null];
    }
}
