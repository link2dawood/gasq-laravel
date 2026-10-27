# Budget Summary, account Dashboard, and Main Menu ROI

The account Dashboard and Main Menu ROI read the signed-in user's saved
`budget-calculator` scenario. `BudgetSummaryService` recomputes that scenario
through `BudgetTcoEngine`; client-supplied or previously cached totals are not
used. No budget saved means an empty state with a link to Budget Calculator.

`cost-to-protect-summary` renders the same in-house, outsourcing, and capital
recovery cards on all three pages. Dashboard and ROI add the comparison table:
the shared baseline labor rate, separate hourly bill / cost-to-protect rates,
coverage hours, staffing, and weekly/monthly/annual costs. Whole-hour totals
omit trailing decimal zeros; fractional hours retain up to two decimal places.
Money uses the selected display currency, while stored inputs remain USD.

Edit the shared baseline and coverage in Budget Calculator. Its reload restores
the actual baseline wage and nested or flat saved coverage fields. Dashboard
and ROI pick up the latest saved budget when opened or recalculated. Reading
the account Dashboard does not run a paid calculation or change saved data.
Main Menu retains its existing calculation/report billing behavior.

The new Main Menu comparison is marked `meta.roiCostBasis = budget`; its saved
snapshot is refreshed from the owner's budget on compute and report generation.
Legacy calculator scenarios retain their existing calculation behavior. Applied
vendor weighted quotes remain available in Applied Vendor Comparison and the
private vendor report; they do not replace the shared Budget Summary ROI basis.

Buyer dashboards show their in-house figures; vendor comparisons follow the
existing vendor/admin visibility. General report privacy, protected PDF delivery,
and scheduling activation are separate existing workflows.

Verification: `BudgetSummaryDashboardTest` covers known financial results,
account isolation, latest-budget refresh, forged totals, fractional coverage,
currency conversion, weighted-quote independence, and protected report output.
