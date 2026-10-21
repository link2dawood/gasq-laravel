# Buyer appraisal report reviews

**October 22 update:** The user selected the existing Bookafy account instead of Calendly. Its Free plan blocks Zoom and authenticated API access. See [Bookafy account review and remaining setup](BOOKAFY_APPRAISAL_SETUP.md). The Calendly implementation below remains inactive and requires a genuine Bookafy provider adapter and live acceptance before the selected replacement can launch.

## Platform recommendation and account access

Use Calendly for booking and appointment management, with its Zoom integration as the event location. GASQ sends the combined personalized email and protected PDF. Calendly's existing confirmation/calendar notification stays enabled for bookings made before the final appraisal is ready.

Calendly webhooks require Standard or above. Calendly currently lists Standard at US$10/seat/month billed annually; verify monthly billing and the account's existing subscription before purchasing anything. Zoom's integration creates and updates meeting details on booking/rescheduling. Zoom Scheduler is an alternative with created/canceled webhooks and management URLs, but this implementation uses the requested Calendly workflow and does not silently substitute platforms.

Sources reviewed October 20, 2026:

- https://developer.calendly.com/docs/getting-started/frequently-asked-questions
- https://calendly.com/pricing
- https://calendly.com/integration/zoom/
- https://developers.zoom.us/docs/api/scheduler/events/
- https://zoom.us/pricing/scheduler

Calendly and Zoom were not connected to this session, and local configuration contained no Calendly or Zoom credentials. Their actual plans, calendars, host identities, Zoom permissions, and mailbox monitoring remain unverified. Do not treat the platform recommendation as an account audit.

## Admin workflow

1. Open **Admin → Buyer Appraisal Reviews** (`/admin/appraisal-reviews`). Create a review from the correct buyer's saved Workforce/Budget calculator state and verified buyer identity.
2. Share the buyer-specific Calendly link. It carries a review UUID in `utm_content`, allowing signed webhooks to associate a booking with the correct report, without guessing from email alone. The invitee email and configured event type must also match. If an appointment was already booked without the review link, an administrator can explicitly link its invitee API URI; buyer and event checks still apply.
3. Review the calculator figures and finalize the approved report with an 8–32 character ASCII opening password. This freezes the generated PDF, report number, preparation date, buyer identity, findings, and file checksum. Revised reports require a new review. Draft calculator changes never replace a final attachment.
4. Retain the password outside this workflow and provide it personally during the actual appointment. **The application does not store the password, expose it on appointment/download pages, attach it to calendar invitations, or release it on a timer.** No password-release job or endpoint exists.
5. GASQ sends the combined report only after both finalization and a current appointment. Either order works. When the appointment precedes the report, the normal booking confirmation is sent first. All generated messages include current management links and a monitored reply-to address.

PDFs live under the private local disk, not public storage. They are generated with required encryption and checked for a PDF encryption dictionary. Checksums bind outgoing attachments to the finalized file. The full appraisal is generated rather than the existing masked, password-free buyer preview.

## Calendly setup

Keep `APPRAISAL_REVIEW_EMAILS_ENABLED=false` until acceptance testing is complete. Set these through server environment configuration, never commit real values:

```dotenv
APPRAISAL_REVIEW_EMAILS_ENABLED=false
APPRAISAL_REVIEW_REPLY_TO=info@getasecurityquote.com
CALENDLY_TOKEN=
CALENDLY_WEBHOOK_SIGNING_KEY=
CALENDLY_APPRAISAL_EVENT_TYPE_URI=
CALENDLY_APPRAISAL_BOOKING_URL=
```

Use a dedicated report-review event type, connect the host's calendar and Zoom, and subscribe `invitee.created` and `invitee.canceled` to `https://YOUR_GASQ_HOST/webhooks/calendly/appraisals`. Supply a signing key when creating the subscription. Request signatures are verified with HMAC-SHA256 and a three-minute tolerance before any API request. GASQ re-fetches authoritative invitee and event resources using the configured account, restricts API hosts/paths, and matches the dedicated event type. Unrelated bookings are ignored.

