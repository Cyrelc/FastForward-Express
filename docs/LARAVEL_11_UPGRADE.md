# Laravel 10 → 11 upgrade runbook

Status: **done on branch `laravel-11`, not deployed.** Prod is on 10.50.5 (PHP 8.4). Target: Laravel 11.57.2.
**Prerequisite:** [STRIPE_UPGRADE.md](STRIPE_UPGRADE.md), deployed 2026-11-27 (Cashier 15, stripe-php 16).
Next steps after this is stable: 11 → 12, then 12 → 13 (separate runbooks).

## How this was verified (2026-11-25)

- Read the official 11.x upgrade guide in full, plus the Cashier 13→14→15 and spatie/laravel-permission 5→6 upgrade notes, and checked every item against the code (table below).
- Resolved the new dependency set with Composer (platform pinned to PHP 8.4), then booted a copy of the app on it: `artisan --version` = 11.57.2, 148 routes (same as on 10).
- Full test suite on the Laravel 11 copy: **196 tests, 194 pass**. The only failure (2 runs of one test) is `SecurityFixesTest::test_untrusted_host_is_rejected_in_production`: Laravel 11 rejects a forged Host with **400** instead of 404. Still rejected, still not reported to logs. The test expectation needs updating (change 5).
- Not covered by tests: real Stripe calls, Stripe webhooks, PDF generation (Browsershot). These get manual checks below.
- That check bundled the Stripe/Cashier upgrade, which has since been split out into its own runbook.

## Breaking changes checked against this app

| Guide item | Affects us? | Evidence |
|---|---|---|
| PHP ≥ 8.2, curl ≥ 7.34 | No | Prod runs PHP 8.4 |
| Dependency versions | **Yes** | See change 1 |
| Application structure | No | Laravel 11 still runs the 10-style layout (Kernel, Handler, providers). Don't migrate it. |
| Floating-point types (`float`/`double` lose total/places) | **Fresh DBs only** | 25 `float()`/`double(…, 8, 2)` calls in old migrations. Prod is already migrated, so nothing changes there. A test DB built from migrations now gets `float`/`double` instead of `float(8,2)`/`double(8,2)`. The suite passes on that schema. |
| Modifying columns (`change()` drops unstated modifiers) | No | The only `change()` is `2026_07_10_000001`: `text('value')->change()` on a column with no default/unsigned/comment, so the result is identical. Already run on prod. |
| SQLite ≥ 3.26 | No | MySQL only |
| Carbon 3 (`diffIn*` returns floats, can be negative) | No | No `diffIn*` calls in app/ or views. Date diffs use PHP's native `DateTime::diff`. Carbon goes 2.72 → 3.x. |
| Password rehashing on login | Benign | Password column is `password`, so no config is needed. We have no `config/hashing.php`, so Laravel 11's default of 12 bcrypt rounds applies (10 was the effective default before). Each user's hash is silently upgraded on their next login. |
| UserProvider / Authenticatable contracts | No | No custom implementations. `User` extends the framework base. |
| `AuthenticationException::redirectTo()` signature | No | Not called (`RedirectToSiteHost::redirectTo` is our own private method) |
| Email verification on `Registered` | No | `Registered` event not used |
| Per-second rate limiting | No | Only `Limit::perMinute()`/`perDay()` helpers (unchanged). `LoginController::$decayMinutes` goes through laravel/ui, which already converts to seconds (checked in vendor). |
| Cache key prefix colon | No | Database cache store, not Redis/Memcached/DynamoDB |
| Model `casts()` method clash | No | No model defines a `casts` method or relation |
| Spatial column types | No | None used |
| Doctrine DBAL removal | No | No DBAL APIs used. The package was only needed for `change()`. Remove it (change 1). |
| `getAllTables`/`getColumnType` etc. | No | Not used |
| MariaDB driver / `uuid()` | No | MySQL 8 with the `mysql` driver |
| Enumerable / Mailer / BatchRepository / ConnectionInterface contracts | No | No custom implementations |
| Sync queue respects `after_commit` | No | No `afterCommit` usage. Prod uses the database queue. |
| `spatie/once` | No | Not installed |
| Sanctum 4 | No action | **Not used:** the only `auth:sanctum` route is commented out, and `EnsureFrontendRequestsAreStateful` isn't in any middleware group. Sanctum 4 still reads our old `verify_csrf_token` config key (checked in vendor). `personal_access_tokens` already exists. |
| Cashier 15 (required by Laravel 11) | Done first | Handled by [STRIPE_UPGRADE.md](STRIPE_UPGRADE.md) |
| Passport / Telescope / Spark / Breeze / Jetstream / Livewire / Inertia | No | Not installed |

### Other packages that move

