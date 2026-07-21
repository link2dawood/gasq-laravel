<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active currency
    |--------------------------------------------------------------------------
    | The platform-wide currency. Resolved at runtime by App\Support\Currency,
    | which prefers the 'currency' admin Setting and falls back to this default.
    | Defaults to USD so nothing changes until a country is switched on.
    */
    'default' => env('CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Supported currency / locale profiles
    |--------------------------------------------------------------------------
    | Add a country by adding a profile here — no code changes required.
    | Calculators enter local values (no FX conversion); this only controls how
    | amounts are labelled and formatted.
    */
    'profiles' => [
        // 'rate' = multiplier applied to the USD labor model to present amounts in
        // this currency (USD result × rate = local result). USD is the base (1.0).
        'USD' => [
            'code' => 'USD',
            'label' => 'United States (USD)',
            'symbol' => '$',
            'locale' => 'en-US',
            'rate' => 1.0,
        ],
        'CAD' => [
            'code' => 'CAD',
            'label' => 'Canada (CAD)',
            // "CA$" (not a bare "$") so flipping to Canada is visible everywhere and
            // never looks identical to USD. locale en-US makes Intl render "CA$" too.
            'symbol' => 'CA$',
            'locale' => 'en-US',
            'rate' => (float) env('EXCHANGE_RATE_CAD', 1.41),
        ],
    ],
];
