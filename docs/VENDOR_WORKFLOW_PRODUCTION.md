# Vendor-to-buyer workflow: release and production verification

## Implemented flow

Vendors share private invitation links. Buyers submit contact details and scope. Only the owning vendor can review leads, prepare estimates, and create protected PDF deliveries. Delivery snapshots retain their original figures, recipient, protected bytes, and checksum. Native appointment scheduling provides vendor availability, time-zone-aware booking, double-booking protection, confirmations, cancellation, and 24-hour/1-hour reminders.

After a native appointment ends, the vendor explicitly attests that the review occurred with the buyer and authorized decision-maker and enters the PDF's original password. The application checks the matching password verifier, appointment, recipient, estimate, and delivery before recording release time, releasing vendor, and review. An external Bookafy/Calendly review can instead be manually attested with a reference and actual start/end times including UTC offset. External attestation is vendor-entered evidence; it is not an automated provider attendance check.

Before release only a one-way password verifier is stored; there is no recoverable password in the delivery record or queue. After explicit release, the password is stored encrypted using the application key for 30 days of controlled buyer access. Preserve and securely back up APP_KEY: rotating or losing it makes existing encrypted tokens and released passwords unreadable. Older delivery records without a verifier cannot be released through this gate; prepare a fresh protected delivery.

The original email and appointment page link to the protected PDF access page. After release, the buyer requests a verification code sent only to the saved recipient email. Codes expire after 10 minutes and are limited to five attempts, with a 60-second resend cooldown. A successful code creates a 30-minute session scoped to that delivery. Revealing the password and downloading the protected PDF require separate CSRF-protected POST actions and are logged. GET requests and email scanners cannot reveal the password. Release does not email the password or automatically send another message; the vendor may direct the buyer to the access link already in the estimate email.

Vendors can revoke online access. Revocation suppresses queued delivery, blocks booking through that delivery, and blocks password/code/download requests. Existing appointments can still be managed or canceled. Revocation cannot recall emailed attachments, downloaded PDFs, or passwords already disclosed. Vendors chose the original password and can disclose it outside the application; the application cannot prevent off-platform disclosure. Offline PDF openings and forwarding are unobservable. Audit records describe app activity, not proof of reading or immutable forensic evidence.

## Production prerequisites

1. Back up the database, private storage, and APP_KEY. Preserve existing private files when deploying. Deploy code and run `php artisan migrate --force` (all Phase 1–5 migrations are included).
2. Set APP_ENV=production, APP_DEBUG=false, an HTTPS APP_URL, a valid APP_KEY, and SESSION_SECURE_COOKIE=true. Keep the local filesystem disk outside the public web root. Use a persistent storage volume and restrict file permissions.
3. Configure and verify real outgoing mail. Preserve existing internal-copy settings for estimate emails. Log/array mailers and failover chains containing them must not count as successful production delivery.
4. Run a supervised queue worker, for example `php artisan queue:work --tries=1`. Set worker timeout and queue retry-after consistently so a slow job is not reclaimed while still running. Restart workers on deployment.
5. Run Laravel's scheduler every minute: `* * * * * cd /path/to/app && php artisan schedule:run`. Appointment reminders require both the scheduler and worker. Investigate stale Sending/uncertain records before deliberately retrying.
6. Enable each vendor's GASQ calendar and supply its actual meeting details and free slots, or configure the existing external Bookafy/Calendly booking URL. Native availability does not synchronize external busy events or automatically create video meetings.
7. Run `php artisan vendor-workflow:preflight --production`. It checks schema, encryption, private file write/read, mail/queue configuration, HTTPS, environment, debug, and secure cookies without sending email or displaying secrets. It does not prove worker/scheduler uptime, SMTP delivery, or database concurrency on the production host.

Do not call the system production verified from local SQLite tests alone. Perform the following staging acceptance on the same database engine, queue driver, and mail transport used in production.

## Staging acceptance

- Use two vendor accounts and buyer inboxes controlled by the tester. Verify distinct invitation links and vendor-specific lead/estimate/history isolation, including direct URL attempts against the other vendor's records.
- Submit a buyer request, prepare a Ready estimate, and send once. Verify actual inbox receipt, PDF attachment, masked preview, private scheduling link, and configured internal copies. Check that duplicate form submissions create no duplicate email.
- Open the attachment with no password and a wrong password (both must fail). Keep the vendor's chosen password available but do not disclose it early. Check the downloaded attachment's checksum against its delivery record.
- Publish vendor slots in a non-UTC time zone. Have two browser sessions compete for one slot. Only one may book; verify the other cannot choose a foreign vendor's slot.
- Verify real confirmations to both parties, the date/time/offset, meeting details, and private manage link. Verify cancellation suppresses pending reminders. Validate actual 24-hour and 1-hour reminders with a controlled appointment; do not alter production server time.
- Confirm release is blocked before the scheduled end, after cancellation, with another vendor's account, with a wrong password, and without the vendor's review attestation. After a genuine completed test review, release and verify the recorded UTC time and vendor.
- From a fresh buyer browser, verify no password on GET, OTP only to the saved email, incorrect/expired code rejection, and delivery-specific verification. Reveal and download; verify the original protected PDF bytes and audit events. Test expiry and revoke access, then check that even a previously verified session is refused.
- Confirm public tokens, OTPs, and PDF passwords do not appear in app analytics or logs. Record acceptance outcomes, mail-provider IDs, worker/scheduler monitoring, and any unresolved failures.

## Automated validation

`php -d memory_limit=512M vendor/bin/phpunit -c phpunit-vendor-workflow.xml`

This focused suite covers the new workflow plus existing Bookafy, report-email, internal-copy, and report formatting regressions. Its GitHub workflow runs on PHP 8.2 and 8.3. Tests use fake mail/queues and an isolated SQLite database; they do not send actual buyer emails. The six legacy V24 test providers now resolve fixtures without requiring an application during test discovery.

The repository's full suite remains a separate release signal. Existing failures must be triaged on their own merits; a green focused workflow suite is not a claim that the whole application or live infrastructure has passed. Retain test output and compare failures against the previous commit before attributing them to this feature.
