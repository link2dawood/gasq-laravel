<?php

return [
    // Keep delivery inactive until a real booking, protected attachment, and inbox are verified.
    'enabled' => env('APPRAISAL_REVIEW_EMAILS_ENABLED', false),
    'reply_to' => env('APPRAISAL_REVIEW_REPLY_TO', 'info@getasecurityquote.com'),

    // Which scheduling account the appraisal flow reads from: calendly or bookafy.
    'provider' => env('APPRAISAL_SCHEDULING_PROVIDER', 'calendly'),

    'calendly_token' => env('CALENDLY_TOKEN'),
    'webhook_signing_key' => env('CALENDLY_WEBHOOK_SIGNING_KEY'),
    'event_type_uri' => env('CALENDLY_APPRAISAL_EVENT_TYPE_URI'),
    'booking_url' => env('CALENDLY_APPRAISAL_BOOKING_URL'),

    'bookafy' => [
        'api_key' => env('BOOKAFY_API_KEY'),
        // Bookafy signs nothing, so the callback URL carries this secret path segment.
        'webhook_token' => env('BOOKAFY_WEBHOOK_TOKEN'),
        // The staff account whose calendar the appraisal bookings land on.
        'staff_email' => env('BOOKAFY_STAFF_EMAIL'),
        // Only bookings of this service become appraisal appointments.
        'service_id' => env('BOOKAFY_APPRAISAL_SERVICE_ID'),
        'booking_url' => env('BOOKAFY_APPRAISAL_BOOKING_URL'),
        // Management links as the booking confirmation spells them; {token},
        // {cancel_token} and {update_token} are replaced per appointment.
        'cancel_url' => env('BOOKAFY_CANCEL_URL'),
        'reschedule_url' => env('BOOKAFY_RESCHEDULE_URL'),
        // The booking question that carries the review id, when one is configured.
        'reference_field' => env('BOOKAFY_REFERENCE_FIELD', 'Reference'),
    ],
];
