# Phase 3: Estimate email and PDF delivery

Deploy all Phase 1–3 migrations with `php artisan migrate --force`. Configure a real mail transport and a queue worker (`php artisan queue:work --tries=1`). The deployment workflow restarts existing workers but does not provision one. With a synchronous queue the send runs immediately; with a background queue the vendor sees Queued until a worker handles it.

Set the existing `APPRAISAL_SCHEDULING_PROVIDER` to `bookafy` or `calendly`, with its HTTPS `BOOKAFY_APPRAISAL_BOOKING_URL` or `CALENDLY_APPRAISAL_BOOKING_URL`. Only the selected provider's own domain/subdomains are accepted. Phase 4 also lets each vendor enable a native GASQ calendar, which uses the same private delivery link without external provider configuration; see VENDOR_APPOINTMENT_SCHEDULING.md. No Bookafy API calls or subscription registration are required to link to its booking page.

## Sending an estimate

Open a saved, Ready estimate with a current validity date. Under **Email estimate to buyer**, choose an 8–32 character PDF password and select **Send estimate to buyer**. The recipient is the buyer email stored on the estimate; a submitted recipient override is ignored. The vendor's email is the reply-to address. Existing **Estimate delivery emails** internal-copy settings apply.

The request generates a protected PDF from saved inputs, stores it on the private local disk, and records a checksum and a snapshot of the recipient, estimate metadata, figures, and booking destination. The worker receives only the delivery ID. The password is not flashed back to forms, included in the email, or serialized in the job. Phase 5 stores a one-way verifier at preparation; only explicit post-review release stores a recoverable password, encrypted under APP_KEY for the buyer access portal. See VENDOR_WORKFLOW_PRODUCTION.md. Keep it available for the appointment and provide it separately.

The professional email includes masked financial figures, the estimate number and validity date, a protected PDF attachment, and a unique private scheduling link. Later changes to the estimate do not alter that attempt's PDF or email snapshot. Archiving the estimate before the worker sends it prevents the send.

Each rendered send form has a request key. Repeating the same request reuses its delivery record. The job atomically claims a queued record, so duplicate queue jobs cannot send it twice. A fresh form allows an intentional new delivery attempt with a new snapshot and link.

## Delivery status

- **Queued:** saved and awaiting the worker.
- **Sending:** claimed by a worker.
- **Sent (transport accepted):** the application's mail transport accepted the message. This does not verify inbox delivery or a read receipt.
- **Failed before send:** the estimate became unavailable, its validity expired, its protected attachment failed verification, or mail is not configured.
- **Needs delivery review:** transport acceptance is uncertain or a worker stopped during sending. No automatic resend occurs. Check the mail provider before deliberately sending again.

The estimate dashboard shows the latest attempt's status. Its edit screen lists all attempts, recipient, timestamps, and safe failure codes. SMTP exception text is not stored. An interrupted worker may leave Sending until the queue marks the job failed; investigate stale attempts before resending.

## Scheduling link

Each delivery has a random 64-character token, stored encrypted with a hash for lookup. Its link redirects to the configured booking page with a unique `gasq_estimate_<request UUID>` reference. Existing booking URL query parameters are preserved. The link expires at the end of that snapshot's validity date. It does not expose buyer contact details or pricing in the redirect URL.

The first link opening is recorded. Email scanners can open links, so this event is not a confirmed appointment, buyer read receipt, or proof of attendance. External booking reconciliation and password release are not automated by this phase. Phase 4 adds native slot booking, appointment confirmation, and reminders for vendors who enable the GASQ calendar. No token is recorded by the application analytics middleware.

Validation uses fake mail, queues, and private storage; no real buyer messages are sent by the tests:

`vendor/bin/phpunit tests/Feature/VendorEstimateDeliveryTest.php tests/Feature/VendorLeadEstimateTest.php tests/Feature/VendorBuyerLeadTest.php`
