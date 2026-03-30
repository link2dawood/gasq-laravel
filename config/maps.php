<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Maps product scope (gasq-laravel)
    |--------------------------------------------------------------------------
    |
    | OAuth keys (GOOGLE_CLIENT_*) are for login only. Browser Maps/Places use
    | GOOGLE_MAPS_API_KEY in config/services.php.
    |
    | Locked scope:
    | - primary: buyer job postings — structured address, lat/lng, place_id, map on job pages
    | - optional calculators: mobile patrol — route / directions when key is set
    |
    | Out of scope for now: vendor service-area polygons, drive-time marketplace matching,
    | and sub-state pricing via geolocation on static estimator dropdowns.
    |
    */

    'scope' => [
        'job_postings' => true,
        'mobile_patrol_calculator' => true,
        'vendor_service_area' => false,
        'marketplace_proximity' => false,
        'calculator_geolocation_general' => false,
    ],

];
