# GASQ Workflow Redesign — Implementation Plan

Source: *GASQ Development Recommendation — Complete Proposed Redesign of the "Post Your
Security Service Opportunity" Workflow* (92 sections).

This plan maps that document onto the existing codebase, states what already exists, and
sequences the work. It supersedes `docs/website-checklist-plan.md` for procurement workflow.

---

## What already exists

Surveyed before planning, so we extend rather than rebuild:

| Capability | Where | Spec ref |
|---|---|---|
| Cost to Protect calculation | `BudgetTcoEngine`, `WorkforceAppraisalReportEngine` | §16–19 |
| Capital Recovery figures | `ReportController`, `GasqImpactController` | §25, §70 |
| Vendor qualification questionnaire | `VendorQuestionnaireController` (responsive/responsible) | §60 |
| Site visit + interview scheduling | `InterviewController`, `Interview`, `InterviewConfig` | §61, §62 |
| Sealed reveal mechanic | `InterviewConfig` / `InterviewController` | §63, §67 |
| Vendor accept / decline | `VendorOpportunityInvitation.status` | §57 |
| Coverage + staffing maths | `MainMenuCalculatorService`, V24 engines | §11, §12 |
| Buyer/vendor data separation | `VendorOpportunityController` redaction | §53, §54 |

## What does not exist

Confirmed absent by grep across `app/`, `resources/`, `database/`, `config/`, `routes/`:

- `shared_resource` — 0 files
- `Verified Shared Resource Rate` — 0 files
- `Price Variance` — 0 files
- `Security Service Opportunity` — 0 files (13 view files still say "Post Job")
- 1,000-hour vendor volume verification — no model, no column
- Line-item bill-rate breakdown as a **submitted, validated, reconciled** structure.
  `bids` currently stores only `amount`, `status`, `message`, `proposal`.

The Shared Resource system is the centrepiece of the spec and is entirely new build.

---

## Phasing

The spec's own priorities (§84–86) are followed, with one addition: a Phase 0 for cheap,
low-risk renames that make the change visible immediately.

### Phase 0 — Terminology and guardrails (low risk, high visibility)

| # | Item | Spec |
|---|---|---|
| 0.1 | "Post Job" → "Post Your Security Service Opportunity" across buyer-facing views | §2, §3 |
| 0.2 | Procurement vocabulary (Buyer Offer, Qualified Vendor, Sealed Price Proposal…) | §3 |
| 0.3 | "Cost to Protect" → "True Cost to Protect™" where it is the buyer benchmark | §16 |
| 0.4 | Mandatory true-cost disclaimer on reports, dashboards, PDFs | §20 |
| 0.5 | Contextual button labels replacing generic "Next" | §73 |
| 0.6 | Educational tooltips | §75 |

Routes keep their existing names/paths — renaming URLs would break live links and deployed
bookmarks. This is presentation only.

### Phase 1 — Shared Resource core (the centrepiece)

The spec's closing statement: *1,000 verified weekly billable hours earns the right to apply
for Shared Resource pricing — it does not approve the rate.* Everything here enforces that.

| # | Item | Spec |
|---|---|---|
| 1.1 | `vendor_operating_volumes` — weekly billable hours, evidence, verification status | §29, §43, §44 |
| 1.2 | Eligibility rule: ≥1,000 verified hours ⇒ *eligible for review*, never auto-approval | §30, §31, RULE 7/8 |
| 1.3 | `bill_rate_breakdowns` + `bill_rate_line_items` with resource classification | §34, §35, §78 |
| 1.4 | Reconciliation engine: Σ line items must equal submitted rate, else block | §38, §39, RULE 12 |
| 1.5 | Shared allocation validation (what is shared, method, account count, hourly) | §36, §37, RULE 11 |
| 1.6 | Vendor bill-rate certification checkbox | §41, RULE 14 |
| 1.7 | Eight-state Shared Resource status machine | §42 |
| 1.8 | Reverification / expiry | §45, RULE 16 |
| 1.9 | Hard gate: pricing model = Shared Resource ⇒ breakdown required and validated | §65, RULE 9/15 |

Acceptance scenarios A–E in §87 become the test suite for this phase.

### Phase 2 — Financial semantics

| # | Item | Spec |
|---|---|---|
| 2.1 | `PriceVariance` vs `CapitalRecoveryOpportunity` as distinct concepts | §24, §25, RULE 3/4/5 |
| 2.2 | Never label a vendor-to-vendor delta as "savings" | §71, RULE 4 |
| 2.3 | GASQ Financial Guardrails replacing floor/target/ceiling | §46, §47 |
| 2.4 | Buyer offer validation states | §49, §50, §51 |
| 2.5 | Cost-confidence + financial-validation status | §21, §22 |

### Phase 3 — Buyer opportunity workflow

| # | Item | Spec |
|---|---|---|
| 3.1 | Eight-step progress tracker with autosave | §5 |
| 3.2 | Step 1 buyer readiness + role/authority | §6, §7, §8 |
| 3.3 | Scope-of-work builder | §9, §10 |
| 3.4 | Automatic coverage calculations | §11 |
| 3.5 | Coverage vs workforce distinction | §12 |
| 3.6 | Baseline wage + source + living-wage validation | §13, §14, §15 |
| 3.7 | Opportunity validation score → ready/not ready | §52 |
| 3.8 | Review & release screen | §56 |

### Phase 4 — Sealed pricing and award

| # | Item | Spec |
|---|---|---|
| 4.1 | Extend `bids` for pricing model, sealed state, breakdown version | §64, §80 |
| 4.2 | Sealed submission gated on breakdown validation | §65 |
| 4.3 | Select preferred vendor **before** reveal | §66, RULE 21 |
| 4.4 | Reveal + selected-vendor breakdown display | §67, §68, §69 |
| 4.5 | Final financial comparison / capital recovery | §70 |

### Phase 5 — Procurement control (mostly extending what exists)

Vendor qualification scoring, site-visit tracking, interview workflow, scope versioning,
rate-review dashboard, audit history (§82), reverification.

### Phase 6 — UX and automation

Begin-before-login, autosave, living-wage integration, dashboards, downloadable assessments,
notifications (§86).

---

## Business rules to encode (§83)

These are platform invariants, not UI copy. Each needs a test.

1. True Cost to Protect is a buyer benchmark, never a vendor bill rate
2. Baseline wage established before final financial validation
3. Vendor-to-vendor differences are Price Variance
4. Price Variance is never automatically "savings"
5. Capital Recovery requires comparison against True Cost to Protect
6. Floor Rate replaced by Verified Shared Resource Rate™
7. ≥1,000 verified weekly hours ⇒ eligible for review
8. 1,000 hours does **not** approve the rate
9. Every Shared Resource rate requires a complete line-item breakdown
10. Every line item classified Direct / Dedicated / Shared / Account-specific
11. Shared allocations financially validated
12. Breakdown reconciles mathematically to the bill rate
13. Material recurring/conditional charges disclosed
14. Vendor certifies accuracy
15. GASQ approves before the rate may be submitted
16. Expired/pending/unverified vendors cannot submit Shared Resource pricing
17. Shared Resource is an operating model, not a discount
18. Buyer confidential data stays separate from vendor-visible data
19. Vendors accept/decline/request adjustment before sealed pricing
20. Pricing sealed during qualification and evaluation
21. Buyer selects preferred vendor before price reveal
22. Only authorised pricing is revealed

---

## Sequencing note

Phase 1 is the hard, high-value core and everything downstream references it, so it is built
before the buyer workflow is restructured. Phase 0 ships first only because it is cheap and
makes the direction visible without risk.
