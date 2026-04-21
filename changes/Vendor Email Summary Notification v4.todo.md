# TODO: Vendor Email Summary Notification v4

Source: `changes/Vendor Email Summary Notification v4.docx`

Current repo status:

- `resources/views/pages/open-bid-offer.blade.php` already renders a vendor-facing summary page with the same overall title.
- `app/Http/Controllers/OpenBidOfferController.php` already supplies most of the top-level values.
- The current implementation is broader than this v4 doc in some places, but it also uses placeholder project-detail answers and a different credits-to-respond formula.

## Implementation TODO

- [x] Audit this v4 doc against the existing `open-bid-offer` page and mark each output field as implemented, partial, or missing.
- [ ] Decide whether this v4 doc should:
  - [x] replace the current `open-bid-offer` layout
  - [ ] become a separate compact variant
  - [ ] become an actual email template distinct from the web page

## Header and Alert Block

- [x] Keep the top title aligned with the spec:
  - [x] `Email Summary Notification to Security Vendor`
  - [x] `ALERT! GASQNOW New Security Project in {city}, {state}`
- [x] Confirm the “Type of Service Requested” field uses the correct canonical source.
- [x] Confirm masked buyer email and masked buyer phone formatting match the v4 example.

## Validation and Offer Summary

- [x] Confirm the summary shows:
  - [x] phone number verified
  - [x] decision maker verified/validated
  - [x] budget verified/validated
  - [x] total bid offer value
- [x] Replace placeholder logic in `OpenBidOfferController.php` so validation flags are based on actual questionnaire/job data.
- [x] Confirm project location renders as:
  - [x] city
  - [x] state
  - [x] zip code

## Credits and Response Metrics

- [x] Align the page with the v4 summary values:
  - [x] total credits to respond
  - [x] responses `X/5`
- [x] Resolve the credits formula mismatch:
  - [x] current controller uses `floor(bidOfferValue / 100)`
  - [x] v4 example implies `393,120 -> 3,931`
- [ ] Confirm whether credits-to-respond should round, floor, ceil, or use a configurable pricing rule.
- [ ] Confirm whether the denominator in `Responses: 3/5` is fixed at 5 or should be dynamic.

## Project Details Block

- [x] Update the “Project Details” section to match the v4 copy more closely.
- [x] Confirm the first line maps correctly:
  - [x] `What is this service for?`
  - [x] example answer: `New Purchase of Services`
- [x] Confirm the decision-maker question uses the exact v4 phrasing:
  - [x] `Are you the person authorized to make a final buying commitment with the vendor or approve payment for the proposed services?`
- [x] Replace placeholder values in `OpenBidOfferController.php` for:
  - [x] `authorizedDecisionMakerText`
  - [x] `priceShoppingText`
  - [x] `hiringDecisionLikelihood`
  - [x] `hiringUrgency`
- [x] Add or map the missing source questions behind those fields:
  - [x] are you price shopping
  - [x] how likely are you to make a hiring decision
  - [x] what is your urgency for starting the project
- [ ] Decide whether these answers come from:
  - [ ] the buyer posting form
  - [ ] the prequalification questionnaire
  - [ ] a separate questionnaire payload

## Coverage Summary

- [x] Confirm the v4 page shows these values exactly:
  - [x] total hours per day of coverage
  - [x] total number of days per week of coverage
  - [x] total weekly hours hired to work
  - [x] total monthly hours hired to work
  - [x] total number of weeks
  - [x] total months of coverage
  - [x] total staff required
  - [x] total term/annual hours
- [x] Review the current math in `OpenBidOfferController` and confirm whether it should come from:
  - [x] questionnaire coverage fields
  - [x] legacy scheduling fields
  - [ ] a normalized computed service
- [x] Confirm `hoursPerDay` should not depend on `daily_start_time`/`daily_end_time` if the buyer flow already stores `hours_per_day`.
- [x] Confirm `daysPerWeek` should not depend on `coverage_days` if the buyer flow already stores `days_per_week`.

## CTA and Flow

- [x] Confirm the CTA label remains:
  - [x] `I ACCEPT BID OFFER`
- [ ] Confirm whether clicking the CTA should:
  - [x] view the job details page
  - [ ] accept the offer directly
  - [ ] open a dedicated vendor response flow

## Data and Persistence

- [x] Check whether the current job/bid schema already stores every value needed by this v4 summary.
- [ ] Add migration(s) only if this v4 output depends on fields not stored anywhere yet.
- [x] Update eager loading or data mapping in `OpenBidOfferController` if buyer questionnaire answers need to be exposed here.
- [x] Decide whether summary values should be recomputed live or snapshotted at posting time.

## Rendering Strategy

- [ ] Decide whether this should be:
  - [ ] a web page only
  - [ ] a mail template only
  - [ ] both, with shared partials
- [ ] If both, extract shared sections into a reusable partial so the web and email versions don’t drift.
- [ ] If email delivery is required, add the actual mailable/notification path that sends this summary to vendors.

## Testing

- [x] Add or update tests for:
  - [x] correct masking of buyer contact info
  - [x] correct visibility for vendor, buyer, and unauthorized users
  - [x] correct rendering of project details answers
  - [x] correct credits-to-respond calculation
  - [x] correct coverage summary math
  - [ ] correct CTA destination or action

## Open Product Decisions

- [ ] Confirm the exact credits-to-respond formula used by v4.
- [ ] Confirm whether v4 is the canonical replacement for the current open bid offer view or just one notification format.
- [x] Confirm the source of “price shopping”, “ready to hire”, and “urgency” answers, since they are not fully wired in the current controller.
- [ ] Confirm whether the response count denominator should always be 5.
