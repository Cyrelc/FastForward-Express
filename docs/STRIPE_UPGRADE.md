# Stripe upgrade runbook (Cashier 13 → 15, stripe-php 8 → 16)

Status: **code on branch `stripe-upgrade`, not deployed.** Runs on Laravel 10 and goes out before the
Laravel 11 upgrade ([LARAVEL_11_UPGRADE.md](LARAVEL_11_UPGRADE.md)), so that upgrade carries no payment changes.

## Why

Laravel 11 needs Cashier 15, and Cashier 15 needs stripe-php ≥ 16. stripe-php 8 gets no fixes. Stripe has
no published retirement date for old API versions, so the dependency chain is what forces this, not Stripe.

## What changes in behaviour

| | Before | After |
|---|---|---|
| Our `StripeClient` calls | Account's default API version (v8 sends none) | `2025-02-24.acacia`, pinned by stripe-php 16 |
| Cashier's calls (cards, setup intents) | `2020-08-27` | `2023-10-16` |
| Webhook payloads | The version each endpoint was created with | Unchanged until the endpoints are replaced (step 4) |
| Account default API version | Unchanged | Unchanged. Don't press the upgrade button: nothing reads the default any more. |

Breaking API changes that touch our code:

1. **2022-11-15: `PaymentIntent.charges` removed** in favour of `latest_charge` (an ID unless expanded).
   `PaymentIntentProcessor` read card brand, last 4 and receipt URL from `charges`. It now reads either shape
   (`getCharge()`), fetching the charge when only its ID is present. `ToolController` expands
   `latest_charge` on retrieve.
2. **2023-08-16: automatic payment methods on by default** for PaymentIntents and SetupIntents. Our Payment
   Element would have offered every method enabled in the Dashboard. Payment recording and the saved-card list
   assume cards, so both intents now ask for `payment_method_types: ['card']`. Offering other methods is a
   separate feature.
3. **Cashier 15: `paymentMethods()`/`hasPaymentMethod()` return all types.** Pinned to `'card'` in
   `PaymentModelFactory` and `PaymentController`.

Checked and unaffected: refund objects (`status`, `amount`, `failure_message`, `payment_intent`), our SDK surface
(`StripeClient` services, `Webhook::constructEvent`, `CardException`/`InvalidRequestException`/`ApiErrorException`,
`getJsonBody()`), and the frontend (it never reads Stripe objects). Cashier's own webhook route, subscriptions and
the `subscriptions.name → type` rename don't apply: we use neither.

## Code changes (branch `stripe-upgrade`)

Includes the refund hotfixes from master (identical changes, so they merge cleanly).

**Upgrade**
- `composer.json`: `laravel/cashier ^15.0`, `stripe/stripe-php ^16.2`, `ext-bcmath` (the processors' `bcdiv()` and
  moneyphp/money 4 both need it). This also brings Laravel to 10.50.5, above prod's 10.50.2. dompdf drops out; nothing uses it.
- `PaymentIntentProcessor::getCharge()` reads the charge from either payload shape.
- `PaymentController`, `PaymentModelFactory`: card-only, as above.

**Webhook processing: idempotent by design**
- `WebhookController` checks the signature and queues **only the object ID** (plus the event ID, for logs).
  Events about other object types are acknowledged and ignored. Queued jobs no longer hold serialized SDK objects,
  so future SDK upgrades don't need the queue drained.
- Jobs are `ShouldBeUniqueUntilProcessing`. A burst of events collapses into one job, but the lock is released when
  the job starts, so an event arriving mid-run queues a new job. Plain `ShouldBeUnique` held the lock until the job
  finished and **silently dropped** those events, after Stripe had already received its 200.
- `PaymentIntentProcessor::sync()` / `StripeRefundProcessor::sync()` **fetch the current object from Stripe** and
  apply it. Duplicate and out-of-order events stop mattering, so the old "forward-only" status lists are gone. That
  also means a decline after `processing` is now recorded.
- **Money moves exactly once**, on the row's transition into `succeeded`, inside a transaction holding a
  `lockForUpdate()` row lock. A refund that later fails (Stripe allows `succeeded` → `failed`) reverses its adjustment.
- The payment sync matches only `stripe_object_type = 'payment_intent'` rows. Refund rows copy the payment's
  PaymentIntent ID and were being caught too.
