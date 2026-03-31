#!/usr/bin/env node
/**
 * Placeholder for regenerating `.expected.json` for React-parity standalone engines.
 *
 * Source of truth for golden files is PHPUnit (`tests/Unit/StandaloneV24ParityTest.php`)
 * running the PHP engines. After changing `MobilePatrolAnalysisEngine` or
 * `GlobalSecurityPricingEngine`, run:
 *
 *   php vendor/bin/phpunit tests/Unit/StandaloneV24ParityTest.php --filter mobile-patrol-analysis
 *   php vendor/bin/phpunit tests/Unit/StandaloneV24ParityTest.php --filter global-security-pricing
 *
 * If a field drifts, update the corresponding `tests/Fixtures/v24/standalone/*.expected.json`
 * from the test failure diff or by temporarily dumping `$out['kpis']` in the test.
 */
console.log(JSON.stringify({ note: 'See header comment in this file.' }, null, 2));
