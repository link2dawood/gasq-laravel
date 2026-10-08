<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Facades\Validator;

/** Staffing template arithmetic. All calculations retain full precision. */
class WeightedRatesEngine
{
    public static function defaults(): array
    {
        return [
            'mode' => 'manual',
            'weeklyHoursPerPerson' => 40,
            'annualWeeks' => 52,
            'coveragePosts' => 1,
            'employerCostSharePct' => 30,
            // The supplied 18/7 multiplier is 1.80 / 0.70 (80% markup).
            'markupPct' => 80,
            'targetType' => 'bill',
            'targetRate' => 21.98,
            'solveIndex' => 2,
            'seniorPremium' => 5,
            'supervisorPremium' => 8.04,
            'positions' => [
                ['name' => 'Security Officer', 'count' => 3, 'payRate' => 5.54],
                ['name' => 'Senior Officers/Lead/Relief', 'count' => 2, 'payRate' => 10.54],
                ['name' => 'Team Supervisor/Contract Management', 'count' => 1, 'payRate' => 13.58],
            ],
        ];
    }

    public function compute(array $raw): array
    {
        $in = array_replace(self::defaults(), $raw);
        $rules = [
            'mode' => 'required|in:manual,solve,generate',
            'weeklyHoursPerPerson' => 'required|numeric|gt:0|max:168',
            'annualWeeks' => 'required|numeric|gt:0|max:53',
            'coveragePosts' => 'required|integer|min:1|max:1000000',
            'employerCostSharePct' => 'required|numeric|min:0|max:99.99',
            'markupPct' => 'required|numeric|min:0|max:10000',
            'positions' => 'required|array|size:3',
            'positions.*' => 'required|array',
            'positions.*.name' => 'required|string|max:120',
            'positions.*.count' => 'required|integer|min:0|max:1000000',
        ];
        if ($in['mode'] !== 'manual') {
            $rules['targetType'] = 'required|in:pay,bill,coverageBill';
            $rules['targetRate'] = 'required|numeric|min:0|max:1000000';
        }
        if ($in['mode'] === 'solve') {
            $rules['solveIndex'] = 'required|integer|in:0,1,2';
        }
        if ($in['mode'] === 'generate') {
            $rules['seniorPremium'] = 'required|numeric|gt:0|max:1000000';
            $rules['supervisorPremium'] = 'required|numeric|gt:seniorPremium|max:1000000';
        } else {
            for ($i = 0; $i < 3; $i++) {
                if ($in['mode'] !== 'solve' || (string) $in['solveIndex'] !== (string) $i) {
                    $rules["positions.$i.payRate"] = 'required|numeric|min:0|max:1000000';
                }
            }
        }
        $validator = Validator::make($in, $rules);
        if ($validator->fails()) {
            return $this->invalid($validator->errors()->all());
        }

        $positions = array_values($in['positions']);
        $count = array_sum(array_column($positions, 'count'));
        if ($count <= 0) {
            return $this->invalid(['Enter at least one employee across the three positions.']);
        }
        $wageShare = 1 - (float) $in['employerCostSharePct'] / 100;
        $multiplier = (1 + (float) $in['markupPct'] / 100) / $wageShare;
        $annualPerPerson = (float) $in['weeklyHoursPerPerson'] * (float) $in['annualWeeks'];
        $weeklyHours = $count * (float) $in['weeklyHoursPerPerson'];
        $annualHours = $count * $annualPerPerson;
        $weeklyCoverageHours = (int) $in['coveragePosts'] * 24 * 7;
        $annualCoverageHours = $weeklyCoverageHours * (float) $in['annualWeeks'];
        $paidToCoverageRatio = $annualHours / $annualCoverageHours;
        $targetMultiplier = match ($in['targetType']) {
            'bill' => $multiplier,
            'coverageBill' => $multiplier * $paidToCoverageRatio,
            default => 1.0,
        };
        $targetPay = $in['mode'] === 'manual' ? null
            : (float) $in['targetRate'] / $targetMultiplier;

        if ($in['mode'] === 'solve') {
            $index = (int) $in['solveIndex'];
            if ($positions[$index]['count'] <= 0) {
                return $this->invalid(['The position being solved must have at least one employee.']);
            }
            $known = 0.0;
            foreach ($positions as $i => $p) {
                if ($i !== $index) {
                    $known += $p['count'] * $p['payRate'];
                }
            }
            $positions[$index]['payRate'] = ($targetPay * $count - $known) / $positions[$index]['count'];
        } elseif ($in['mode'] === 'generate') {
            // Center distinct role premiums on the requested weighted pay.
            $offsets = [0.0, (float) $in['seniorPremium'], (float) $in['supervisorPremium']];
            $weightedOffset = 0.0;
            foreach ($positions as $i => $p) {
                $weightedOffset += $p['count'] * $offsets[$i] / $count;
            }
            foreach ($positions as $i => &$p) {
                $p['payRate'] = $targetPay - $weightedOffset + $offsets[$i];
            }
            unset($p);
        }

        foreach ($positions as &$p) {
            if ($p['payRate'] < -1e-9) {
                return $this->invalid(['This target requires a negative pay rate. Increase the target, reduce the known rates, or reduce the role premiums.']);
            }
            $p['payRate'] = max(0.0, (float) $p['payRate']);
        }
        unset($p);

        $combined = 0.0;
        foreach ($positions as $p) {
            $combined += $p['count'] * $p['payRate'];
        }
        $weightedPay = $combined / $count;
        $weightedBill = $weightedPay * $multiplier;
        $annualBillCost = $weightedBill * $annualHours;
        $annualEmployerCost = $weightedPay / $wageShare * $annualHours;
        $coverageBillRate = $annualBillCost / $annualCoverageHours;
        foreach ($positions as &$p) {
            $p['combinedHourlyWages'] = $p['count'] * $p['payRate'];
            $p['timeShare'] = $p['count'] / $count;
            $p['wageShare'] = $combined > 0 ? $p['combinedHourlyWages'] / $combined : 0.0;
            $p['weeklyHours'] = $p['count'] * (float) $in['weeklyHoursPerPerson'];
            $p['annualHours'] = $p['count'] * $annualPerPerson;
            $p['employerCost'] = $p['payRate'] / $wageShare;
            $p['billRate'] = $p['payRate'] * $multiplier;
            $p['annualBillCost'] = $p['billRate'] * $p['annualHours'];
        }
        unset($p);

        return [
            'isValid' => true,
            'errors' => [],
            'warnings' => $annualHours < $annualCoverageHours
                ? ['Paid roster hours are below the required coverage hours. Increase staffing or weekly hours to cover the schedule.']
                : [],
            'mode' => $in['mode'],
            'multiplier' => $multiplier,
            'targetPayRate' => $targetPay,
            'positions' => $positions,
            'totals' => [
                'count' => $count,
                'weightedPayRate' => $weightedPay,
                'combinedHourlyWages' => $combined,
                'timeShare' => 1.0,
                'wageShare' => $combined > 0 ? 1.0 : 0.0,
                'weeklyHours' => $weeklyHours,
                'annualHours' => $annualHours,
                'annualPaidHours' => $annualHours,
                'weeklyCoverageHours' => $weeklyCoverageHours,
                'annualCoverageHours' => $annualCoverageHours,
                'paidToCoverageRatio' => $paidToCoverageRatio,
                'annualEmployerCost' => $annualEmployerCost,
                'employerCostPerCoverageHour' => $annualEmployerCost / $annualCoverageHours,
                'coverageBillRate' => $coverageBillRate,
                'employerCost' => $weightedPay / $wageShare,
                'weightedBillRate' => $weightedBill,
                'annualBillCost' => $annualBillCost,
                'annualCostAtRoundedBillRate' => round($weightedBill, 2) * $annualHours,
                'roundingDifference' => round($weightedBill, 2) * $annualHours - $weightedBill * $annualHours,
                'annualCostAtRoundedCoverageBillRate' => round($coverageBillRate, 2) * $annualCoverageHours,
                'coverageRoundingDifference' => round($coverageBillRate, 2) * $annualCoverageHours - $annualBillCost,
            ],
        ];
    }

    private function invalid(array $errors): array
    {
        return ['isValid' => false, 'errors' => $errors, 'positions' => [], 'totals' => null];
    }
}