- No local row yet → the job throws and retries with backoff (5/15/30/60 s). Card-on-file and refunds both save
  their row after Stripe acts, so the webhook can arrive first; before, it was silently skipped.
- `stripe:reconcile` (scheduled every 15 min) re-syncs payments still `pending`/`processing` (last 30 days) and
  in-flight refunds, as a backstop for missed webhooks. The receipts tool now uses the same `sync()`.
- `Payment::$fillable` gains `error`. Decline and refund-failure messages were silently discarded before.

**Tests:** `tests/Feature/StripeWebhookProcessingTest.php` (exactly-once, stale state, decline, refund-row isolation,
retry on missing row, refund success/failure, controller queues IDs only, unique-until-processing, reconcile),
`tests/Feature/StripeRefundTest.php`, `tests/Unit/Payments/PaymentIntentProcessorChargeTest.php`.

## Verify on dev (Stripe test mode)

```bash
composer install
DB_USERNAME=test_user vendor/bin/phpunit
stripe listen --forward-to http://localhost/api/webhooks/stripe/receivePaymentIntentUpdate \
  --events payment_intent.created,payment_intent.processing,payment_intent.succeeded,payment_intent.payment_failed,payment_intent.canceled
# The CLI forwards with the SDK's API version; that exercises the new payload shape.
```

Use test card `4242 4242 4242 4242` and check each of these:
- One-off payment (Payment Element shows card only). The webhook marks it succeeded with brand, last 4 and receipt, and the invoice balance drops once.
- Card-on-file payment, same checks.
- Add a card (card only), set it as default, delete a non-default card.
- Declined card `4000 0000 0000 0002`: an error is shown and nothing is recorded as paid.
- Receipts tool (admin) on a payment with no receipt URL.
- Refund (Duplicate, and Other with free text). The row shows pending, then succeeded via `refund.updated`, and the balance goes back up once. In the Stripe Dashboard, the refund's Metadata shows the reason text and invoice ID.
- `php artisan stripe:reconcile` reports the in-flight count and 0 failures.

## Deploy

```bash
# Pre-flight
php -m | grep -E '^bcmath$'                        # must print bcmath
cd /var/www/FastForward-Express
git rev-parse HEAD > ~/pre-stripe-commit.txt
tar czf ~/pre-stripe-vendor.tgz vendor composer.lock 2>/dev/null || tar czf ~/pre-stripe-vendor.tgz vendor

php artisan down
# Queued jobs hold serialized stripe-php v8 Event objects (and the job constructors change): let them finish first
php artisan tinker --execute="echo DB::table('jobs')->count();"   # wait for 0
sudo supervisorctl stop 'laravel-queue:*'

git pull
composer install --no-dev --optimize-autoloader
php artisan optimize:clear && php artisan optimize

sudo supervisorctl start 'laravel-queue:*'
php artisan up
```

The code reads both payload shapes, so the webhook endpoints can be swapped any time after this deploy (step 4),
or left as they are for now.

**Rollback:** `git checkout $(cat ~/pre-stripe-commit.txt)`, restore the vendor tarball, `php artisan optimize:clear && php artisan optimize`,
restart the workers. No schema changes.

## Step 4: move the webhook endpoints to the new API version (after the deploy is stable)

An endpoint's API version is fixed when it's created, so you make new endpoints and retire the old ones. For each
of the two existing endpoints (payment intent: `…/api/webhooks/stripe/receivePaymentIntentUpdate`, refund:
`…/api/webhooks/stripe/receiveRefundUpdate`):

1. Note its URL and enabled events in the Dashboard (Developers → Webhooks).
2. Create a duplicate pinned to the new version, with the same URL and events:
   ```bash
   stripe webhook_endpoints create --url <same url> \
     --enabled-events <same events, comma separated> -d api_version=2025-02-24.acacia
   ```
3. Put the new signing secret in prod `.env` (`STRIPE_PAYMENT_INTENT_SECRET` / `STRIPE_REFUND_SECRET`), then
   `php artisan optimize` and restart the workers. **Disable the old endpoint at the same moment.** While both
   are live, every event is delivered twice, and the one signed with the old secret gets a 403 and keeps retrying.
4. Make a payment and confirm the next event is processed (activity log, `jobs` channel).
