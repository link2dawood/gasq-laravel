# Phase 4: Vendor estimate review scheduling

Run all Phase 1–4 migrations with `php artisan migrate --force`. Configure a real mail transport, a queue worker (`php artisan queue:work --tries=1`), and Laravel's scheduler (`* * * * * php /path/to/artisan schedule:run`). `vendor-appointments:process` runs every minute to queue due reminders and dispatch pending appointment messages.

## Vendor availability

Open **Calendar** from the vendor navigation or **Manage availability & appointments** from Estimates. Select **Use my GASQ calendar**, choose an IANA time zone, and supply a meeting link, phone number, or location. Save the settings, then publish availability by selecting a date and local start/end times. Every complete 30-minute period is published as an individual slot. Overlapping slots, past times, nonexistent local times, and ambiguous repeated daylight-saving times are rejected. Publish ranges on one side of a daylight-saving transition at a time.

Remove unbooked slots from the calendar. Booked slots must be canceled before they can be removed. Canceling an appointment releases the slot for another buyer; remove the slot afterward if you no longer want to offer it. Calendar settings and availability can be changed only by their owning vendor.

Changes to the vendor's time zone or meeting details apply to newly published slots. Existing slots and appointments retain their original details. Disabling the native calendar stops new native bookings; it does not cancel already-booked appointments or their reminders.

## Buyer booking

New estimate emails sent while the native calendar is enabled use the built-in booking flow. The existing unique, private scheduling link displays that vendor's available slots. Buyers can select their own display time zone, choose a date and time, and confirm without registering an account. The saved estimate must still be Ready and its delivery link unexpired.

The vendor row is locked during publication, booking, and cancellation. Unique active slot and estimate reservations prevent two buyers taking the same slot or repeated form submissions creating duplicate appointments. Booking snapshots contact information and meeting details. Each estimate has one active appointment across its delivery attempts.

Confirmation emails are queued separately to the buyer and vendor. Each recipient sees the time in their own time zone, including UTC offset. Emails include meeting details and a private appointment management link. This management link remains usable after the estimate invitation expires, so a buyer can view or cancel an already-booked appointment. Cancellation queues notifications to both parties and suppresses pending confirmations/reminders. A buyer can return to the estimate link to book again while that invitation remains valid.

## Reminders and delivery status

The scheduler queues 24-hour and 1-hour reminders to both parties. A reminder is eligible only if the appointment existed at its due time. Appointments booked less than 24 hours ahead skip the 24-hour reminder; appointments booked less than an hour ahead receive confirmation only. Reminders have a five-minute processing window; overdue reminders are suppressed rather than sent with misleading timing. Keep the scheduler and worker running continuously.

Message records are unique by appointment, message kind, and audience. Workers claim a queued record once. Statuses are queued, sending, sent, failed, suppressed, or uncertain. Sent means mail transport acceptance, not confirmed inbox delivery. Transport errors and interrupted workers require manual delivery review and are not automatically retried. The vendor calendar displays confirmation and reminder statuses for each appointment.

The PDF password is never sent by these messages or released automatically. Provide it separately during the review.

## Existing external scheduling

Existing Bookafy/Calendly estimate links continue to redirect to the configured provider. Vendors who leave native scheduling disabled keep that flow for new estimate emails. Native calendar availability is managed within GASQ; it does not synchronize busy events with Bookafy, Google Calendar, or Outlook, and it does not create video meetings automatically. Publish only times the vendor can attend and provide an existing meeting link/phone number. Bookafy-based appraisal reviews retain their existing reconciliation and reminders; these native estimate appointments use the new command.

Tests use fake mail and queues and do not send real messages:

`vendor/bin/phpunit tests/Feature/VendorAppointmentSchedulingTest.php tests/Feature/VendorEstimateDeliveryTest.php tests/Feature/VendorLeadEstimateTest.php tests/Feature/VendorBuyerLeadTest.php`
