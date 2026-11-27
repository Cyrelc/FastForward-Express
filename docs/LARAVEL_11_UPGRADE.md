# Laravel 10 → 11 upgrade runbook

Status: **planned, not started.** Prod is on 10.50.2 (PHP 8.4). Target: Laravel 11.57.x.
**Prerequisite:** [STRIPE_UPGRADE.md](STRIPE_UPGRADE.md) deployed first (Cashier 15, stripe-php 16).
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

## Changes to make (on a branch: `laravel-11`)

Branch from master after `stripe-upgrade` has merged.

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
```

Also **remove `composer.lock` from `.gitignore` and commit it.** Right now prod resolves its own versions (prod is on 10.50.2 while dev's lock is 10.48.4), so what we test is not what we deploy. The repo is public, but the lock only lists package versions, so nothing sensitive leaks.

**2. Host-rejection test:** `SecurityFixesTest.php:70` → `->assertStatus(400)`. Update the "404" wording in the `TrustHosts` docblock and in the `handle()` comment.

**Don't** run the `vendor:publish --tag=cashier-migrations` / `sanctum-migrations` steps from the guide. Cashier 13 already created its tables on prod, and Sanctum's migration is already in `database/migrations`. Publishing would add duplicate create-table migrations that fail on `migrate`.

## Verify on dev

```bash
composer update -W
DB_USERNAME=test_user vendor/bin/phpunit         # expect all green after change 5
php artisan --version                            # 11.x
```

Then by hand:
- log in and out; trigger the lockout
- invoice PDF and manifest PDF (Browsershot)
- one Stripe test payment end to end (`stripe listen`), as a smoke test of the payment stack on the new framework
- check the queue workers process jobs; check the activity log for errors

## Deploy to prod

```bash
# Pre-flight
php -v                                    # 8.4
php -m | grep -E '^curl$'
cd /var/www/FastForward-Express
git rev-parse HEAD > ~/pre-l11-commit.txt
tar czf ~/pre-l11-vendor.tgz vendor composer.lock   # fast rollback (no lock in git yet)
mysqldump FastForwardExpress | gzip > ~/pre-l11-db.sql.gz

php artisan down
# Drain the queue so no job is serialized under one framework version and run under another
php artisan tinker --execute="echo DB::table('jobs')->count();"   # wait for 0
sudo supervisorctl stop 'laravel-queue:*'

git pull
composer install --no-dev --optimize-autoloader   # post-install runs `artisan optimize`
php artisan migrate:status                        # expect nothing pending
php artisan optimize:clear && php artisan optimize

sudo supervisorctl start 'laravel-queue:*'
php artisan up
php artisan --version                             # Laravel Framework 11.x
```

Afterwards, watch the activity log and the Discord channel for a day.

**Rollback:** `php artisan down`, `git checkout $(cat ~/pre-l11-commit.txt)`, restore `~/pre-l11-vendor.tgz`, `php artisan optimize:clear && php artisan optimize`, restart the workers, `php artisan up`. No schema changes are involved, so the DB doesn't need restoring.
