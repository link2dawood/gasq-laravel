<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Standardized Weighted Interview Scorecard (spec §7)
    |--------------------------------------------------------------------------
    |
    | Every vendor is scored on the same criteria so capability is compared
    | apples-to-apples BEFORE any price is revealed. Each criterion is scored
    | 0–10 by the buyer; the weighted average (×10) becomes the vendor's
    | capability_score (0–100). Weights must total 1.0.
    |
    */

    'criteria' => [
        ['key' => 'capability',    'label' => 'Capability & relevant experience', 'weight' => 0.25],
        ['key' => 'staffing',      'label' => 'Staffing & coverage plan',          'weight' => 0.20],
        ['key' => 'compliance',    'label' => 'Licensing, insurance & compliance', 'weight' => 0.15],
        ['key' => 'communication', 'label' => 'Communication & responsiveness',    'weight' => 0.15],
        ['key' => 'understanding', 'label' => 'Site & risk understanding',         'weight' => 0.15],
        ['key' => 'track_record',  'label' => 'References & track record',          'weight' => 0.10],
    ],
];
