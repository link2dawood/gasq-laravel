# Vendor Weighted Rates

Open **Vendor Weighted Rates** from the calculator menu or main calculator. Vendor and admin accounts can edit and save their own roster. The latest saved roster reopens with the main calculator's existing saved state.

## Calculation and presets

- **Current Bill Rate Analysis:** enter a current bill rate per role. An empty actual wage uses `bill × 7 / 18`, labeled **Implied pay wage — model estimate**. Entering an actual wage replaces only the wage estimate; it does not change the current bill rate.
- **GASQ Wage-Based Pricing:** enter actual wages. Bill rates use the fixed GASQ 6:1 model, `wage / 0.70 × 3,744 / 2,080 = wage × 18 / 7`. Changing employees or staffing hours does not change these constants.
- Shared defaults are 40 hours per employee per week and 52 weeks. Each row can override these assumptions, allowing unequal role hours. Monthly payroll hours are annual role hours divided by 12.
- Rates are weighted by annual role hours. Calculations and saved results retain full precision; only displayed currency and percentages are rounded. Zero bill rates have unavailable wage shares.

The attached PDF preset contains eight rows: prime unarmed officers (21 at 39.29), subcontractor unarmed officers (72 at 39.29), video surveillance (14 at 40.58), security console (16 at 40.58), mobile patrol (11 at 40.58), armed officers (9 at 42.86), badging clerk (1 at 43.70), and armed supervisor (1 at 45.37). The six-person preset uses 3 Security Officers, 2 Lead/Supervisors, and 1 Management employee, with the previously specified model wages.

## Main calculator mapping and saving

`scenario.meta.vendorWeightedRates` stores the draft inputs/results separately from the explicitly applied snapshot. Editing or loading another preset saves a draft without replacing the applied selection.

**Review Results to Apply** previews the full roster, rates, sources, assumptions, payroll hours, project service hours, and buyer baseline. **Apply to Main Calculator** maps the selected rates to separate `meta.vendorWeightedPayWage` and `meta.vendorWeightedBillRate` inputs. The existing Economic Justification comparison uses `meta.vendorTrueHourlyCost` for the applied bill rate. The original vendor comparison is retained for ordinary reports.

Buyer `meta.basePayRate` / `meta.basePay`, employee cost, and scope/posts are preserved. Project estimates use explicit `meta.annualBillableServiceHours`, otherwise coverage hours derived from the main calculator's scope, otherwise its weekly service hours multiplied by annual weeks. Roster payroll hours never replace service hours.

The existing per-user `CalculatorStateStore` saves the roster and main result atomically. The roster uses the existing state table; electronic signing adds the `vendor_report_signatures` migration. Module compute/apply endpoints enforce vendor/admin access and recalculate results from inputs rather than accepting client-supplied totals. The existing main calculator retains its billing behavior; roster editing does not spend compute credits.

## Disclosure and verification

The module does not submit bids, reveal sealed prices, or publish wages. Ordinary main-calculator PDF downloads and email reports remove this module's private rates/roster and restore the original comparison. Buyer pricing continues through the existing sealed-price reveal workflow.

`VendorWeightedRateEngineTest` covers the sample acceptance totals, unequal hours, actual overrides, fixed model constants, zero bills, validation, baseline/service preservation, and existing main-calculator behavior. `VendorWeightedRateModuleTest` covers save/reopen/apply, draft versus applied state, vendor/admin access, user isolation, invalid saves, untrusted posted totals, and report filtering. Browser checks also exercise modes, presets, custom roles, hour overrides, inline errors, reopening, and mobile table scrolling.

The sample reproduces 145 employees, 5,800 weekly hours, 25,133.33 monthly hours, 301,600 annual payroll hours, $12,048,524.80 annual bill cost, $39.95 weighted bill rate, $15.54 weighted implied wage, and 38.89% implied wage share.

## Protected vendor dashboard PDF

The main Download button opens the module's password form. **Download Protected PDF** exports the account's saved draft using the supplied dashboard-and-schedule design, with a landscape legal layout for readable schedules. Longer rosters continue across pages with repeated column headings and page numbers.

The private `vendor-weighted-rates` report reuses the account's `main-menu` saved state and recalculates from its inputs. It uses the existing report download system, with vendor/admin access enforced. Downloads require POST and an 8–32 character ASCII open password supplied in the request body. Passwords are cleared after download and are excluded from validation flash data; they are not stored in estimates or URLs. The renderer fails if required encryption is unavailable. Ordinary main reports keep their existing privacy filtering, and the private report is not added to buyer email/reveal flows.

The report adds weekly and monthly bill costs. Weekly costs sum actual weekly role hours multiplied by the role rate; monthly costs divide annual costs by 12, including role-specific weeks. Its rate-build explanation uses annual wage and hour weights. The rounding note compares the exact annual bill cost against the displayed rounded bill rate times payroll hours.

The draft's $2,252.68 combined wages derive from multiplying rounded per-role wages. The implemented report retains the existing full-precision result of $2,252.66. It displays the entered headcount (145 in the sample); the draft's separate 208 staffing estimate has no supplied formula and is not substituted for the roster.

`VendorWeightedRateReportTest` covers dynamic totals, role-hour overrides, actual wages, required passwords, protected PDF output, account isolation, and buyer access restrictions. Rendered documents were also checked for readable page layout and password rejection by a PDF reader.

## Electronic signing

In the module, review the saved report, enter the signer’s full name and optional title, choose **Type my name** or **Draw my signature**, confirm intent, and select **Sign Saved Report**. The next password-protected download includes the signature, signer, server signing time in UTC, consent, and unique record reference. Signing is optional for unsigned exports.

Signatures belong to the authenticated vendor/admin’s own saved report. A SHA-256 fingerprint binds the record to normalized report rates, staffing, hours, report identity, mode, currency, and full-precision totals. The server rejects a stale report hash. Changes require a new signature; older records retain their report snapshot and are not carried onto a changed PDF. Records are stored separately from mutable calculator state, and contain the account identity, typed name or normalized PNG drawing, consent, signing time, and report snapshot. Passwords are excluded. Buyer baseline wages and project service hours retain their existing behavior.

This is an electronic signature record, with typed or drawn marks. It does not use certificate-based PDF digital signatures or request signatures from other parties. Existing private report and sealed-price permissions continue to apply.

`VendorReportSigningTest` checks consent, account isolation, stale hashes, report changes, persistent snapshots, server-controlled identity/time, drawing validation, protected signed output, and current-signature selection at download. Apply the new migration during deployment.
