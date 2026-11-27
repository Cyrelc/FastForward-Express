<?php

namespace Tests\Feature;

use App\Jobs\ReceiveStripeRefundWebhook;
use App\Jobs\ReceiveStripeWebhook;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentIntentProcessor;
use App\Services\StripeRefundProcessor;
use DB;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * Stripe delivers webhooks at least once, in any order. Whatever arrives, and however often, each payment must
 * move the invoice balance exactly once.
 */
class StripeWebhookProcessingTest extends TestCase {
    use RefreshDatabase;

    // See ZoneSaveTest: APP_URL carries a /app path, so absolute URLs are used to hit the real routes
    private const HOST = 'http://localhost';

    protected function setUp(): void {
        parent::setUp();
        if(!DB::table('payment_types')->where('name', 'Stripe (Pending)')->exists())
            DB::table('payment_types')->insert(['name' => 'Stripe (Pending)', 'type' => 'stripe_pending']);
    }

    private function makeInvoice($balanceOwing) {
        return DB::table('invoices')->insertGetId([
            'bill_start_date' => '2026-11-01', 'bill_end_date' => '2026-11-30', 'date' => '2026-11-30',
            'balance_owing' => $balanceOwing, 'bill_cost' => 100, 'tax' => 0, 'total_cost' => 100,
        ], 'invoice_id');
    }

    private function makePayment($invoiceId, $attributes = []) {
        return Payment::create($attributes + [
            'amount' => 40,
            'date' => now()->toDateString(),
            'invoice_id' => $invoiceId,
            'payment_type_id' => DB::table('payment_types')->where('name', 'Stripe (Pending)')->value('payment_type_id'),
            'stripe_object_type' => 'payment_intent',
            'stripe_payment_intent_id' => 'pi_test',
            'stripe_status' => 'requires_payment_method',
        ]);
    }

