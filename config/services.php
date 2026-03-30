<?php

/*
|--------------------------------------------------------------------------
| Stripe mode: test (dev) or live (production)
| Set STRIPE_MODE=test|live, or leave unset to use APP_ENV (production => live).
|--------------------------------------------------------------------------
*/
$stripeMode = env('STRIPE_MODE', env('APP_ENV') === 'production' ? 'live' : 'test');

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        // JavaScript Maps + Places + Directions — see config/maps.php for product scope
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'stripe' => [
        'mode' => $stripeMode,
        'secret' => $stripeMode === 'live' ? env('STRIPE_SECRET') : env('STRIPE_SECRET_TEST'),
        'publishable' => $stripeMode === 'live' ? env('STRIPE_PUBLISHABLE_KEY_LIVE') : env('STRIPE_PUBLISHABLE_KEY_TEST'),
        'webhook_secret' => $stripeMode === 'live'
            ? env('STRIPE_WEBHOOK_SECRET_LIVE', env('STRIPE_WEBHOOK_SECRET'))
            : env('STRIPE_WEBHOOK_SECRET_TEST', env('STRIPE_WEBHOOK_SECRET')),
        'success_url' => env('STRIPE_SUCCESS_URL'),
        'cancel_url' => env('STRIPE_CANCEL_URL'),
    ],

];