Run `php artisan appraisals:preflight`. It reports configuration presence and account/event/webhook access without printing credentials or sending mail. It cannot determine every purchased feature; verify the actual account plan in Calendly and Zoom. Subscribe using Calendly's webhook setup UI/API and retain the subscription ID operationally.

Rescheduling is a cancellation plus a new booking in Calendly. Both delivery orders are supported: the new appointment supersedes the old, cancellation applies only to its own invitee, and repeated events do not resend the report. Existing Zoom details and management links are refreshed on every event and through `appraisals:process --sync`.

## Attendance, reminders, and delivery reliability

The final combined appraisal delivery follows the **Estimates** BCC switch under **Admin Settings → Process Email Copies (BCC)**, defaulting to info@getasecurityquote.com. Booking, change, cancellation, and reminder emails do not acquire this process copy. See [Process email copies](PROCESS_EMAIL_COPIES.md).

Calendly supports reconfirmation Workflows, but the documented invitee API returns reconfirmation timestamps without a reusable action URL. A native reconfirmation Workflow can be enabled in Calendly as a separate platform message. The combined GASQ email therefore uses a **custom GASQ attendance action**, not a fabricated Calendly confirmation link. Its signed, expiring appointment link first shows details; confirmation requires a checkbox and POST, so email scanners cannot confirm attendance merely by following a GET. It does not unlock the report or attest that the appointment actually occurred.

Run the Laravel scheduler every minute and a persistent queue worker. `appraisals:process` schedules 24-hour and 1-hour reminders, using UTC internally and displaying the buyer's IANA zone, abbreviation, and UTC offset, including DST. Late bookings skip reminder times already passed. Reminders are suppressed if more than five minutes late, after cancellation/supersession, or after the appointment. Provider refresh runs every five minutes to recover missed updates. Disable equivalent Calendly reminder Workflows for this event type to prevent duplicate reminders; preserve the initial booking/calendar confirmation.

Each report has one unique delivery key; each appointment reminder/change message has its own unique key. A worker durably claims a message, rechecks the report/appointment under row locks, and never resends a sent/claimed message automatically. If a mail-service response or worker interruption makes acceptance uncertain, the delivery remains `uncertain`/`sending` for manual investigation. Exactly-once mailbox delivery cannot be guaranteed over SMTP; avoiding automatic retries after ambiguous acceptance prevents duplicate reports at the cost of requiring operator reconciliation. Confirm the provider's delivery log before any manual retry. `appraisals:process` reports these records.

## Acceptance test and activation

Automated tests fake scheduling and mail, and do not prove a real account link or inbox delivery. The approved test recipient is **info@getasecurityquote.com**.

Create a dedicated sample review for that inbox and a real test Calendly booking with Zoom. Finalize a sample protected PDF; retain its password separately. Then:

```sh
php artisan appraisals:test-email REVIEW_UUID
php artisan appraisals:test-email REVIEW_UUID --send
```

The first prepares a private HTML preview. `--send` submits the real protected test to the configured mail service, even while automatic delivery is inactive. It requires a finalized sample review for the approved inbox and verifies its real Calendly booking before sending. Check inbox delivery and PDF rejection without a password; confirm personalized financial fields, number/date, zone, Zoom link, custom attendance confirmation, Calendly reschedule/cancel actions, a replacement meeting, both reminders, cancellation suppression, and duplicate webhooks. Check no password appears in any email, calendar invite, reminder, link, or page. Do not enable native workflows that contain the PDF password.

After acceptance, enable `APPRAISAL_REVIEW_EMAILS_ENABLED=true`, refresh the configuration cache, and restart workers. Cancel the dedicated test booking when testing finishes. No live rollout or acceptance email was performed solely by adding this code.

Deployment requires the new migration, private storage, configured SMTP, scheduler, and queue worker. No new Composer dependency is required. Planning allowance for account setup and live acceptance is 1–2 business days after access and a test booking are available; this is conditional, not a committed launch date. Engineering fees require the agreed billing rate; do not treat the provider subscription as an implementation quote.
