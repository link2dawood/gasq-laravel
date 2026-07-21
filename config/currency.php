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
        // Rates are starting defaults — update them per market from Admin → Settings
        // (exchange_rate_<code>) with the current USD→local rate.
        'USD' => ['code' => 'USD', 'label' => 'United States (USD)', 'symbol' => '$',   'locale' => 'en-US', 'rate' => 1.0],
        'CAD' => ['code' => 'CAD', 'label' => 'Canada (CAD)',        'symbol' => 'CA$', 'locale' => 'en-US', 'rate' => (float) env('EXCHANGE_RATE_CAD', 1.41)],
        'GBP' => ['code' => 'GBP', 'label' => 'United Kingdom (GBP)', 'symbol' => '£',   'locale' => 'en-GB', 'rate' => 0.79],
        'AUD' => ['code' => 'AUD', 'label' => 'Australia (AUD)',      'symbol' => 'A$',  'locale' => 'en-US', 'rate' => 1.53],
        'INR' => ['code' => 'INR', 'label' => 'India (INR)',          'symbol' => '₹',   'locale' => 'en-IN', 'rate' => 86.0],
        'BRL' => ['code' => 'BRL', 'label' => 'Brazil (BRL)',         'symbol' => 'R$',  'locale' => 'en-US', 'rate' => 5.70],
        'ZAR' => ['code' => 'ZAR', 'label' => 'South Africa (ZAR)',   'symbol' => 'R',   'locale' => 'en-US', 'rate' => 18.50],
        'EUR' => ['code' => 'EUR', 'label' => 'Germany / France (EUR)', 'symbol' => '€', 'locale' => 'en-IE', 'rate' => 0.93],
        'CNY' => ['code' => 'CNY', 'label' => 'China (CNY)',          'symbol' => 'CN¥', 'locale' => 'en-US', 'rate' => 7.25],
        'JPY' => ['code' => 'JPY', 'label' => 'Japan (JPY)',          'symbol' => '¥',   'locale' => 'en-US', 'rate' => 157.0],
    ],
];
