<?php

return [

    /*
    |--------------------------------------------------------------------------
    | How long viewer IP and device data is kept (spec 56)
    |--------------------------------------------------------------------------
    |
    | Set at 12 months on the client's instruction. The event trail itself is
    | permanent — spec 57 requires a document's history to stay auditable, so
    | the sweep clears the identifying columns and leaves every event in place.
    | Who did what and when survives; the address they did it from does not.
    |
    | Set GASQ_DOCUMENT_RETENTION_MONTHS in .env to change the window. 0 turns
    | the sweep off, which is a deliberate decision and not a default.
    |
    */

    'personal_data_retention_months' => max(0, (int) env('GASQ_DOCUMENT_RETENTION_MONTHS', 12)),

];
