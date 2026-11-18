<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Report display timezone
    |--------------------------------------------------------------------------
    |
    | The application stores and reasons in UTC. Reports are read by buyers and
    | vendors in the United States, so every date and time printed on a report
    | is rendered in this zone instead.
    |
    | America/New_York is the Eastern zone, not a fixed offset: it prints EST in
    | winter and EDT over daylight saving, which is what a reader in New York
    | expects to see. Setting a fixed 'EST' here would print 9:00 AM EST for a
    | report actually generated at 10:00 AM local time in July.
    |
    */

    'timezone' => env('REPORT_TIMEZONE', 'America/New_York'),

];
