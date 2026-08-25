<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Calculator compute cost (wallet credits)
    |--------------------------------------------------------------------------
    |
    | Credits deducted for each successful POST to a calculator compute endpoint.
    | Set CALCULATOR_CREDITS_PER_RUN in .env (minimum 1 when cached).
    |
    */

    'calculator_per_run' => max(1, (int) env('CALCULATOR_CREDITS_PER_RUN', 5)),

    /*
    |--------------------------------------------------------------------------
    | Pay As You Go credits
    |--------------------------------------------------------------------------
    |
    | Buy any quantity of credits at a flat unit price instead of committing to
    | a pack. The packs stay as the volume-discount option: at the default rate
    | below, PAYG is deliberately the most expensive per credit, so Starter /
    | Professional / Enterprise still reward buying ahead.
    |
    | BUSINESS DECISION — unit_price is a placeholder default, not a signed-off
    | rate. For reference the current packs work out at roughly $0.97, $0.79 and
    | $0.66 per credit ($29/30, $79/100, $199/300). Set CREDITS_PAYG_UNIT_PRICE
    | in .env once the rate is agreed.
    |
    */

    'payg' => [
        'unit_price' => max(0.01, (float) env('CREDITS_PAYG_UNIT_PRICE', 1.00)),
        'min' => max(1, (int) env('CREDITS_PAYG_MIN', 5)),
        'max' => max(1, (int) env('CREDITS_PAYG_MAX', 2000)),
    ],

];
