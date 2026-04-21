# TODO: GASQ Master Buyer RFP Prequalification Questionnaire

Source: `changes/GASQ MASTER BUYER RFP PREQUALIFICATION QUESTIONNAIRE 04-18-2026.docx`

Current repo status:

- `resources/views/jobs/create.blade.php` already contains a partial buyer questionnaire.
- `app/Http/Requests/StoreJobPostingRequest.php` already validates many related fields.
- `resources/views/pages/open-bid-offer.blade.php` and `app/Http/Controllers/OpenBidOfferController.php` already expose a buyer-summary screen, but several questionnaire outputs are still placeholders.

## Implementation TODO

- [ ] Audit the questionnaire against the current buyer posting flow and mark every field as one of: already implemented, partially implemented, or missing.
- [ ] Update `resources/views/jobs/create.blade.php` so the form section names and prompts match the document more closely.
- [ ] Add the missing Buyer Identity fields if they are not persisted yet:
  - [ ] legal company name
  - [ ] property / site name
  - [ ] primary contact name
  - [ ] title / role
  - [ ] business email
  - [ ] mobile phone with SMS verification
  - [ ] business address
- [ ] Tighten the Decision Authority wording to match the questionnaire’s disqualifying intent.
- [ ] Confirm the disqualifying rules are enforced exactly for:
  - [ ] not the final decision maker
  - [ ] no approved budget
  - [ ] unwilling to move forward if vendors accept pricing
- [ ] Add the missing Project Readiness options if any are absent:
  - [ ] new requirement
  - [ ] replacing provider
  - [ ] contract expiring
  - [ ] incident-driven
  - [ ] budget planning
- [ ] Verify the service-start timeline options match the questionnaire wording.
- [ ] Align the Budget & Financial Readiness section with the document:
  - [ ] approved / flexible / restrictive / pending / no approved budget
  - [ ] budget amount or range
  - [ ] budget type: monthly / annual / contract total
  - [ ] true internal cost calculated yes/no
  - [ ] fallback actions if pricing exceeds expectations
- [ ] Align the Current Security Situation section:
  - [ ] in-house / outsourced / none
  - [ ] replacing provider yes/no
- [ ] Add Procurement & Compliance question:
  - [ ] multiple bids required yes/no
- [ ] Add Price Alignment & Buyer Behavior question:
  - [ ] willing to adjust scope to match budget yes/no
- [ ] Add Risk & Liability question with the exact three outcomes:
  - [ ] recent assessment completed
  - [ ] no assessment but wants one
  - [ ] no assessment and waiver required
- [ ] Add the “Run Calculation” and “Post Job Offer Announcement” flow explicitly in the buyer UX if that handoff is not obvious yet.
- [ ] Expand the service taxonomy in the form so it reflects the questionnaire’s scope-of-work groups:
  - [ ] property and facility security
  - [ ] core guarding services
  - [ ] patrol services
  - [ ] executive and personal protection
  - [ ] specialized site coverage
  - [ ] emergency and response services
  - [ ] event security services
  - [ ] transport and logistics security
- [ ] Review whether `category` plus `service_types[]` is enough, or whether the grouped taxonomy needs new persisted fields.
- [ ] Align the calculator input fields with the document:
  - [ ] baseline wage assumption
  - [ ] hours per day with minimum 8-hour rule if required by product
  - [ ] days per week
  - [ ] weeks of coverage
  - [ ] staff per 8-hour shift
- [ ] Decide whether the “ADD READJUST BUTTON” notes should become real UI controls or just clearer edit/recalculate actions.
- [ ] Add a pre-submission review state with:
  - [ ] adjust hours
  - [ ] adjust posts
  - [ ] adjust service level
  - [ ] adjust budget
  - [ ] recalculate estimate
- [ ] Add the authorization block to the posting flow:
  - [ ] certification text
  - [ ] name
  - [ ] title
  - [ ] signature or equivalent consent capture
  - [ ] date
- [ ] Check whether `job_postings` can store all required answers; add migration(s) only for fields that truly need persistence.
- [ ] Update `app/Models/JobPosting.php` fillable/casts for any new stored questionnaire data.
- [ ] Update `app/Http/Requests/StoreJobPostingRequest.php` rules/messages to match the final questionnaire contract.
- [ ] Update controller save logic so every kept field is persisted and normalized consistently.
- [ ] Replace placeholder values in `app/Http/Controllers/OpenBidOfferController.php` with real derived questionnaire data where available.
- [ ] Update `resources/views/pages/open-bid-offer.blade.php` to reflect the refined buyer qualification summary.
- [ ] Add or update feature tests for:
  - [ ] disqualifying responses
  - [ ] successful qualified submission
  - [ ] required questionnaire validation
  - [ ] questionnaire data persistence
  - [ ] summary display on the open bid offer page

## Open Product Decisions

- [ ] Confirm whether “authorized representative” should still be allowed, since the document frames non-decision-maker responses as disqualifying.
- [ ] Confirm whether minimum coverage must be 8 hours, or whether the current app should continue allowing smaller values elsewhere.
- [ ] Confirm whether “baseline wage assumption” belongs in the buyer posting form, calculator flow, or both.
- [ ] Confirm whether the authorization “signature” should be typed-name consent, checkbox consent, or a real signature capture.