| Package | From → to | Affects us? |
|---|---|---|
| spatie/laravel-permission | 5.11 → 6.x (5.x won't install on Laravel 11) | No. We don't use its middleware (where the namespace changed). All `givePermissionTo`/`hasRole` calls pass names, not numeric IDs. Our permission migration doesn't touch `PermissionRegistrar` statics. No `registerPermissions()` in tests. |
| stripe/stripe-php, moneyphp/money, dompdf | — | Already moved by the Stripe upgrade |
| spatie/browsershot | 4 → 5 would happen | Avoid the extra variable: pin to `^4.0` (change 1). spatie/laravel-pdf accepts both. |
| symfony/* | 6.x → 7.x | Only the explicitly required `symfony/postmark-mailer` and `symfony/http-client` need their constraints raised |

## Re-check on the branch (2026-11-27)

- Re-checked the guide items against everything merged since the first check (chargebacks, Stripe, PDF download,
  passphrases): still no `diffIn*`, no new `change()`, no model `casts()`.
- Updated only the packages below and what they need (`composer update <names> -W`), not everything. Every version
  change is listed under "What moved".
- Full suite: **272 tests pass.** The 2 deprecations come from Laravel's own `config/database.php` on PHP 8.5
  (`PDO::MYSQL_ATTR_SSL_CA`) and don't occur on prod's PHP 8.4.
- Booted: `artisan --version` = 11.57.2, 148 routes.
- PDFs, by hand: invoice print (merged), invoice download (zip), invoice bills and manifest print all render.

## Changes made (branch `laravel-11`)

**1. composer.json**

```diff
-        "doctrine/dbal": "^3.0",
-        "laravel/sanctum": "^3.2",
+        "laravel/sanctum": "^4.0",
-        "spatie/laravel-permission": "^5.10",
+        "spatie/laravel-permission": "^6.0",
-        "symfony/postmark-mailer": "^6.3",
-        "symfony/http-client": "^6.3",
-        "laravel/framework": "^10.0",
-        "nunomaduro/collision": "^7.0",
+        "symfony/postmark-mailer": "^7.0",
+        "symfony/http-client": "^7.0",
+        "laravel/framework": "^11.0",
+        "nunomaduro/collision": "^8.1",
+        "spatie/browsershot": "^4.0",
 require-dev:
-        "symfony/css-selector": "5.0.*",
 config:
+        "platform": {"php": "8.4.0"}
```

- `symfony/css-selector` was pinned to 5.0 since 2016, for the long-removed DomCrawler. Only Laravel's mail CSS
  inliner (`tijsverkoyen/css-to-inline-styles`) needs it now, so its version follows that package.
- `platform.php` makes Composer resolve for prod's PHP 8.4 even when run on dev's 8.5. Raise it when prod's PHP moves.
- `composer.lock` is already committed (since the Stripe upgrade), so prod installs exactly what was tested.

### What moved

Majors: laravel/framework 10.50.5 → 11.57.2, laravel/sanctum 3 → 4, spatie/laravel-permission 5.11 → 6.25,
nesbot/carbon 2 → 3, laravel/serializable-closure 1 → 2, nunomaduro/collision 7 → 8, nunomaduro/termwind 1 → 2,
carbonphp/carbon-doctrine-types 2 → 3, and the Symfony components 6 → 7 (console, error-handler, finder,
http-client, http-foundation, http-kernel, mailer, mime, postmark-mailer, process, routing, uid, var-dumper) or
7 → 8 (event-dispatcher, string, translation, css-selector). Laravel 11.57 accepts Symfony 8 for those; Symfony 8
needs PHP 8.4.

Minor: brick/math, filp/whoops (dev), laravel/prompts, league/commonmark, spatie/browsershot 4.0.1 → 4.4.0 (still 4),
spatie/temporary-directory, tijsverkoyen/css-to-inline-styles.

Added: league/uri, league/uri-interfaces, symfony/clock, symfony/polyfill-php85, symfony/polyfill-time.
Removed: doctrine/dbal and its doctrine/cache, doctrine/deprecations, doctrine/event-manager, psr/cache.

Unchanged: Stripe, Cashier, the PDF merger, Pusher, query builder, activity log and everything else not listed.

**2. Host-rejection test:** `SecurityFixesTest.php:70` → `->assertStatus(400)`. Update the "404" wording in the `TrustHosts` docblock and in the `handle()` comment.

**Don't** run the `vendor:publish --tag=cashier-migrations` / `sanctum-migrations` steps from the guide. Cashier 13 already created its tables on prod, and Sanctum's migration is already in `database/migrations`. Publishing would add duplicate create-table migrations that fail on `migrate`.

## Verify on dev

```bash
composer install --no-scripts && php artisan package:discover && php artisan config:clear
vendor/bin/phpunit                               # 272 pass; never with a cached config (it would hit the dev DB)
php artisan --version                            # 11.57.2
```

Then by hand:
- log in and out; trigger the lockout
- invoice PDF and manifest PDF (Browsershot). Done on dev: print, download, bills, manifest
- one Stripe test payment end to end (`stripe listen`), as a smoke test of the payment stack on the new framework
- check the queue workers process jobs; check the activity log for errors

## Deploy to prod

```bash
# Pre-flight
php -v                                    # 8.4
php -m | grep -E '^curl$'
cd /var/www/FastForward-Express
git rev-parse HEAD > ~/pre-l11-commit.txt
tar czf ~/pre-l11-vendor.tgz vendor                 # fast rollback: the old vendor, no composer run needed
mysqldump FastForwardExpress | gzip > ~/pre-l11-db.sql.gz

php artisan down
# Drain the queue so no job is serialized under one framework version and run under another.
# Workers pause in maintenance mode, so stop them and drain by hand with --force.
sudo supervisorctl stop 'laravel-queue:*'
php artisan queue:work --force --stop-when-empty
php artisan tinker --execute="echo DB::table('jobs')->count();"   # must be 0

git pull
composer check-platform-reqs --lock                # every line must say success
composer install --no-dev --optimize-autoloader   # post-install runs `artisan optimize`
php artisan migrate:status                        # expect nothing pending
php artisan optimize:clear && php artisan optimize

sudo supervisorctl start 'laravel-queue:*'
php artisan up
php artisan --version                             # Laravel Framework 11.x
```

Afterwards, watch the activity log and the Discord channel for a day.

**Rollback:** `php artisan down`, `git checkout $(cat ~/pre-l11-commit.txt)`, `rm -rf vendor && tar xzf ~/pre-l11-vendor.tgz`, `php artisan optimize:clear && php artisan optimize`, restart the workers, `php artisan up`. No schema changes are involved, so the DB doesn't need restoring.
