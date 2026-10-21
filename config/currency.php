<?php

$reference = json_decode(file_get_contents(__DIR__.'/../resources/data/exchange-rates.json'), true, 512, JSON_THROW_ON_ERROR);
$rate = fn (string $code) => $reference['rates'][$code];

return [
    'reference' => $reference,
    'refresh_on_request' => env('CURRENCY_REFRESH_ON_REQUEST', env('APP_ENV') !== 'testing'),
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
    | Add a profile together with a verified fallback quote for its currency.
    | Calculation values stay in USD. Monetary inputs and outputs are converted
    | for display; staffing hours, ratios, and headcount are never converted.
    */
    'profiles' => [
        // 'rate' = multiplier applied to the USD labor model to present amounts in
        // this currency (USD result × rate = local result). USD is the base (1.0).
        // Last verified defaults; ExchangeRateService refreshes the dated ECB sheet.
        'USD' => ['code' => 'USD', 'label' => 'United States (USD)', 'symbol' => '$',   'locale' => 'en-US', 'rate' => $rate('USD')],
        'CAD' => ['code' => 'CAD', 'label' => 'Canada (CAD)',        'symbol' => 'CA$', 'locale' => 'en-US', 'rate' => $rate('CAD')],
        'MXN' => ['code' => 'MXN', 'label' => 'Mexico (MXN)',        'symbol' => 'MX$', 'locale' => 'en-US', 'rate' => $rate('MXN')],
        'GBP' => ['code' => 'GBP', 'label' => 'United Kingdom (GBP)', 'symbol' => '£',   'locale' => 'en-GB', 'rate' => $rate('GBP')],
        'AUD' => ['code' => 'AUD', 'label' => 'Australia (AUD)',      'symbol' => 'A$',  'locale' => 'en-US', 'rate' => $rate('AUD')],
        'INR' => ['code' => 'INR', 'label' => 'India (INR)',          'symbol' => '₹',   'locale' => 'en-IN', 'rate' => $rate('INR')],
        'BRL' => ['code' => 'BRL', 'label' => 'Brazil (BRL)',         'symbol' => 'R$',  'locale' => 'en-US', 'rate' => $rate('BRL')],
        'ZAR' => ['code' => 'ZAR', 'label' => 'South Africa (ZAR)',   'symbol' => 'R',   'locale' => 'en-US', 'rate' => $rate('ZAR')],
        'EUR' => ['code' => 'EUR', 'label' => 'Germany / France (EUR)', 'symbol' => '€', 'locale' => 'en-IE', 'rate' => $rate('EUR')],
        'CNY' => ['code' => 'CNY', 'label' => 'China (CNY)',          'symbol' => 'CN¥', 'locale' => 'en-US', 'rate' => $rate('CNY')],
        'JPY' => ['code' => 'JPY', 'label' => 'Japan (JPY)',          'symbol' => '¥',   'locale' => 'en-US', 'rate' => $rate('JPY')],
    ],
];
