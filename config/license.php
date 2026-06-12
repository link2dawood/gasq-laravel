<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Calculator license / usage agreement
    |--------------------------------------------------------------------------
    | Master switch for the click-through license gate. Kept OFF until the
    | wording is reviewed by counsel and the flow has been tested, so it can
    | never block production usage prematurely. Flip to true to enforce.
    */
    'enabled' => env('LICENSE_GATE_ENABLED', false),

    // Bump a version string whenever its terms change — users who accepted an
    // older version are re-prompted to accept the new one.
    'buyer_version' => '2026-06-01',
    'seller_version' => '2026-06-01',

    // Internal inbox already used elsewhere; surfaced here for the legal pages.
    'contact_email' => 'info@getasecurityquotenow.com',
];
