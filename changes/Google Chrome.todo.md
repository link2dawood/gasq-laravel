# TODO: Email Summary Notification to Security Vendor

Source: `changes/Google Chrome.docx`

Current repo status:

- `resources/views/pages/open-bid-offer.blade.php` already renders a vendor-facing project summary.
- `app/Http/Controllers/OpenBidOfferController.php` already builds the page payload.
- Several fields in the current controller are still placeholder values or are derived from older job fields that may not match the questionnaire flow.

## Implementation TODO

- [ ] Audit the document against the existing open bid offer page and mark each item as: already implemented, partially implemented, or missing.
- [x] Update `resources/views/pages/open-bid-offer.blade.php` to match the document’s output order and labeling more closely.
- [x] Keep the top alert banner aligned with the spec:
  - [x] `ALERT! GASQNOW New Security Project in {city}, {state}`
  - [x] service type summary
  - [x] masked buyer email and phone
- [x] Confirm the masking rules for buyer contact data are correct and consistent.
- [x] Verify the page shows these validation badges or equivalents correctly:
  - [x] phone verified
  - [x] decision maker verified/validated
  - [x] budget verified/validated
- [x] Confirm `decisionMakerValidated` is based on actual questionnaire data, not just `buyer !== null`.
- [x] Confirm `budgetValidated` is based on the intended questionnaire/budget answers, not only `budget_min` or `budget_max`.
- [x] Align the location block with the document:
  - [x] city
  - [x] state
  - [x] zip code
- [x] Align the coverage summary block with the document:
  - [x] total hours per day
  - [x] total number of days per week
  - [x] total weekly hours
  - [x] total monthly hours
  - [x] total number of weeks
  - [x] total months of coverage
  - [x] total staff required
  - [x] total term/annual hours
- [x] Review how coverage values are derived in `OpenBidOfferController` and make sure the math matches the questionnaire inputs rather than legacy scheduling fields.
- [x] Check whether `hoursPerDay` should come from questionnaire fields instead of `daily_start_time` and `daily_end_time`.
- [x] Check whether `daysPerWeek` should come from questionnaire fields instead of `coverage_days`.
- [x] Check whether `totalWeeks` should use questionnaire coverage duration if service end date is optional or absent.
- [x] Add the missing value from the document:
  - [x] appraisal fee
- [x] Confirm the formula for:
  - [x] total credits to respond
  - [x] appraisal fee
  - [x] total bid offer value
- [ ] Align the response status block:
  - [ ] `Responses: X/5 Professionals have accepted bid offer`
  - [ ] confirm whether the denominator should always be `5` or should remain dynamic
- [x] Expand the Project Details section so it reflects the questionnaire answers from the doc:
  - [x] final decision maker answer
  - [x] approval authority
  - [x] budget already approved
  - [x] prepared to move forward if accepted
  - [x] multiple locations yes/no
  - [x] locations count when applicable
  - [x] property type / industry
  - [x] selected service types
  - [x] request type: new / replacement / expanded / emergency
  - [x] desired service start/end dates
  - [x] hours per day
  - [x] days per week
  - [x] weeks per year
  - [x] officers required per 8-hour shift
  - [x] approved budget answer
  - [x] budget format
  - [x] monthly budget when selected
  - [x] annual budget when selected
- [ ] Replace current placeholders in `OpenBidOfferController.php`:
  - [ ] `priceShoppingText`
  - [ ] `hiringDecisionLikelihood`
  - [ ] `hiringUrgency`
  - [ ] `authorizedDecisionMakerText`
- [x] Decide whether the page should show “Project Details” exactly as answer/value pairs instead of the current shorter narrative labels.
- [x] Confirm whether “Type of Service Requested” should use `category`, `title`, first selected service type, or a computed label.
- [ ] Confirm whether the CTA should remain:
  - [ ] `I ACCEPT BID OFFER`
  - [ ] route to `jobs.show`
  - [ ] or whether it should trigger a dedicated accept/respond action
- [ ] Review whether the page should be rendered as an email-style notification, a web page, or both.
- [ ] If this output is also emailed, decide whether there should be:
  - [ ] a Blade mail template
  - [ ] a shared partial for web + email reuse
- [x] Check whether current job/bid persistence already stores all values required by this summary page.
- [ ] Add migration(s) only if the summary requires fields that do not exist anywhere yet.
- [x] Update controller eager loading if extra buyer/job metadata is needed for the summary.
- [x] Add or update tests for:
  - [x] vendor can view their own bid summary
  - [x] buyer can view their own bid summary
  - [x] unauthorized users are blocked
  - [x] masked contact details render correctly
  - [x] derived coverage values render correctly
  - [x] validation flags and money totals render correctly

## Open Product Decisions

- [ ] Confirm whether the denominator in `3/5 Professionals have accepted bid offer` is fixed at 5 or should be the real number of invited vendors.
- [ ] Confirm the appraisal fee formula and whether it is always shown.
- [ ] Confirm whether this page is strictly a vendor-facing landing page or also the exact body/content for an outbound notification.
- [ ] Confirm which data source is canonical for coverage math: questionnaire fields, legacy scheduling fields, or both.
