# Weighted calculator currency conversion

The calculator keeps its model and saved inputs in USD. Selecting a display currency converts monetary inputs, calculated wages/bill rates, costs, and protected weighted reports. Editing a local-currency input converts it back to USD once. Switching currency never rewrites a saved roster or changes hours, headcount, wage share, ROI percentages, payback months, or dollar-for-dollar ratios. Untouched inputs retain their original precision; the ROI engine also retains monetary precision until display rather than rounding in USD before conversion.

`ExchangeRateService` fetches the ECB daily XML reference sheet and divides each EUR quote by the EUR/USD quote to obtain units per USD. USD always remains 1. All supported currencies must be present and valid before one complete dated snapshot is stored in `settings.exchange_rates_snapshot`. Invalid, incomplete, older, future-dated, or unavailable provider responses retain the previous verified sheet. The shipped fallback is the verified October 21, 2026 sheet in `resources/data/exchange-rates.json`; legacy undated `exchange_rate_*` settings and the old CAD environment override no longer control conversion.

The Laravel scheduler refreshes rates hourly using `php artisan currency:refresh`. Page rendering also attempts a throttled refresh at most hourly, with a five-minute retry delay after failure, so a working scheduler is not the sole refresh mechanism. Automated tests disable request-time network refresh. Use `CURRENCY_REFRESH_ON_REQUEST=false` when deliberately disabling that fallback.

Admin Settings displays every supported rate, its publication date, its source link, and a **Refresh ECB Rates** action. Rates cannot be forged through the generic settings form. ECB publishes business-day reference rates rather than intraday transaction quotes; the displayed as-of date identifies the actual sheet used. Quotes older than four days are labeled as the last available rate pending refresh.

The protected vendor report uses the owner's currency and includes the currency, rate, and publication date in its footer. The rounding reconciliation uses the local rounded bill rate against the exact converted role total. A non-USD signature binds the conversion rate/date as well as the roster, and must be renewed when converted figures change. Existing base-USD signature fingerprints retain their prior content structure.

Source: https://www.ecb.europa.eu/stats/policy_and_exchange_rates/euro_reference_exchange_rates/html/index.en.html

Checks cover supported cross rates, invalid/outage retention, refresh throttling, administrator permissions, repeated currency switches without modifying stored inputs, converted PDFs, local rounding reconciliation, FX-bound signatures, and full-precision 70/30 ROI amounts. Browser verification additionally exercises all 11 currency selections, editing CAD wages/bill rates, role hours, employer cost, and protected CAD PDF download.
