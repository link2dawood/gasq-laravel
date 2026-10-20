# Process email copies

Open **Admin Settings → Process Email Copies (BCC)** to edit the internal copy address and independently enable vendor-weighted report copies and estimate delivery copies. The default address is `info@getasecurityquote.com`; both processes default to enabled. Saving persists the settings in the existing `settings` table under `email_copies` and clears their cache. No new schema or SMTP/provider change is required.

The rule is deliberately attached to selected processes, rather than a global mail listener:

| Setting | Included deliveries |
| --- | --- |
| Vendor-weighted reports | Protected `vendor-weighted-rates` report emails |
| Estimates | Workforce/Budget master estimate, buyer edition, allocation PDF, vendor-submitted estimate PDF, and the final combined buyer appraisal report |

Booking confirmations, appointment changes/cancellations, reminders, private-estimate invitations, access/verification codes, password resets, and other transactional messages do not acquire this new BCC. Existing general calculator-report and CRM routing remain separately managed; the estimates switch does not disable an existing HubSpot CRM BCC. Private vendor-weighted messages do not acquire a CRM BCC.

Copies are BCC only. The main recipient and approved message body are preserved. The copy address is excluded if it is already a primary recipient, with case-insensitive deduplication against existing BCC addresses. Request-supplied CC/BCC data cannot alter the report's internal copy settings. Routing addresses are omitted from the report send confirmation and from the vendor-facing copy explanation.

Only administrators can save the copy address and switches. The address must be a single valid email address. The generic settings form cannot bypass this validation. A malformed value written outside the validated form is ignored at send time instead of becoming an invalid recipient. Settings are read for each message, including by queue workers, so no configuration-cache rebuild or worker restart is needed after an admin edit.

The final buyer appraisal email remains subject to its existing report-ready/appointment gates and inactive-until-tested feature flag. This setting does not activate that workflow or release any PDF password.

`ProcessEmailCopiesTest` covers defaults, scope, admin-only editing, validation and bypass rejection, persistence/cache refresh, recipient deduplication, per-process disable behavior, preservation of CRM routing, and exclusion of appointment messages. `CalculatorReportEmailTest` checks protected vendor reports use BCC, preserve their approved cover message and reply-to, and never include the PDF password.
