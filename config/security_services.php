<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Canonical Security Services
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the security services buyers can contract on
    | GASQ. Used by BOTH the public "/security-services" page (the cards) and
    | the job-posting "What type of security service are you requesting?"
    | dropdown (see App\Http\Controllers\JobPostingController). Update this
    | list and every buyer-facing location stays in sync.
    |
    | Icons are Font Awesome 6 free-solid names (the layout loads FA 6.5.1).
    |
    */

    'services' => [
        ['name' => 'Unarmed Security Guard',          'icon' => 'fa-user-shield',    'desc' => 'Uniformed, unarmed officers providing visible deterrence, observation and reporting, access control, and customer assistance. The most common service for offices, residential communities, retail, and commercial properties.'],
        ['name' => 'Armed Security Guard',            'icon' => 'fa-shield-halved',  'desc' => 'Licensed, armed officers for higher-risk environments or assets — cash handling, high-value inventory, sensitive facilities, or locations with an elevated threat profile.'],
        ['name' => 'Mobile Patrol',                   'icon' => 'fa-car',            'desc' => 'Marked-vehicle patrols that cover multiple checkpoints or properties on a schedule. Cost-effective coverage for large sites, parking areas, or several locations that do not need a dedicated on-site officer.'],
        ['name' => 'Foot Patrol',                     'icon' => 'fa-person-walking', 'desc' => 'Officers patrolling a property on foot to cover interior corridors, stairwells, grounds, and areas a vehicle cannot reach — ideal for campuses, complexes, and large buildings that need a visible, roving presence.'],
        ['name' => 'Roving Patrol',                   'icon' => 'fa-arrows-rotate',  'desc' => 'Scheduled roving checks across a route or several zones — officers move between posts and checkpoints rather than staying at a fixed location, deterring activity through unpredictable, documented rounds.'],
        ['name' => 'Executive Protection',            'icon' => 'fa-user-tie',       'desc' => 'Close personal protection for executives, VIPs, and at-risk individuals — including advance work, secure transportation, and event coverage.'],
        ['name' => 'Concierge / Front Desk Security', 'icon' => 'fa-bell-concierge', 'desc' => 'Front-desk and lobby officers who blend hospitality with security — greeting visitors, managing sign-in, issuing badges, directing guests, and monitoring access in residential, corporate, and hotel settings.'],
        ['name' => 'Access Control Officers',         'icon' => 'fa-id-badge',       'desc' => 'Officers dedicated to managing entry points — credential checks, visitor logs, deliveries, and lobby or gate control.'],
        ['name' => 'Fire Watch',                      'icon' => 'fa-fire',           'desc' => 'Temporary fire-safety patrols required when sprinkler systems, alarms, or other fire protection are impaired or offline — keeping you compliant and covered.'],
        ['name' => 'Loss Prevention',                 'icon' => 'fa-tags',           'desc' => 'Retail and asset-protection officers focused on reducing shrink, deterring theft, and supporting investigations.'],
        ['name' => 'Event Security',                  'icon' => 'fa-calendar-check', 'desc' => 'Crowd management, access control, bag checks, and incident response for concerts, conferences, sporting events, and private functions.'],
        ['name' => 'School Security',                 'icon' => 'fa-school',         'desc' => 'Campus safety tailored to K-12 and higher education — visitor screening, access control, dismissal support, and emergency response.'],
        ['name' => 'Hospital Security',               'icon' => 'fa-hospital',       'desc' => 'Healthcare-specific coverage for emergency departments, behavioral health units, infant protection, and general campus safety, with de-escalation training.'],
        ['name' => 'Parking Enforcement',             'icon' => 'fa-square-parking', 'desc' => 'Officers managing parking areas and garages — enforcing permits and time limits, issuing citations, directing traffic flow, and deterring theft and vandalism in lots and structures.'],
    ],
];
