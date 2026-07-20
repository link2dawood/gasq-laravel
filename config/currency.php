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
        'USD' => [
            'code' => 'USD',
            'label' => 'United States (USD)',
            'symbol' => '$',
            'locale' => 'en-US',
        ],
        'CAD' => [
            'code' => 'CAD',
            'label' => 'Canada (CAD)',
            // "CA$" (not a bare "$") so flipping to Canada is visible everywhere and
            // never looks identical to USD. locale en-US makes Intl render "CA$" too.
            'symbol' => 'CA$',
            'locale' => 'en-US',
        ],
    ],
];
