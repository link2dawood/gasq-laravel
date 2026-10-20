<?php

return [
    // Keep delivery inactive until a real booking, protected attachment, and inbox are verified.
    'enabled' => env('APPRAISAL_REVIEW_EMAILS_ENABLED', false),
    'reply_to' => env('APPRAISAL_REVIEW_REPLY_TO', 'info@getasecurityquote.com'),
    'calendly_token' => env('CALENDLY_TOKEN'),
    'webhook_signing_key' => env('CALENDLY_WEBHOOK_SIGNING_KEY'),
    'event_type_uri' => env('CALENDLY_APPRAISAL_EVENT_TYPE_URI'),
    'booking_url' => env('CALENDLY_APPRAISAL_BOOKING_URL'),
];
