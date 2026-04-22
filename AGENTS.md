<claude-mem-context>
# Memory Context

# [gasq-laravel] recent context, 2026-04-23 2:17am GMT+5

Legend: 🎯session 🔴bugfix 🟣feature 🔄refactor ✅change 🔵discovery ⚖️decision
Format: ID TIME TYPE TITLE
Fetch details: get_observations([IDs]) | Search: mem-search skill

Stats: 13 obs (6,511t read) | 171,421t work | 96% savings

### Apr 22, 2026
1 12:57a 🔵 gasq-laravel Billing Schema: Coupons, Wallets, and Transactions Migration Layout
2 1:35a 🔵 gasq-laravel Credits & Wallet System Architecture Mapped
3 " 🔵 CouponRedemptionTest: 8 Edge Cases Covering Full Validation Surface
4 1:51a ⚖️ Coupon Credit Email Policy: Send Email on Redemption
5 2:43a ⚖️ Billing and Async Hardening Plan Queued for Implementation
6 2:44a 🔄 WalletService Refactored for Concurrency Safety with DB Transactions and Row Locking
7 2:45a 🔴 Stripe Webhook Idempotency Implemented with Two-Layer Guard
8 " 🔄 SendCreditsGrantedNotification Converted to Queued Laravel Listener
9 2:46a 🟣 BillingHardeningTest Feature Test Suite Added
10 " 🔴 SendCreditsGrantedNotification Gets afterCommit = true to Prevent Premature Queue Dispatch
11 " ✅ docs/setup.md Updated with Queue Worker Requirement
12 2:48a 🔴 BillingHardeningTest Corrected: Stripe Header Key, Dynamic Plan ID, Queue::fake() Added
13 2:50a 🔴 Feature Tests Fixed: CSRF 419 Responses Required withoutMiddleware in setUp()

Access 171k tokens of past work via get_observations([IDs]) or mem-search skill.
</claude-mem-context>