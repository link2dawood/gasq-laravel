# Customer appointment email setup

All password-protected calculator/report emails, including Main Menu, Cost to Protect, Workforce-to-Post Allocation, Weighted Rate, and lead estimates, include a scheduling button, simple date/time selection instructions, and a copyable link for email clients that do not display buttons.

`CustomerBookingLink` chooses the selected provider's valid HTTPS booking URL. If that URL is missing or invalid, it uses another configured Bookafy/Calendly booking URL. Credentials in URLs, unrelated domains, non-HTTPS URLs, and fragments are rejected. Existing query parameters are preserved.

The configuration reviewed on November 9, 2026 selected Calendly with no Calendly booking URL, while a Bookafy booking URL was configured. Before this correction, report messages fell back to asking the customer to reply, and lead-estimate email preparation could be blocked. The configured Bookafy URL is now available to those email flows without changing the global provider or registering callbacks.

Vendor-native calendars still take priority for lead-estimate deliveries. Their customer emails retain the unique private scheduling link. External delivery links retain their unique reference, using the reference field for the provider actually selected by the resolver.

This correction affects newly generated emails. It does not update already-sent messages, change PDF-password release rules, configure provider availability, or enable Bookafy webhook reconciliation. External booking confirmations and availability remain controlled in the scheduling provider's account.

To review the message, generate a report with a PDF password and use its existing email action, or open a Ready lead estimate and use **Send estimate to buyer**. The configured Bookafy page was rechecked on November 9, 2026 with a browser user agent and loaded standalone, returning HTTP 200 and the title "Book Your Appointment with GASQ". Its earlier HTTP 403 came from automated-request blocking, not a broken link. Confirm the appointment type, available slots, and the booking confirmation in a normal browser before sending to a real customer.

Validation includes resolver priority/fallback, URL restrictions, customer email rendering, and correct Bookafy reference tagging when Calendly is selected but lacks a URL. Tests use fake email and do not send customer messages.

## Vendor invitation and optional allocation password

Vendors find their unique link at **Dashboard → Buyer enquiries → Your private invitation link**. They can copy the link or choose **Draft invitation email**, which opens a prefilled message in their own email application. The vendor adds the buyer's address, reviews the draft, and sends it directly. The application does not send this draft automatically.

The Workforce-to-Post Allocation & Line-Item Breakdown report now exposes the existing optional password control. Leave it blank to create a PDF that opens without a password. Enter a password to require it for opening and include the scheduling message when emailing that protected copy. This choice applies to this calculator report; lead-estimate and private weighted-report workflows keep their existing password requirements.

Local PDF checks confirmed blank-password opening, refusal to open the protected copy without its password, and successful opening with its chosen password.

The Cost to Protect Buyer Edition keeps its approved cover message, which quotes no vendor figures and offers no password control in the interface. Should a password ever reach that report, its cover now names the booked review as the route to the password instead of sending an unopenable attachment with no instructions. This keeps the rule that every password-protected PDF requires a scheduled appointment true on every email path, rather than only on the paths the interface exposes today.

Every other producer of a protected PDF was reviewed against the same rule. The vendor's own estimate download requires a password and reaches no buyer. The final buyer appraisal is always encrypted and its delivery is already gated on a live scheduled appointment. The embedded calculator path emails a PDF rendered in the browser and accepts no password.

## Approved Cost to Protect cover message

The protected Cost to Protect cover now follows the message supplied by GASQ on November 9, 2026, written against report GASQ-20261107-105838-V9. Its subject is "Your GASQ Security Cost Summary: Review Your Capital Recovery Opportunity". The body opens with the recovery headline, prints the Detailed Cost Appraisal Comparison as a fourteen-row table of buyer in-house against vendor outsourcing figures, then the What the Estimate Includes paragraphs, then the Next Step scheduling section and the standard brand block.

Every figure in that table comes from `CostToProtectEstimate`, the same service the attached PDF uses, so the cover can never quote a number the report contradicts. The controller passes the whole estimate array to the cover for this reason.

Money is concealed by replacing the leading digits and keeping the final group with its cents, so $782,068.11 prints as $XXX,068.11 and $24.37 as $XX.37. The headline sentence quotes the recovery without cents, as the approved message does. Coverage quantities, staffing counts, and the recovery percentage are not concealed. `CalculatorReportEmailTest` pins all fourteen masked figures against that report's own totals and asserts no unmasked amount reaches the email.

Two deliberate departures from the supplied document, both open to correction: the cover keeps a report number and date-prepared line under the greeting, for traceability and consistency with the other covers, and it retains the existing on-site survey notes and attachment blocks so the vendor survey feature keeps working.

Four protected covers still carry wording written in-house rather than supplied: the Workforce-to-Post allocation, the weighted rate report, the generic report, and the lead estimate. GASQ said a message per protected document would follow.
