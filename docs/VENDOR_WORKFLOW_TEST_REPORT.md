# Vendor workflow validation — November 8, 2026

## Result

Phases 1–5 are implemented locally, and Phase 6 automated validation and production handoff documentation are included. Changes are not committed, pushed, or deployed. Production sign-off remains pending: the existing application-wide failures and live infrastructure acceptance described below have not been resolved by this feature.

## Focused workflow suite

Command:

`php -d memory_limit=512M vendor/bin/phpunit -c phpunit-vendor-workflow.xml`

Final result: **70 tests, 676 assertions, zero failures/errors**. Runtime: PHP 8.5.5, isolated SQLite database, fake mail and queues. Two existing PDO/MySQL configuration deprecations were reported by PHP 8.5. The new dedicated GitHub workflow is configured for PHP 8.2 and 8.3; those remote jobs have not been run for these unpushed changes.

Coverage includes unique vendor invitation links, attribution tampering rejection, private lead/estimate/history isolation, estimate arithmetic and snapshots, protected PDFs, recipient binding, duplicate-send prevention, delivery failures, native/external scheduling link behavior, time zones and daylight-saving validation, slot conflicts, booking idempotency, confirmations to both parties, cancellation, 24-hour/1-hour reminders, post-review release, wrong-password/role rejection, vendor attendance attestation, release timestamps, external review attestation, buyer email OTP, delivery-scoped verification, session expiry, password reveal, verified PDF download, file integrity failure, expiry, revocation, and a follow-up appointment for a revised PDF. Public booking and PDF access actions were separately tested with CSRF middleware enabled outside the test environment.

A parallel diagnostic run collided on Laravel's shared fake storage. It was discarded and the final 70-test suite above was run sequentially. New workflow tests prevent unmocked HTTP requests. Email tests use fake delivery; they are not evidence of production inbox receipt.

## Migrations and build checks

All five new phase migrations were applied to a disposable SQLite database, rolled back with `--step=5`, and reapplied successfully. No application database was migrated by this check. Blade templates compiled successfully, PHP syntax checks passed for the new services/controllers/jobs/commands, and `git diff --check` passed. This does not substitute for migration/concurrency testing on the production database engine.

The application-key/private-storage/mail/queue preflight command is covered by tests: a test log/array transport fails readiness, and a configured live transport passes the configuration checks. It sends no email and cannot prove SMTP delivery, worker uptime, or cron operation.

## Full application suite and baseline

The six V24 data providers previously prevented full-suite discovery by calling `base_path()` before application bootstrap. They now resolve fixture paths directly, allowing discovery and execution. Full-suite execution needed a 512 MB limit; the local 128 MB default exhausted memory during PDF rendering.

The recorded full run had **403 tests, 2,473 assertions, 16 errors and 57 failures**. An isolated worktree at the previous commit `bcae798`, with only the same fixture-discovery correction and an isolated application bootstrap, ran **372 tests with 72 failing/error cases**. All 72 of those failing cases also occurred in the feature branch. No new workflow tests failed in the full run.

One additional registration test in the feature branch inherited local beta closure/Twilio Verify settings. Its test setup now explicitly disables the beta gate, pins dummy SMS configuration, and blocks unmocked HTTP. It was rerun individually and passed (1 test, 4 assertions). Production registration behavior was not changed. The full application suite was not rerun after this test-isolation correction; it must not be described as green.

Remaining baseline failures span legacy unit view bootstrap, stale calculator parity expectations, coupon/billing/authentication tests, and vendor opportunity automation. They are release blockers to triage separately rather than assertions rewritten to match current output. Raw local diagnostic outputs remain under `/tmp/gasq-phase6-*` for this session.

## Production sign-off still required

Use [VENDOR_WORKFLOW_PRODUCTION.md](VENDOR_WORKFLOW_PRODUCTION.md) for configuration, the non-sending `vendor-workflow:preflight --production` check, and staging acceptance. Confirm real inbox delivery, actual PDF/password behavior, vendor concurrency on the production DB engine, both appointment notifications, reminders with real scheduler/worker execution, and the original-recipient OTP/release/revocation flow. Resolve or explicitly disposition the existing full-suite failures before calling the entire application production verified.

Offline PDF openings, forwarding, and passwords disclosed outside the application cannot be tracked or recalled. The audit history records observable application events and file checksums; it is not proof of offline reading.

## MySQL deployment correction — November 9, 2026

The first beta deployment exposed an automatically generated 67-character index name in the delivery migration; MySQL limits identifiers to 64 characters. The index now has an explicit shorter name. Because MySQL DDL is not transactional, the migration can rebuild the known empty partial table left by that failed attempt. It refuses to discard a nonempty delivery table.

Added tests compile every workflow migration through Laravel's MySQL grammar without connecting to a database, check all identifiers against the server limit, verify complete rebuilding of an empty partial table, and verify preservation of a table with records. The migration compatibility plus estimate/delivery regression run passed: 14 tests and 745 assertions. The dedicated workflow CI includes these new checks.

The corrected beta migration completed successfully and the dedicated workflow checks passed on PHP 8.2 and PHP 8.3. Server preflight confirmed all workflow tables, the release migration, encryption, private file storage, mail configuration, and queue configuration. It also exposed production debug and secure-cookie settings that were unsafe. Production configuration now enforces debug off and secure session cookies, while preserving development/test behavior. Both environment policies were checked in isolated PHP processes. Hosting scheduler/worker operation and actual inbox acceptance still require operational verification.