    private function paymentIntent($status, $extra = []) {
        return PaymentIntent::constructFrom($extra + [
            'id' => 'pi_test',
            'object' => 'payment_intent',
            'status' => $status,
            'amount_received' => $status === 'succeeded' ? 4000 : 0,
            'last_payment_error' => null,
            'latest_charge' => $status === 'succeeded' ? [
                'id' => 'ch_test', 'object' => 'charge', 'receipt_url' => 'https://pay.stripe.com/receipts/ch_test',
                'payment_method_details' => ['type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']],
            ] : null,
        ]);
    }

    private function balanceOwing($invoiceId) {
        return (float)Invoice::find($invoiceId)->balance_owing;
    }

    private function signedPost($uri, array $payload, $secret) {
        $body = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);

        return $this->call('POST', self::HOST . $uri, [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function test_success_reduces_the_balance_once_however_often_it_is_applied() {
        $invoiceId = $this->makeInvoice(100);
        $payment = $this->makePayment($invoiceId);
        $processor = new PaymentIntentProcessor();

        // Duplicate delivery, a retried job, the receipts tool and stripe:reconcile all end up here again
        $processor->apply($this->paymentIntent('succeeded'));
        $processor->apply($this->paymentIntent('succeeded'));

        $this->assertSame(60.0, $this->balanceOwing($invoiceId));
        $payment->refresh();
        $this->assertSame('succeeded', $payment->stripe_status);
        $this->assertEquals(40, $payment->amount);
        $this->assertSame('**** **** **** 4242', $payment->reference_value);
        $this->assertSame('https://pay.stripe.com/receipts/ch_test', $payment->receipt_url);
        $this->assertSame('Visa', DB::table('payment_types')->where('payment_type_id', $payment->payment_type_id)->value('name'));
    }

    public function test_a_stale_status_never_undoes_a_success() {
        $invoiceId = $this->makeInvoice(100);
        $payment = $this->makePayment($invoiceId);
        $processor = new PaymentIntentProcessor();

        $processor->apply($this->paymentIntent('succeeded'));
        $processor->apply($this->paymentIntent('processing'));

        $this->assertSame('succeeded', $payment->fresh()->stripe_status);
        $this->assertSame(60.0, $this->balanceOwing($invoiceId));
    }

    public function test_a_decline_after_processing_is_recorded() {
        // processing -> requires_payment_method is a real Stripe transition; the old "forward only" order skipped it
        $invoiceId = $this->makeInvoice(100);
        $payment = $this->makePayment($invoiceId, ['stripe_status' => 'processing']);

        (new PaymentIntentProcessor())->apply($this->paymentIntent('requires_payment_method', [
            'last_payment_error' => ['message' => 'Your card was declined.'],
        ]));

        $payment->refresh();
        $this->assertSame('requires_payment_method', $payment->stripe_status);
        $this->assertSame('Your card was declined.', $payment->error);
        $this->assertSame(100.0, $this->balanceOwing($invoiceId));
    }

    public function test_a_refund_row_sharing_the_payment_intent_is_left_alone() {
        $invoiceId = $this->makeInvoice(100);
        $this->makePayment($invoiceId);
        $refundRow = $this->makePayment($invoiceId, [
            'amount' => -40, 'stripe_object_type' => 'refund', 'stripe_refund_id' => 're_test', 'stripe_status' => 'pending',
        ]);

        (new PaymentIntentProcessor())->apply($this->paymentIntent('succeeded'));

        $refundRow->refresh();
        $this->assertSame('pending', $refundRow->stripe_status);
        $this->assertEquals(-40, $refundRow->amount);
        $this->assertSame(60.0, $this->balanceOwing($invoiceId));
    }

    public function test_an_unknown_payment_intent_throws_so_the_job_retries() {
        // Card-on-file saves its row after Stripe charges, so the webhook can arrive first
        $this->expectException(\RuntimeException::class);

        (new PaymentIntentProcessor())->apply($this->paymentIntent('succeeded'));
    }

    public function test_sync_fetches_the_current_state_with_the_charge() {
        $invoiceId = $this->makeInvoice(100);
        $this->makePayment($invoiceId);

        $paymentIntents = \Mockery::mock();
        $paymentIntents->shouldReceive('retrieve')->once()
            ->with('pi_test', ['expand' => ['latest_charge']])
            ->andReturn($this->paymentIntent('succeeded'));
        $this->app->instance(StripeClient::class, (object)['paymentIntents' => $paymentIntents]);

        (new PaymentIntentProcessor())->sync('pi_test');

        $this->assertSame(60.0, $this->balanceOwing($invoiceId));
    }

    public function test_refund_success_restores_the_balance_once_and_a_later_failure_reverses_it() {
        $invoiceId = $this->makeInvoice(60);
        $refundRow = $this->makePayment($invoiceId, [
            'amount' => -40, 'stripe_object_type' => 'refund', 'stripe_refund_id' => 're_test', 'stripe_status' => 'pending',
        ]);
        $processor = new StripeRefundProcessor();
        $refund = fn($status) => Refund::constructFrom([
            'id' => 're_test', 'object' => 'refund', 'status' => $status, 'amount' => 4000, 'payment_intent' => 'pi_test',
            'failure_message' => $status === 'failed' ? 'expired_or_canceled_card' : null,
        ]);

        $processor->apply($refund('succeeded'));
        $processor->apply($refund('succeeded'));
        $this->assertSame(100.0, $this->balanceOwing($invoiceId));

        $processor->apply($refund('failed'));
        $this->assertSame(60.0, $this->balanceOwing($invoiceId));
        $this->assertSame('expired_or_canceled_card', $refundRow->fresh()->error);
    }

    public function test_an_unknown_refund_throws_so_the_job_retries() {
        $this->expectException(\RuntimeException::class);

        (new StripeRefundProcessor())->apply(Refund::constructFrom(['id' => 're_unknown', 'object' => 'refund', 'status' => 'succeeded', 'amount' => 100]));
    }

    public function test_webhooks_queue_only_the_object_id() {
        Queue::fake();
        config(['services.stripe.stripe_payment_intent_secret' => 'whsec_pi', 'services.stripe.stripe_refund_secret' => 'whsec_re']);

        $this->signedPost('/api/webhooks/stripe/receivePaymentIntentUpdate', [
            'id' => 'evt_1', 'object' => 'event', 'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test', 'object' => 'payment_intent', 'status' => 'succeeded']],
        ], 'whsec_pi')->assertOk();

        $this->signedPost('/api/webhooks/stripe/receiveRefundUpdate', [
            'id' => 'evt_2', 'object' => 'event', 'type' => 'refund.updated',
            'data' => ['object' => ['id' => 're_test', 'object' => 'refund', 'status' => 'succeeded']],
        ], 'whsec_re')->assertOk();

        // An event about some other object is acknowledged but not queued
        $this->signedPost('/api/webhooks/stripe/receiveRefundUpdate', [
            'id' => 'evt_3', 'object' => 'event', 'type' => 'charge.refunded',
            'data' => ['object' => ['id' => 'ch_test', 'object' => 'charge']],
        ], 'whsec_re')->assertOk();

        Queue::assertPushed(ReceiveStripeWebhook::class, fn($job) => $job->paymentIntentId === 'pi_test' && $job->eventId === 'evt_1');
        Queue::assertPushed(ReceiveStripeRefundWebhook::class, fn($job) => $job->refundId === 're_test');
        Queue::assertPushed(ReceiveStripeRefundWebhook::class, 1);
    }

    public function test_jobs_release_their_unique_lock_when_they_start() {
        // A lock held until the job finished made Laravel drop events arriving meanwhile
        $this->assertInstanceOf(ShouldBeUniqueUntilProcessing::class, new ReceiveStripeWebhook('pi_test'));
        $this->assertInstanceOf(ShouldBeUniqueUntilProcessing::class, new ReceiveStripeRefundWebhook('re_test'));
    }

    public function test_reconcile_resyncs_in_flight_payments_and_refunds() {
        $invoiceId = $this->makeInvoice(100);
        $this->makePayment($invoiceId, ['stripe_status' => 'pending']);
        $this->makePayment($invoiceId, ['stripe_payment_intent_id' => 'pi_abandoned']); // requires_payment_method: skipped
        $this->makePayment($invoiceId, [
            'amount' => -40, 'stripe_object_type' => 'refund', 'stripe_refund_id' => 're_test', 'stripe_status' => 'pending',
        ]);

        $paymentIntents = \Mockery::mock();
        $paymentIntents->shouldReceive('retrieve')->once()->with('pi_test', \Mockery::any())->andReturn($this->paymentIntent('succeeded'));
        $refunds = \Mockery::mock();
        $refunds->shouldReceive('retrieve')->once()->with('re_test')->andReturn(Refund::constructFrom([
            'id' => 're_test', 'object' => 'refund', 'status' => 'pending', 'amount' => 4000,
        ]));
        $this->app->instance(StripeClient::class, (object)['paymentIntents' => $paymentIntents, 'refunds' => $refunds]);

        $this->artisan('stripe:reconcile')->assertSuccessful();

        $this->assertSame(60.0, $this->balanceOwing($invoiceId));
    }
}
