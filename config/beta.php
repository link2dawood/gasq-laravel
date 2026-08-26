<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Keep this deployment out of search results
    |--------------------------------------------------------------------------
    |
    | Sends "X-Robots-Tag: noindex, nofollow, noarchive" on every response and
    | renders the matching <meta name="robots"> tag. Set BETA_NOINDEX=true on the
    | beta host and leave it false in production.
    |
    | Deliberately a header rather than a robots.txt "Disallow". Disallow only
    | stops crawling — a URL that leaks into an email, a WhatsApp message or a
    | LinkedIn post can still be indexed from that link alone, and because the
    | crawler is forbidden from fetching the page it never sees a noindex tag and
    | the listing sticks. Allowing the crawl and answering "noindex" is what
    | actually keeps a page out, and it covers the React SPA and PDFs too.
    |
    */

    'noindex' => (bool) env('BETA_NOINDEX', false),

    /*
    |--------------------------------------------------------------------------
    | Invite-only registration
    |--------------------------------------------------------------------------
    |
    | When true, a valid invite code is required to create an account. The site
    | itself stays publicly viewable on purpose: a guest can still land, run the
    | free estimate and see a Cost to Protect figure. That guest-to-signup step
    | is the single most important thing the beta needs to measure, and a
    | password wall over the whole site would hide it completely.
    |
    | Codes are compared case-insensitively after trimming. Give different groups
    | different codes and the analytics will show which channel actually converts.
    |
    */

    'invite_only' => (bool) env('BETA_INVITE_ONLY', false),

    'invite_codes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('BETA_INVITE_CODES', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Beta cap — how many get in, and until when
    |--------------------------------------------------------------------------
    |
    | This is how exposure is limited during the beta, rather than by locking out
    | the users we are paying to attract. Cap the headcount, cap the window, and
    | the amount of the product on show is bounded either way.
    |
    | BETA_MAX_ACCOUNTS  total non-admin accounts allowed. 0/null = unlimited.
    | BETA_CLOSES_AT     date/time after which registration closes (any format
    |                    strtotime understands, e.g. 2026-09-30 or
    |                    "2026-09-30 23:59:59"). Blank = no end date.
    |
    | Both double as scarcity: the register page shows remaining places and the
    | closing date, which gives outreach a reason to chase ("beta closes Friday").
    |
    */

    'max_accounts' => (int) env('BETA_MAX_ACCOUNTS', 0),

    'closes_at' => env('BETA_CLOSES_AT') ?: null,

];
