---
title: Spreadsheet ↔ UI Cell Mapping
workbook: GASQ_TCO_Model_TCO_Draft_Updated_v12.xlsx
purpose: Map UI inputs/outputs to authoritative spreadsheet cells
status: draft
---

## How to use this document

- **UI field** is the website input name we will standardize on (matches the TypeScript calc engine in `gasqTcoCalculations.ts`).
- **Spreadsheet cell** is the authoritative value/formula location in Excel.
- This mapping is the basis for:
  - Golden test fixtures
  - Backend parity endpoints
  - UI-to-backend payload contracts

---

## A) Assumptions / Controls (`Inputs` sheet)

Source: `Inputs!A1:E53`

### A.1 Core Controls → `AssumptionsInputs`

| UI field (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `assumptions.directLaborWage` | `Inputs!B4` | Direct Labor Wage ($/paid hour) |
| `assumptions.annualPaidHoursPerFTE` | `Inputs!B5` | Annual Paid Hours per FTE |
| `assumptions.annualProductiveCoverageHoursPerFTE` | `Inputs!B6` | Annual Productive Coverage Hours per FTE |
| `assumptions.localityPayPct` | `Inputs!B7` | Locality Pay % |
| `assumptions.shiftDifferentialPct` | `Inputs!B8` | Shift Differential % |
| `assumptions.otHolidayPremiumPct` | `Inputs!B9` | OT/Holiday Premium % |

Note: the calc engine also has `assumptions.consolidatedHWBenefit` (not present in `Inputs` range shown; we will confirm where this lives in the workbook next).

### A.2 Fringe / Employer Burden → `AssumptionsInputs`

| UI field (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `assumptions.ficaMedicarePct` | `Inputs!B12` | FICA / Medicare % |
| `assumptions.futaPct` | `Inputs!B13` | FUTA % |
| `assumptions.sutaPct` | `Inputs!B14` | SUTA % |
| `assumptions.workersCompPct` | `Inputs!B15` | Workers Compensation % |
| `assumptions.healthWelfarePerHour` | `Inputs!B16` | Health & Welfare ($/paid hour) |
| `assumptions.vacationPct` | `Inputs!B17` | Vacation % |
| `assumptions.paidHolidaysPct` | `Inputs!B18` | Paid Holidays % |
| `assumptions.sickLeavePct` | `Inputs!B19` | Sick Leave % |

### A.3 Operations / Support → `AssumptionsInputs`

| UI field (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `assumptions.recruitingHiringPct` | `Inputs!B22` | Recruiting / Hiring % |
| `assumptions.trainingCertPct` | `Inputs!B23` | Training / Certification % |
| `assumptions.uniformsEquipmentPct` | `Inputs!B24` | Uniforms / Equipment % |
| `assumptions.fieldSupervisionPct` | `Inputs!B25` | Field Supervision % |
| `assumptions.contractManagementPct` | `Inputs!B26` | Contract Management % |
| `assumptions.qualityAssurancePct` | `Inputs!B27` | Quality Assurance % |
| `assumptions.vehiclesPatrolPct` | `Inputs!B28` | Vehicles / Patrol % |
| `assumptions.technologySystemsPct` | `Inputs!B29` | Technology / Systems % |
| `assumptions.generalLiabilityInsurancePct` | `Inputs!B30` | General Liability Insurance % |
| `assumptions.umbrellaOtherInsurancePct` | `Inputs!B31` | Umbrella / Other Insurance % |

### A.4 Corporate & Pricing Controls → `AssumptionsInputs`

| UI field (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `assumptions.adminHrPayrollPct` | `Inputs!B34` | Administrative / HR / Payroll % |
| `assumptions.accountingLegalPct` | `Inputs!B35` | Accounting / Legal % |
| `assumptions.corporateOverheadPct` | `Inputs!B36` | Corporate Overhead % |
| `assumptions.gAndAPct` | `Inputs!B37` | G&A % |
| `assumptions.profitFeePct` | `Inputs!B38` | Profit / Fee % |

### A.5 Vendor pricing controls (used by Summary/KPIs)

| UI field (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `assumptions.vendorTcoFactorVsGovernmentTco` (naming TBD) | `Inputs!B39` | Vendor TCO Factor vs Government TCO |
| `assumptions.vendorFloorFactorVsVendorTco` (naming TBD) | `Inputs!B40` | Vendor Floor Factor vs Vendor TCO |
| `assumptions.minimumWeeklyHoursForFloorEligibility` (naming TBD) | `Inputs!B41` | Minimum Weekly Hours for Floor Eligibility |

---

## B) Scope of Work (`Scope_of_Work` + integrated block on `Post_Positions`)

Important: `Scope_of_Work` is a **linked mirror**; the editable cells are on `Post_Positions` column `X` block.

Editable inputs:
- `Post_Positions!X27` Hours of Coverage per Day
- `Post_Positions!X28` Days of Coverage per Week
- `Post_Positions!X29` Weeks of Coverage
- `Post_Positions!X30` Staff per 8-Hour Shift

### B.1 Scope inputs → `ScopeInputs`

| UI field (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `scope.hoursOfCoveragePerDay` | `Post_Positions!X27` | Hours of Coverage per Day |
| `scope.daysOfCoveragePerWeek` | `Post_Positions!X28` | Days of Coverage per Week |
| `scope.weeksOfCoverage` | `Post_Positions!X29` | Weeks of Coverage |
| `scope.staffPerShift` | `Post_Positions!X30` | Staff per 8-Hour Shift |

Derived / verification values (not directly edited):
- `Post_Positions!X33` Annual Coverage Hours
- `Post_Positions!X37` Annual Labor Hours
- `Post_Positions!X38` FTEs Required @ Paid Hours Basis

---

## C) Post positions (per-post rows) (`Post_Positions`)

Primary user-editable row block:
- Rows `7:21` represent up to 15 posts.

### C.1 Per-post inputs → `PostPosition[]`

For each post row \(r \in [7..21]\):

| UI field (code) | Spreadsheet cell | Notes |
|---|---:|---|
| `posts[i].postName` | `Post_Positions!B{r}` | Example: “Post 1” |
| `posts[i].positionTitle` | `Post_Positions!C{r}` | Example: “Armed Security Officer” |
| `posts[i].location` | `Post_Positions!D{r}` | Optional |
| `posts[i].weeklyHours` | `Post_Positions!E{r}` | Weekly Hours |
| `posts[i].payWage` | `Post_Positions!F{r}` | Pay Wage (AUTO uses `Inputs!B4`) |
| `posts[i].rateMode` | `Post_Positions!G{r}` | AUTO / MANUAL (via `W{r}` wage mode too) |
| `posts[i].manualBillRate` | `Post_Positions!H{r}` | Manual Bill Rate |
| `posts[i].manualPayWage` (naming TBD) | `Post_Positions!V{r}` | Manual Pay Wage ($/hr) |
| `posts[i].wageMode` (naming TBD) | `Post_Positions!W{r}` | Wage Mode |

Notes:
- `F{r}` contains an Excel formula selecting between `V{r}` and `Inputs!B4` depending on wage mode.
- The current UI model may not expose *both* `V/W` and `F`; we will normalize UI fields during implementation.

---

## D) Vehicle module (`Vehicle`)

Editable vehicle inputs are in `Vehicle!B5:B15`.

### D.1 Vehicle inputs → `VehicleInputs`

| UI field (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `vehicle.vehiclesRequired` | `Vehicle!B5` | Vehicles Required |
| `vehicle.avgMilesPerVehiclePerDay` | `Vehicle!B6` | Average Miles per Vehicle per Day |
| `vehicle.fuelEconomy` | `Vehicle!B9` | Fuel Economy |
| `vehicle.fuelCostPerGallon` | `Vehicle!B10` | Fuel Cost per Gallon |
| `vehicle.maintenanceCostPerMile` | `Vehicle!B11` | Maintenance Cost per Mile |
| `vehicle.monthlyLeasePerVehicle` | `Vehicle!B12` | Monthly Lease / Payment per Vehicle |
| `vehicle.monthlyInsurancePerVehicle` | `Vehicle!B13` | Monthly Insurance per Vehicle |
| `vehicle.monthlyGpsPerVehicle` | `Vehicle!B14` | Monthly GPS / Telematics per Vehicle |
| `vehicle.annualRegistrationPerVehicle` | `Vehicle!B15` | Annual Registration / Tags per Vehicle |

Key outputs (for KPI verification):
- `Vehicle!C29` Total Annual Vehicle Cost
- `Vehicle!C30` Vehicle Cost per Annual Labor Hour

---

## E) Uniform & Equipment module (`Uniform_Equipment`)

Editable uniform/equipment inputs are in `Uniform_Equipment!B7:B18` (plus links `B5`, `B6` derived).

### E.1 Uniform inputs → `UniformInputs`

| UI field (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `uniform.uniformSetsPerEmployee` | `Uniform_Equipment!B7` | Uniform Sets per Employee |
| `uniform.costPerUniformSet` | `Uniform_Equipment!B8` | Cost per Uniform Set |
| `uniform.uniformUsefulLife` | `Uniform_Equipment!B9` | Uniform Useful Life (months) |
| `uniform.dutyGearKitPerEmployee` | `Uniform_Equipment!B10` | Duty Gear Kit per Employee |
| `uniform.dutyGearUsefulLife` | `Uniform_Equipment!B11` | Duty Gear Useful Life (months) |
| `uniform.radioDevicePerEmployee` | `Uniform_Equipment!B12` | Radio / Device per Employee |
| `uniform.radioDeviceUsefulLife` | `Uniform_Equipment!B13` | Radio / Device Useful Life (months) |
| `uniform.badgeIdMiscPerEmployee` | `Uniform_Equipment!B14` | Badge / ID / Misc. Issue per Employee |
| `uniform.badgeIdUsefulLife` | `Uniform_Equipment!B15` | Badge / ID Useful Life (months) |
| `uniform.annualConsumablesPerEmployee` | `Uniform_Equipment!B16` | Annual Consumables / Small Equipment |
| `uniform.spareFloatInventoryFactor` | `Uniform_Equipment!B17` | Spare / Float Inventory Factor |
| `uniform.programManagementFactor` | `Uniform_Equipment!B18` | Program Management / Logistics Factor |

Key outputs (for KPI verification):
- `Uniform_Equipment!C35` Total Annual Uniform & Equipment Cost
- `Uniform_Equipment!C36` Uniform & Equipment Cost per Labor Hour

---

## Next mapping sections (to complete next)

The following workbook areas still need explicit UI ↔ cell mapping:
- Vendor bids / pricing inputs (`Vendor_Pricing`)
- IGCE inputs (`Government_IGCE_Model` / `Government_IGCE_Model` family)
- Training module inputs (`Training_Module`)

---

## F) KPI outputs (for UI display + golden tests)

### F.1 Executive summary KPIs (`Summary`)

Source: `Summary!A1:E25`

| UI KPI (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `kpi.annualCoverageHours` | `Summary!B4` | Annual Coverage Hours |
| `kpi.ftesRequired` | `Summary!B5` | FTEs Required |
| `kpi.governmentTcoPerHour` | `Summary!B6` | Government /Customer TCO per Hour |
| `kpi.annualTotalCostOfOwnership` | `Summary!B7` | Annual Total Cost of Ownership |
| `kpi.annualUniformEquipmentCost` | `Summary!B8` | Annual Uniform & Equipment Cost |
| `kpi.vendorTcoRate` | `Summary!B9` | Vendor TCO Rate |
| `kpi.vendorFloorRate` | `Summary!B10` | Vendor Floor Rate |
| `kpi.vendorTargetRate` | `Summary!B11` | Vendor Target Rate |
| `kpi.vendorCeilingRate` | `Summary!B12` | Vendor Ceiling Rate |
| `kpi.capitalRecoveryPerHour` | `Summary!B13` | Capital Recovery per Hour |
| `kpi.annualCapitalRecovery` | `Summary!B14` | Annual Capital Recovery |
| `kpi.annualVehicleCost` | `Summary!B15` | Annual Vehicle Cost |
| `kpi.annualCapitalRecoverySavedVendorTco` | `Summary!B16` | Annual Capital Recovery Saved (Vendor TCO) |

Cross-check KPIs used to validate the model:
- `Summary!B24` Burden Build
- `Summary!B25` Burden Check

### F.2 Government TCO KPIs (`Government_TCO`)

Source: `Government_TCO!A1:E15`

| UI KPI (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `kpi.blendedDirectLaborWage` | `Government_TCO!B4` | Blended Direct Labor Wage / Pay Rate |
| `kpi.fullBurdenedHourlyCost` | `Government_TCO!B5` | Full Burdened Hourly Cost |
| `kpi.governmentTcoPerProductiveCoverageHour` | `Government_TCO!B8` | Government TCO per Productive Coverage Hour |
| `kpi.vendorRateFromGovernmentTco` | `Government_TCO!B9` | Vendor Rate |
| `kpi.capitalRecoverySavedPerHour` | `Government_TCO!B10` | Capital Recovery Saved per Hour |
| `kpi.annualCoverageHoursRequired` | `Government_TCO!B11` | Annual Coverage Hours Required |
| `kpi.annualGovernmentCostForRequiredCoverage` | `Government_TCO!B12` | Annual Government Cost for Required Coverage |
| `kpi.annualVendorCostForRequiredCoverage` | `Government_TCO!B13` | Annual Vendor Cost for Required Coverage |
| `kpi.annualCapitalRecoverySaved` | `Government_TCO!B15` | Annual Capital Recovery Saved |

### F.3 Vendor pricing guardrails + vendor evaluation (`Vendor_Pricing`)

Source: `Vendor_Pricing!A1:F19`

| UI KPI (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `kpi.governmentTcoPerProductiveCoverageHour` | `Vendor_Pricing!B4` | Government TCO per Productive Coverage Hour |
| `kpi.vendorTcoRate` | `Vendor_Pricing!B5` | Vendor TCO Rate |
| `kpi.vendorAbsorbedFloorRate` | `Vendor_Pricing!B6` | Vendor Absorbed Floor Rate |
| `kpi.vendorTargetRate` | `Vendor_Pricing!B7` | Vendor Target Rate |
| `kpi.vendorCeilingRate` | `Vendor_Pricing!B8` | Vendor Ceiling Rate |
| `kpi.capitalRecoverySavedPerHour` | `Vendor_Pricing!B9` | Capital Recovery Saved per Hour |
| `kpi.annualCapitalRecoverySaved` | `Vendor_Pricing!B10` | Annual Capital Recovery Saved |

Vendor evaluation inputs (editable):
- Vendor 1: `Vendor_Pricing!B15` (bid rate), `Vendor_Pricing!C15` (weekly hours billed)
- Vendor 2: `Vendor_Pricing!B16`, `Vendor_Pricing!C16`
- Vendor 3: `Vendor_Pricing!B17`, `Vendor_Pricing!C17`
- Vendor 4: `Vendor_Pricing!B18`, `Vendor_Pricing!C18`
- Vendor 5: `Vendor_Pricing!B19`, `Vendor_Pricing!C19`

Computed vendor evaluation outputs (display candidates):
- `Vendor_Pricing!D15:D19` Floor eligible?
- `Vendor_Pricing!E15:E19` Range position
- `Vendor_Pricing!F15:F19` Interpretation

### F.4 Buyer vs vendor comparison KPIs (`Buyer_Vendor_Comparison`)

Source: `Buyer_Vendor_Comparison!A1:E26`

| UI KPI (code) | Spreadsheet cell | Spreadsheet label |
|---|---:|---|
| `kpi.vendorTcoAnnualSavings` | `Buyer_Vendor_Comparison!C10` | Annual Capital Recovery Saved (Vendor TCO) |
| `kpi.vendorTargetAnnualSavings` | `Buyer_Vendor_Comparison!D10` | Annual Capital Recovery Saved (Vendor Target) |
| `kpi.vendorFloorAnnualSavings` | `Buyer_Vendor_Comparison!E10` | Annual Capital Recovery Saved (Vendor Floor) |
| `kpi.vendorTcoSavingsPct` | `Buyer_Vendor_Comparison!C11` | Savings % vs Buyer (Vendor TCO) |
| `kpi.vendorTargetSavingsPct` | `Buyer_Vendor_Comparison!D11` | Savings % vs Buyer (Vendor Target) |
| `kpi.vendorFloorSavingsPct` | `Buyer_Vendor_Comparison!E11` | Savings % vs Buyer (Vendor Floor) |

### F.5 Contract summary report KPIs (`Contract_Summary`)

Source: `Contract_Summary!B1:J25`

This sheet is report-oriented. Key cells currently used by the sheet:
- `Contract_Summary!J2` Weekly hours (driven from post positions)
- `Contract_Summary!J3` Blended straight time bill rate
- `Contract_Summary!J4` Blended straight time pay rate
- `Contract_Summary!J7` Contract economics (effective pay rate; derived)
- `Contract_Summary!J9` Total annual revenue (from hourly billings)
- `Contract_Summary!J11` Vehicle / pass-through (annual)
- `Contract_Summary!J13` Total annual contract revenue
- `Contract_Summary!J22` Effective bill rate
- `Contract_Summary!J23` Buyer government TCO rate
- `Contract_Summary!J24` Vendor TCO rate
- `Contract_Summary!J25` Vendor floor rate

Next step for this section:
- Enumerate exactly which `Contract_Summary` fields the website must display, then lock a definitive KPI list.

