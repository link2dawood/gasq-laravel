---

## GASQ Spreadsheet Parity Plan
source_spreadsheet: GASQ_TCO_Model_TCO_Draft_Updated_v12.xlsx
goal: Pixel-perfect UI + exact spreadsheet-matching calculations
status: active

## Objective

Make the GASQ web app match `**GASQ_TCO_Model_TCO_Draft_Updated_v12.xlsx**`:

- **UI/UX**: pixel-perfect to the established calculator UI.
- **Logic**: calculator outputs match spreadsheet outputs exactly for the same inputs.

This plan is intentionally incremental: we will enable backend functionality **one route at a time**, with testable spreadsheet parity at each step.

## Current State (as of this plan)

- Laravel serves the bundled SPA for many calculator/onboarding routes to keep UI pixel-perfect.
- Duplicate route conflicts were cleaned up; backend endpoints were moved under `/_backend/*` to avoid collisions.
- Spreadsheet workbook is present at repo root: `GASQ_TCO_Model_TCO_Draft_Updated_v12.xlsx`.

## Principles

- **Spreadsheet is the source of truth**: any mismatch is treated as a bug until proven otherwise.
- **Golden-test driven**: every calculator gets a fixed set of input cases and expected outputs.
- **No silent drift**: parity checks must run locally before shipping changes.
- **One calculator at a time**: enable functionality only for one page/route per iteration.

## Scope: What “Parity” Means

For each calculator route:

- **Inputs**: all user-editable fields map to a known spreadsheet cell(s).
- **Outputs**: the displayed KPI/results map to specific spreadsheet output cells.
- **Rounding/formatting**: match spreadsheet rounding rules (including displayed decimals and currency formatting).
- **Edge cases**: blanks, zeros, and invalid inputs behave consistently with spreadsheet assumptions.

## Phase 0 — Lock UI Routing (already mostly done)

- Serve the SPA shell from Laravel (bundled assets under `public/assets/*`) for the calculator routes.
- Keep Laravel backend endpoints under `/_backend/*` (auth-protected) until each route is wired.

Deliverable:

- Stable list of routes that load the embedded SPA shell.

## Phase 1 — Spreadsheet Reverse-Engineering (foundation)

### 1.1 Identify the “authoritative outputs”

Start from the main report sheets:

- `Summary`
- `Contract_Summary`
- `Government_TCO`
- `Vendor_Pricing`

For each chosen sheet:

- Enumerate required output cells (KPI numbers the site must show).
- Record formulas and referenced cells.

### 1.2 Identify user-editable inputs

Start with:

- `Inputs`
- `Scope_of_Work`
- `Post_Positions`
- supporting modules (`Vehicle`, `Uniform_Equipment`, etc.)

Deliverable:

- A mapping document: **UI field → Spreadsheet cell** and **UI KPI → Spreadsheet cell**.

## Phase 2 — Golden Test Harness (must-have)

Create a repeatable parity runner:

- Load spreadsheet model.
- Apply a test case’s input values (cell assignments).
- Recalculate outputs.
- Compare expected outputs to website/backend outputs.

Notes:

- Use a “golden fixtures” folder containing 10–30 test cases per calculator.
- Include at least:
  - 1 minimal case
  - 1 typical case
  - 1 high-volume case
  - 1 edge case (zeros/blanks)

Deliverable:

- Command that prints a clear diff of mismatches (cell-by-cell).

## Phase 3 — Implement Backend Calculation Engine (one calculator at a time)

For each calculator route (order below):

1. Implement backend compute endpoint under `/_backend/*`.
2. Ensure it matches the spreadsheet using the golden tests.
3. Wire the SPA or Blade UI to call that endpoint (no visual changes unless required).
4. Repeat until stable, then move to the next route.

### Recommended order

1. `**/security-billing`**
2. `**/main-menu-calculator**` (tabs: security/manpower/economic/billrate)
3. `**/contract-analysis**`
4. `**/mobile-patrol-calculator**` / `**/mobile-patrol-comparison**`
5. `**/gasq-tco-calculator**` (largest model)

Deliverable per route:

- Backend endpoint returning a JSON payload of all KPIs shown on the page.
- Parity tests passing against the spreadsheet for the route’s golden cases.

## Phase 4 — Production Hardening

- Centralize rounding/formatting rules (currency, percent, hours) to avoid drift.
- Add input validation aligned to spreadsheet assumptions.
- Add logging for calculation version + inputs (for debugging mismatches).
- Add versioning for spreadsheet model updates:
  - store workbook version string (e.g., `v12`)
  - tie backend calc version to workbook version

## Phase 5 — Spreadsheet Updates Workflow

When the spreadsheet changes:

- Add the new workbook file version.
- Run the parity harness to detect changed outputs.
- Update mapping/fixtures only if the spreadsheet truly changed expectations.

## Definition of Done

We consider parity complete when:

- All target calculator routes use backend endpoints for computations.
- Golden tests pass for every calculator route against the spreadsheet workbook.
- UI remains pixel-perfect to the reference calculator design.

