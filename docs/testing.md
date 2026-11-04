# Practical coverage audit

This is a behaviour audit, not a claimed line-coverage percentage. CI previously ran mostly isolated SQLite tests and JavaScript DOM checks. The newer IMDb and streaming features had much stronger coverage than the older account, guest and friendship flows.

| Area | Gap found | Tests added |
| --- | --- | --- |
| Save my score | No complete registration test transferring guest history; no partial-write failure test | Registration copies only the current session's movie and TV watched/favourite/watchlist records, preserves unwatched TV favourites, hashes passwords, rejects invalid registration, and rolls back all account/history writes if transfer fails |
| Guest and member history | Little direct coverage of guest writes or favourite/watchlist ownership | Repeated writes stay idempotent; edits preserve other users, other sessions and TV rows with the same numeric ID; invalid IDs/booleans and unauthenticated writes change nothing |
| Authentication | No real username/password login and logout test | Wrong password rejected, valid username login succeeds, logout removes authentication |
| Friendships | No end-to-end request/accept/remove tests | Request emails target the recipient, replacement invalidates the old token, acceptance works once, unknown tokens are harmless, existing friendships work in both directions, invitations do not create friendships, either participant can remove their own friendship |
| Movie listing SQL | Several tests checked SQL text or the small filter helper, without executing the actual MySQL joins | Real MySQL runs the movie query and home/watchlist controllers, combined streaming OR/language/genre/decade/popularity/watched/favourite filters, friend history exclusion, guest-session isolation and Show more with no repeated movies |
| TV network behaviour | Happy paths were stronger than failure/race cases | A stale search cannot overwrite newer results; double Show more clicks send one request; failed loads preserve cards and retry the same page; failed saves keep watched state unchanged; random-picker failure can be retried with the same filters |

Two failures discovered by these tests were fixed: registration previously left an account and copied watchlist rows behind when history transfer failed; deleting a friendship failed when the current user was its recipient. Registration now uses one transaction, and deletion checks both friendship orientations and returns 404 for an unrelated pair.

The MySQL fixtures run the shared streaming/tracking schema changes on MySQL as well as testing the actual production SQL. They never use the application database. HTTP availability responses and email delivery are faked; no live emails, TMDB calls or production writes occur in the suite.

## Run locally

```bash
composer test
npm run build
npm test
```

The PHP command includes the MySQL integration suite, but skips its five cases unless explicitly enabled. To run those tests locally, create a **disposable** database named `moviechecklist_test_local` and run:

```bash
RUN_MYSQL_TESTS=1 MYSQL_TEST_DATABASE=moviechecklist_test_local composer test
```

The integration fixture drops/recreates tables in that database before each test. Database names outside the `moviechecklist_test_*` prefix are rejected. Override `MYSQL_TEST_HOST`, `MYSQL_TEST_PORT`, `MYSQL_TEST_USERNAME` and `MYSQL_TEST_PASSWORD` if necessary; otherwise it uses the local MySQL connection credentials. Keep credentials out of source control.

GitHub Actions provides an isolated MySQL 8 service and enables these tests automatically. Both the SQLite and MySQL cases must pass before Forge is triggered. `npm test` discovers every `.cjs` test under `tests/js`, including the new network tests.

## Remaining gaps

- Full browser journeys and visual layout: jsdom verifies real compiled Vue behaviour but does not exercise a browser's layout engine, touch interactions or actual image delivery. A small Playwright journey covering guest → Save my score → login → movie/TV toggle would be the next useful addition.
- Concurrent friendship acceptance and tag editing/authorization still lack dedicated tests. Sequential token acceptance is covered; that does not prove concurrent requests are safe.
- MySQL fixtures exercise real joins and the current catalogue migrations, not a full production-data upgrade or a fresh installation through every historical migration. Those legacy migrations do not fully describe the current application schema.
- Mail transport and real external-provider contracts are outside this deterministic suite. Mail recipients and application outcomes are covered with fakes.
