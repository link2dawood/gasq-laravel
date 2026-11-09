# Phase 1: Vendor invitation and buyer lead capture

Run `php artisan migrate --force` when deploying this feature. No new environment variables or queue worker are required.

Vendors open **Buyer enquiries** from the dashboard navigation or **Buyer enquiries & invitation link** on the existing Leads screen. On first visit, the system creates a unique, random 64-character invitation token for that vendor. The link remains stable until the vendor selects **Generate replacement link**. Copy or preview the link from this screen, then share it with prospective buyers.

Buyers open `/buyer-enquiries/{token}` without creating an account. They provide their name, company (optional), email, phone, service location, service type, and scope. Preferred start date and estimated weekly service hours are optional. Required fields are validated before storage. The form includes CSRF protection and submission rate limiting.

The server resolves the vendor from the invitation token; it does not accept vendor attribution, lead status, or internal notes from the public form. Invalid and replaced links return 404. Links belonging to accounts that are no longer vendors also return 404. Public invitation pages are marked noindex and no-store and use a no-referrer policy.

Each submission enters that vendor's inbox as **New**. Vendors can search contact names, companies, or emails, filter by status, and save internal notes. Statuses are New, Contacted, Qualified, Won, and Lost. Only the owning vendor can read or update these leads. Replacing a link does not change existing leads or their attribution.

This phase captures enquiries in the vendor inbox. It does not send invitation emails, send submission notifications, register buyers, generate estimates, or create bid opportunities. Vendors share the links themselves. No messages are sent automatically.

Validation: `vendor/bin/phpunit tests/Feature/VendorBuyerLeadTest.php`.
