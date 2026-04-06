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

];
