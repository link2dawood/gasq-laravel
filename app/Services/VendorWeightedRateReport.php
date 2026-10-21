<?php

namespace App\Services;

use App\Support\Currency;
use Illuminate\Validation\ValidationException;

class VendorWeightedRateReport
{
    public function __construct(private VendorWeightedRateEngine $engine) {}

    /** Build the private report from saved inputs, never from posted or rounded totals. */
    public function prepare(array $payload): array
    {
        $scenario = (array) ($payload['scenario'] ?? []);
        $module = (array) data_get($scenario, 'meta.vendorWeightedRates', []);
        $inputs = $module['draft'] ?? data_get($module, 'applied.inputs');
        if (! is_array($inputs)) {
            throw ValidationException::withMessages(['report' => ['Calculate and save a vendor weighted estimate before downloading its report.']]);
        }
        $result = $this->engine->compute($inputs);
        if (! $result['isValid']) {
            throw ValidationException::withMessages($result['fieldErrors']);
        }
        foreach ($result['roles'] as &$role) {
            $role['weeklyBillCost'] = $role['weeklyHours'] * $role['billRate'];
            $role['monthlyBillCost'] = $role['annualBillCost'] / 12;
        }
        unset($role);
        $ownerCurrency = ($payload['user'] ?? null)?->currency;
        $currency = Currency::jsConfig(isset(config('currency.profiles')[$ownerCurrency ?? '']) ? $ownerCurrency : null);
        $t = &$result['totals'];
        $t['weeklyBillCost'] = array_sum(array_column($result['roles'], 'weeklyBillCost'));
        $t['monthlyBillCost'] = $t['annualBillCost'] / 12;
        // Reconcile the displayed local-currency rate, retaining USD model values.
        $t['roundedRateAnnualCost'] = round(round($t['weightedBillRate'] * $currency['rate'], 10), 2) / $currency['rate'] * $t['annualRoleHours'];
        $t['roundingDifference'] = $t['roundedRateAnnualCost'] - $t['annualBillCost'];
        unset($t);

        return array_merge($payload, [
            'weighted' => $result,
            'weightedInputs' => $inputs,
            'reportCurrency' => $currency,
            'selectionLabel' => isset($module['applied']) && $inputs === $module['applied']['inputs']
                ? 'Applied vendor selection' : 'Saved vendor draft',
            'buyerSite' => data_get($scenario, 'meta.companyName') ?: data_get($scenario, 'meta.inputs.company_name') ?: 'Not supplied',
            'vendorName' => ($payload['user'] ?? null)?->company ?: ($payload['user'] ?? null)?->name ?: 'Not supplied',
        ]);
    }
}
