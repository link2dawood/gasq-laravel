# V24 Main Menu Calculator — acceptance checklist

This checklist is the manual spot-check companion to `tests/Unit/MainMenuV24ParityTest.php`.

## Prereqs

- Load the workbook: `GASQ_TCO_Model_UPDATED_Final_V24 Lovable.xlsx`
- Open the Laravel Main Menu page: `/main-menu-calculator`
- Ensure you are logged in (route is auth-protected).

## Spot-check cases (minimum)

### Case A — Basic

Use the UI fields as follows:

- Security Cost
  - Location/State: California
  - Service Type: Unarmed Guard
  - Hours per Week: 40
  - Number of Guards: 1
- Manpower Hours
  - Site Coverage: 24
  - Shift Pattern: 8-hour
  - Scheduling Factor: 1.4
- Economic Justification
  - Employee True Hourly Cost: 133
  - Weekly Hours Performed: 168
  - Weeks in Year: 52
  - Months in Year: 12
- Bill Rate
  - Base Pay Rate: 18
  - Profit Margin: 15
- Bill Rate Components
  - Keep defaults (41.05, 10.96, 2.02, 0.09, 1.47, 0.50, 3.07)
- Contract Summary
  - Vehicle/Pass-Through Billings: 12000
  - Vehicle/Pass-Through Costs: 163286
  - Working Capital Req: 0

Expected results (current parity harness): see
`tests/Fixtures/v24/main_menu/case_basic.expected.json`.

## Rounding rules (lock down)

As parity is refined to actual V24 formulas, explicitly document per-field rounding (currency to 2 decimals, ratios/percentages to 1 or 2 decimals) inside:
- `docs/SPREADSHEET_CELL_MAPPING.md` (Section G + KPI tables)
- `tests/Fixtures/v24/main_menu/*.expected.json`

