# Acceptance verification

Executed locally on 12 October 2026 against the implemented application. No remote CI or Azure deployment is claimed.

| Check | Result | Evidence |
| --- | --- | --- |
| Full PHP suite, SQLite | 108 tests, 608 assertions; all pass | [Execution log](verification/sqlite.txt), [JUnit XML](verification/phpunit-sqlite.xml) |
| Full PHP suite, MySQL | 108 tests, 608 assertions; all pass; randomized order, seed 12345 | [Execution log](verification/mysql.txt), [JUnit XML](verification/phpunit-mysql.xml) |
| Chromium browser journeys | 6 tests pass; zero failures, skips, or cancellations | [Execution log](verification/browser.txt) |
| Laravel Pint | Pass, no remaining formatting changes | [Execution log](verification/pint.txt) |
| PHP and JavaScript syntax | Pass | [Execution log](verification/syntax.txt) |
| Composer metadata and dependency audit | Strict validation passes; no known security advisories found | [Execution log](verification/composer.txt) |
| Production configuration, route, and Blade cache compilation | Pass; caches cleared afterward for local development | [Execution log](verification/build.txt) |
| Running local MySQL demo | Health and login endpoints return HTTP 200 | [Execution log](verification/local-smoke.txt) |

Runtime: PHP 8.4.26, Laravel 13.35.0, PHPUnit 12.5.38, MySQL 8.4.11, Node.js 24.19.0, Playwright 1.64.0. Dependencies are pinned in Composer and pnpm lockfiles. PHP tests fail on warnings, risky tests, and deprecations. Browser tests check page errors and console warnings/errors on the principal workflows.

Coverage includes identity and role boundaries, CSRF, account activation, ownership, XSS escaping, conditional input validation, transactional updates, foreign keys, multiple qualifications, alternative academic routes, incomplete information, compatible scales, criterion precedence, income boundaries and configuration changes, proportional margins, stable ordering before pagination, date-only and timed deadlines, bookmark idempotency, source-review auditing, and database-error log redaction.

The browser suite exercises registration/logout, desktop dashboard and recommendation filters, details and recorded application links, saving/removing bookmarks, adding/removing qualifications, administrator route/reference controls, and five student pages at a 390-pixel mobile width. Academic pathway cards also pass layout checks at widths of 1440, 390, and 320 pixels. The rendered dashboard, recommendation, detail, mobile, and updated academic-card screenshots were inspected visually against the supplied Stitch direction.

The warmed recommendation acceptance test uses the same 40-scholarship fictional dataset on both databases. The final measurements were 27.0 ms on SQLite and 38.3 ms on MySQL, with 12 queries each, below the PRD's 2-second target. These are local measurements, not production load-test claims. Exact measurements: [SQLite](verification/benchmark-sqlite.json), [MySQL](verification/benchmark-mysql.json).

Each implementation milestone had a passing relevant suite before the next milestone proceeded. The final full regression suites above are the delivery evidence. No failing tests were deleted, disabled, or weakened.

Online acceptance remains pending: subscription benefits, available PHP runtime, spending protection, and database costs must be verified before Azure resources are created. After deployment, repeat the shared-fixture checks and HTTPS journeys in [DEPLOYMENT.md](DEPLOYMENT.md).
