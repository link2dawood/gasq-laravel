# TODO: Buyer Online Posting Form (Client-Facing)

Source: `changes/Job Offer Questionaire 04-18-2026.docx`

Current repo status:

- `resources/views/jobs/create.blade.php` already implements a large portion of this buyer posting form.
- `app/Http/Requests/StoreJobPostingRequest.php` already validates many of the specified fields.
- The current implementation includes extra qualification/risk questions that are not the primary focus of this document, while several exact form-spec details are still missing or only partially aligned.

## Implementation TODO

- [ ] Audit the full spec against `resources/views/jobs/create.blade.php` and `StoreJobPostingRequest.php` and mark each numbered field as: implemented, partial, or missing.
- [x] Align the page header with the spec:
  - [x] title: `Post Your Security Service Request`
  - [x] subtitle about scope, job offer announcement, and vendor responses
- [x] Add or improve progress-section UI so the form clearly reflects these stages:
  - [x] Contact Information
  - [x] Decision Authority
  - [x] Service Location
  - [x] Service Request Details
  - [x] Schedule and Staffing
  - [x] Duties and Site Conditions
  - [x] Budget and Offer Terms
  - [x] Compliance Requirements
  - [x] Posting Terms and Submission

## Section 1: Contact Information

- [ ] Confirm all current fields match the spec exactly:
  - [ ] full name
  - [ ] job title
  - [ ] company / property / organization name
  - [ ] email address
  - [ ] mobile phone number
  - [ ] preferred contact method
  - [ ] best time to contact
- [x] Add the missing SMS verification action for mobile number.
- [ ] Decide whether SMS verification should be:
  - [ ] a real OTP flow in-form
  - [ ] a required precondition using the existing phone verification system
  - [ ] a separate verification button with status

## Section 2: Decision Authority

- [ ] Confirm `final_decision_maker` options match:
  - [ ] Yes
  - [ ] No
  - [ ] I am an authorized representative
- [ ] Confirm `approval_authority` options match the exact ranges in the spec.
- [ ] Add the conditional field:
  - [ ] `final_approver_name` when not final decision maker or authorized representative
- [ ] Align budget approval wording with the spec:
  - [ ] Yes
  - [ ] No
  - [ ] Pending approval
- [ ] Align “prepared to move forward” wording with the spec:
  - [ ] Yes
  - [ ] No
  - [ ] Need internal review first
- [ ] Confirm whether the current disqualification behavior still applies for this form version.

## Section 3: Service Location

- [ ] Confirm the service address field captures a full address as required.
- [ ] Confirm multi-location logic exists and behaves conditionally:
  - [ ] yes/no
  - [ ] location count shown only when yes
- [ ] Confirm `property_type` option list matches the doc exactly.
- [x] Add the missing conditional field for:
  - [x] `property_type_other` when `Other` is selected

## Section 4: Service Request Details

- [ ] Confirm the service-type checkbox group matches the spec exactly.
- [x] Add a conditional free-text field for:
  - [x] `service_type_other` when `Other` is selected
- [ ] Confirm `request_type` matches:
  - [ ] New Service
  - [ ] Replace Current Provider
  - [ ] Expand Existing Coverage
  - [ ] Temporary / Emergency Coverage
- [ ] Confirm `service_start_date` exists and is required.
- [ ] Confirm `desired_contract_term` option list matches the spec.
- [ ] Confirm `primary_reason` matches the spec as paragraph text.

## Section 5: Schedule and Staffing

- [ ] Confirm these fields exist and validate correctly:
  - [ ] hours per day requiring coverage
  - [ ] days per week requiring coverage
  - [ ] weeks per year requiring coverage
  - [ ] number of officers required per 8-hour shift
- [ ] Confirm shift checkbox options match the spec exactly.
- [ ] Confirm `assignment_type` matches:
  - [ ] Dedicated Post
  - [ ] Patrol Route
  - [ ] Hybrid
- [x] Add the missing conditional patrol type field group:
  - [x] Foot Patrol
  - [x] Vehicle Patrol
  - [x] Golf Cart Patrol
  - [x] Bike Patrol
- [x] Make the patrol type field conditional on assignment requiring patrol.

## Section 6: Duties and Site Conditions

- [ ] Confirm `duties_required[]` matches the full option set from the doc.
- [x] Add a conditional free-text field for:
  - [x] `duties_other` when `Other` is selected
