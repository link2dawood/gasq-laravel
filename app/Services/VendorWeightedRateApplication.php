<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class VendorWeightedRateApplication
{
    public function __construct(private VendorWeightedRateEngine $engine) {}

    public function apply(array $scenario, array $inputs): array
    {
        $result = $this->engine->compute($inputs);
        if (! $result['isValid']) {
            throw ValidationException::withMessages($result['fieldErrors']);
        }
        $meta = (array) ($scenario['meta'] ?? []);
        $module = (array) ($meta['vendorWeightedRates'] ?? []);
        if (! isset($module['originalVendorComparison'])) {
            $module['originalVendorComparison'] = [
                'present' => array_key_exists('vendorTrueHourlyCost', $meta),
                'value' => $meta['vendorTrueHourlyCost'] ?? null,
            ];
        }
        $module['draft'] = $inputs;
        $module['draftResult'] = $result;
        $module['applied'] = ['inputs' => $inputs, 'result' => $result, 'appliedAt' => now()->toIso8601String()];
        $meta['vendorWeightedRates'] = $module;
        $scenario['meta'] = $meta;

        return $this->normalize($scenario);
    }

    /** Recompute from stored inputs; never trust client-supplied totals. */
    public function normalize(array $scenario): array
    {
        $inputs = data_get($scenario, 'meta.vendorWeightedRates.applied.inputs');
        if (! is_array($inputs)) {
            return $scenario;
        }
        $result = $this->engine->compute($inputs);
        if (! $result['isValid']) {
            throw ValidationException::withMessages($result['fieldErrors']);
        }
        $scenario['meta']['vendorWeightedRates']['applied']['result'] = $result;
        $scenario['meta']['vendorWeightedPayWage'] = $result['totals']['weightedPayWage'];
        $scenario['meta']['vendorWeightedBillRate'] = $result['totals']['weightedBillRate'];
        $scenario['meta']['vendorRateSource'] = $result['rateSource'];
        $scenario['meta']['vendorRateMode'] = $result['mode'];
        // The existing Economic Justification engine reads this comparison input.
        $scenario['meta']['vendorTrueHourlyCost'] = $result['totals']['weightedBillRate'];

        return $scenario;
    }

    public function comparison(array $scenario, array $coverage): ?array
    {
        $applied = data_get($scenario, 'meta.vendorWeightedRates.applied.result');
        if (! is_array($applied) || ! ($applied['isValid'] ?? false)) {
            return null;
        }
        $meta = (array) ($scenario['meta'] ?? []);
        $hours = $meta['annualBillableServiceHours'] ?? $coverage['annualCoverageHours']
            ?? ((float) ($meta['weeklyHoursPerformed'] ?? 168) * (float) ($meta['weeksInYear'] ?? 52));
        if (! is_numeric($hours) || ! is_finite((float) $hours) || $hours < 0) {
            throw ValidationException::withMessages(['annualBillableServiceHours' => ['Project billable service hours must be a nonnegative number.']]);
        }

        return [
            'weightedPayWage' => $applied['totals']['weightedPayWage'],
            'weightedBillRate' => $applied['totals']['weightedBillRate'],
            'annualBillableServiceHours' => (float) $hours,
            'projectAnnualEstimate' => $applied['totals']['weightedBillRate'] * $hours,
            'annualPayrollHours' => $applied['totals']['annualRoleHours'],
            'buyerBaselineWage' => $meta['basePayRate'] ?? $meta['basePay'] ?? null,
            'buyerBaselineHourlyCost' => $meta['employeeTrueHourlyCost'] ?? 133,
            'rateSource' => $applied['rateSource'], 'mode' => $applied['modeLabel'],
            'wageSource' => $applied['wageSource'],
        ];
    }

    /** General reports must not bypass the sealed-price reveal workflow. */
    public function publicScenario(array $scenario): array
    {
        $original = data_get($scenario, 'meta.vendorWeightedRates.originalVendorComparison');
        if (is_array($original)) {
            if ($original['present']) {
                $scenario['meta']['vendorTrueHourlyCost'] = $original['value'];
            } else {
                unset($scenario['meta']['vendorTrueHourlyCost']);
            }
        }
        foreach (['vendorWeightedRates', 'vendorWeightedPayWage', 'vendorWeightedBillRate', 'vendorRateSource', 'vendorRateMode'] as $key) {
            unset($scenario['meta'][$key]);
        }

        return $scenario;
    }
}
