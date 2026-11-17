# Private Estimate — Side-by-Side Comparison Report

The buyer-facing deliverable of the Private Estimate by Invitation flow. The
buyer answers the intake questionnaire, the platform calculates the rate, both
parties agree the scope, and this one report carries everything the buyer needs
to decide.

## What it shows

Two pages:

1. **Comparison** — the scope that was priced, the buyer's in-house cost against
   the agreed vendor cost line by line, capital recovered, payback, and the
   reconciliation against the budget the buyer had approved.
2. **Method** — the build-up behind the agreed rate, how the in-house column was
   arrived at, certification, intellectual property and the disclaimer.

Both are produced from `App\Services\PrivateEstimate\EstimateComparisonReport`,
which the on-screen buyer portal reads as well, so the page and the PDF cannot
disagree.

## Three rules

**1. The vendor column is the platform-calculated rate.**
It comes from `estimate_pricing.final_bill_rate`, the rate the GASQ engine
calculated from the vendor's workforce build-up and both sides agreed. It is
never the internal column multiplied by `budget_calculator.vendor_discount_factor`.

That matters arithmetically, not just editorially. A vendor column derived as
`internal × 0.70` makes capital recovery exactly 30% and payback exactly 8.4
months on every report ever produced, whatever the inputs. With the real rate,
both figures move per engagement, which is what makes them worth printing.

**2. The internal column says where it came from.**
If intake captured the buyer's current cost (`current_bill_rate` or
`current_annual_cost`), that is used as given and labelled "Your current cost to
protect". Otherwise the GASQ should-cost model runs off the baseline wage and the
column is labelled "GASQ modelled in-house should-cost". They are different
claims and the report never blurs them.

**3. The totals reconcile against the approved budget.**
`budget_amount` and `budget_basis` are normalised to an annual figure and
compared with the agreed annual cost. The report prints the variance either way:
within budget, or over it by a stated amount with the note that the scope or the
budget has to move. A report with no recorded budget says so rather than
presenting the price as if it had been funded.

## Where it appears

| Surface | Route |
|---|---|
| Vendor preview | `GET /vendor/private-estimates/{estimate}/comparison` |
| Vendor PDF (optional open password) | `POST /vendor/private-estimates/{estimate}/comparison.pdf` |
| Buyer portal | the invitation portal, once the estimate is released and priced |

The buyer sees the comparison only when the estimate has reached
`estimate_ready` or beyond, is not expired, and carries a rate for the current
scope version. Seeing it marks the estimate viewed on the vendor's timeline.

Negative recovery is not floored at zero: an agreed price above the buyer's
in-house cost is reported as additional cost, in both the figures and the labels.

## Related

The Bid Engagement Report (`pdf.bid-engagement-report`) follows the same rule:
its comparison column is the submitted bid, and the 70% figure survives only as a
labelled GASQ benchmark. Where a job carries no baseline wage from the buyer,
that report withholds the comparison instead of back-deriving a baseline from the
vendor's own bid.