- [ ] Confirm `service_package_expectation` wording matches the spec exactly.
- [ ] Confirm `hands_off_expected` wording matches the spec exactly.
- [ ] Confirm `has_written_post_orders` wording matches the spec exactly.
- [x] Add the missing file upload field for:
  - [x] post orders
  - [x] site maps
  - [x] special instructions
- [x] Add backend handling for uploaded files if this field is kept.
- [ ] Confirm `known_site_risks` matches the spec as optional paragraph text.

## Section 7: Budget and Offer Terms

- [ ] Align “approved budget” options with the spec:
  - [ ] Yes
  - [ ] No
  - [ ] Working on budget now
- [ ] Confirm `budget_format` options match:
  - [ ] Hourly Budget
  - [ ] Monthly Budget
  - [ ] Annual Budget
  - [ ] Need GASQ to help estimate
- [ ] Rename or relabel hourly budget entry so it matches the spec:
  - [ ] target baseline hourly bill rate
- [ ] Make monthly and annual budget fields conditionally required based on selected format.
- [ ] Make hourly budget conditionally required when hourly format is selected.
- [ ] Confirm `willing_post_offer` matches:
  - [ ] Yes
  - [ ] No
- [ ] Confirm `allow_scope_adjustment` matches:
  - [ ] Yes
  - [ ] No
  - [ ] Maybe after review
- [x] Add the optional field:
  - [x] side-by-side in-house vs outsourced cost comparison

## Section 8: Compliance Requirements

- [x] Add the missing compliance fields to the form and validation:
  - [x] officer licensing required
  - [x] background checks required
  - [x] drug testing required
  - [x] uniformed officers required
  - [x] insurance minimums required
  - [x] insurance limits / certifications / compliance terms
- [x] Confirm whether `insurance minimums required` should be stored as an array.
- [x] Add exact options for insurance minimums:
  - [x] General Liability
  - [x] Workers Compensation
  - [x] Auto Liability
  - [x] Umbrella / Excess Liability
  - [x] Not sure

## Section 9: Posting Terms and Submission

- [ ] Add or confirm the field:
  - [ ] send posting to multiple qualified vendors for response
- [ ] Confirm `vendor_response_deadline` exists and is required.
- [x] Add the missing optional field:
  - [x] additional notes to vendors
- [x] Expand the buyer certification text so it matches the spec statement more closely.
- [x] Expand the consent-to-contact text so it matches the spec statement more closely.
- [ ] Confirm the submit button label matches:
  - [ ] `Submit My Security Posting Request`

## Data, Validation, and Persistence

- [x] Check whether all specified fields are actually persisted after submit, not just validated.
- [x] Audit `job_postings` storage strategy for array-like fields:
  - [x] service types
  - [x] shifts needed
  - [x] patrol types
  - [x] duties required
  - [x] insurance minimums
- [x] Add migration(s) only for fields from this spec that are still missing.
- [x] Update `app/Models/JobPosting.php` fillable and casts for any new structured fields.
- [x] Update controller save logic so the submitted form maps to persisted job data consistently.
- [x] Review whether some fields belong on `users`, `job_postings`, or a separate questionnaire payload.

## UX and Behavior

- [ ] Decide whether the current form should stay as one long page or become a multi-step/progress form as implied by the spec.
- [x] Add conditional show/hide behavior for:
  - [x] final approver name
  - [x] locations count
  - [x] property type other
  - [x] service type other
  - [x] patrol type needed
  - [x] duties other
  - [x] hourly/monthly/annual budget fields
- [ ] Decide whether file uploads should be optional but strongly encouraged when post orders exist.
- [ ] Ensure mobile and desktop layouts stay usable despite the larger field count.

## Testing

- [x] Add or update feature tests for:
  - [x] qualified buyer can submit the posting form
  - [x] conditional fields validate correctly
  - [x] SMS/phone verification requirement behaves correctly
  - [x] array and checkbox fields persist correctly
  - [x] file upload handling works if implemented
  - [x] compliance fields validate and persist correctly
  - [x] certification and consent are required

## Open Product Decisions

- [ ] Confirm whether this form should keep the extra qualification fields currently present but not emphasized in this document.
- [ ] Confirm whether “No” and “Need internal review first” are allowed to submit or should block posting.
- [ ] Confirm whether buyer phone verification should reuse the existing auth phone verification or require a posting-specific SMS step.
- [ ] Confirm whether uploaded post orders should be vendor-visible immediately or only after buyer approval.
