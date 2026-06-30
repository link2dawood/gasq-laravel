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

    'gasq' => [
        // Address that receives admin lead alerts when new buyer leads land.
        'admin_alert_email' => env('GASQ_ADMIN_ALERT_EMAIL', 'info@getasecurityquote.com'),

        // Address that receives contact-form submissions.
        'contact_email' => env('GASQ_CONTACT_EMAIL', 'info@getasecurityquotenow.com'),

        // Master "owner" password for report PDFs. Recipients open with no
        // password and cannot print/copy; entering this password in a PDF
        // reader unlocks printing/copying. Leave unset to lock fully (random
        // owner password, no override). Keep this secret — set it in .env.
        'report_master_password' => env('REPORT_MASTER_PASSWORD'),
    ],

    'hubspot' => [
        // HubSpot "Log to CRM" BCC address — emailed reports are BCC'd here so
        // they auto-log onto the matching contact's HubSpot timeline.
        'bcc' => env('HUBSPOT_BCC', '45427418@bcc.hubspot.com'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        // Twilio Verify — used for phone-verification OTPs. When set, PhoneOtpService
        // delegates to Verify (exempt from US A2P 10DLC) instead of generating + sending
        // codes via raw Messages API. Leave unset to fall back to the legacy SMS path.
        'verify_service_sid' => env('TWILIO_VERIFY_SERVICE_SID'),
    ],

];
