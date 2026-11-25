<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripeRefundProcessor;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Spatie\Permission\Models\Permission;
use Stripe\Exception\InvalidRequestException;
use Stripe\Refund;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * Refunding a Stripe payment: the request to Stripe, our refund record, and the webhook that settles it.
 */
class StripeRefundTest extends TestCase {
    use RefreshDatabase;

    // See ZoneSaveTest: APP_URL carries a /app path, so absolute URLs are used to hit the real routes
    private const HOST = 'http://localhost';

    private $refunds;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->refunds = \Mockery::mock();
        $this->app->instance(StripeClient::class, (object)['refunds' => $this->refunds]);
    }

    private function makeInvoice($balanceOwing) {
        return DB::table('invoices')->insertGetId([
            'bill_start_date' => '2026-11-01', 'bill_end_date' => '2026-11-30', 'date' => '2026-11-30',
            'balance_owing' => $balanceOwing, 'bill_cost' => 100, 'tax' => 0, 'total_cost' => 100,
        ], 'invoice_id');
    }

    private function makeStripePayment($invoiceId, $amount = 40) {
        return Payment::create([
            'amount' => $amount,
            'date' => '2026-11-20',
            'invoice_id' => $invoiceId,
            'payment_type_id' => 4,
            'stripe_object_type' => 'payment_intent',
            'stripe_payment_intent_id' => 'pi_' . uniqid(),
            'stripe_status' => 'succeeded',
        ]);
    }

    private function actAsRefunder() {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('payments.delete.*.*'));
        $this->actingAs($user);
    }

    public function test_refund_records_a_pending_refund() {
        $this->actAsRefunder();
        $payment = $this->makeStripePayment($this->makeInvoice(60));

        $this->refunds->shouldReceive('create')->once()
            ->with([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'metadata' => ['reason' => 'requested_by_customer', 'invoice_id' => (string)$payment->invoice_id],
                'reason' => 'requested_by_customer',
            ])
            ->andReturn(Refund::constructFrom(['id' => 're_123', 'object' => 'refund']));

        $this->deleteJson(self::HOST . '/payments/' . $payment->payment_id, ['reason' => 'requested_by_customer'])
            ->assertOk();

        $refund = Payment::where('stripe_refund_id', 're_123')->firstOrFail();
        $this->assertEquals(-40, $refund->amount);
        $this->assertSame('requested_by_customer', $refund->comment);
        $this->assertSame('pending', $refund->stripe_status);
        $this->assertSame('refund', $refund->stripe_object_type);
    }

    public function test_free_text_reason_goes_to_stripe_as_metadata() {
        // Stripe's `reason` rejects anything outside its three values, which used to block "Other" refunds entirely
        $this->actAsRefunder();
        $payment = $this->makeStripePayment($this->makeInvoice(60));

        $this->refunds->shouldReceive('create')->once()
            ->with([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'metadata' => ['reason' => 'Paid twice by phone', 'invoice_id' => (string)$payment->invoice_id],
            ])
            ->andReturn(Refund::constructFrom(['id' => 're_456', 'object' => 'refund']));

        $this->deleteJson(self::HOST . '/payments/' . $payment->payment_id, ['reason' => 'Paid twice by phone'])
            ->assertOk();

        $this->assertSame('Paid twice by phone', Payment::where('stripe_refund_id', 're_456')->value('comment'));
    }

    public function test_stripe_error_records_nothing() {
        $this->actAsRefunder();
        $payment = $this->makeStripePayment($this->makeInvoice(60));
        $transactionLevel = DB::transactionLevel(); // RefreshDatabase already holds one open

        $this->refunds->shouldReceive('create')->once()->andThrow(new InvalidRequestException('Charge already refunded'));

        $this->deleteJson(self::HOST . '/payments/' . $payment->payment_id, ['reason' => 'duplicate'])
            ->assertStatus(400)
            ->assertJsonPath('errors.stripe_error.0', 'Charge already refunded');

        $this->assertSame(0, Payment::whereNotNull('stripe_refund_id')->count());
        $this->assertSame($transactionLevel, DB::transactionLevel());
    }

    #[RequiresPhpExtension('bcmath')] // StripeRefundProcessor uses bcdiv(); prod has it, a bare dev PHP may not
    public function test_succeeded_refund_puts_the_amount_back_on_the_invoice() {
        $invoiceId = $this->makeInvoice(60);
        $payment = $this->makeStripePayment($invoiceId);
        $refundRow = $payment->replicate();
        $refundRow->fill(['amount' => -40, 'stripe_object_type' => 'refund', 'stripe_refund_id' => 're_789', 'stripe_status' => 'pending'])->save();

        $event = (object)['data' => (object)['object' => Refund::constructFrom([
            'id' => 're_789', 'object' => 'refund', 'status' => 'succeeded', 'amount' => 4000,
            'payment_intent' => $payment->stripe_payment_intent_id,
        ])]];

        (new StripeRefundProcessor())->processRefund($event);

        $this->assertEquals(100, Invoice::find($invoiceId)->balance_owing);
        $this->assertSame('succeeded', $refundRow->fresh()->stripe_status);
    }

    public function test_refund_webhook_rejects_a_bad_signature() {
        $this->postJson(self::HOST . '/api/webhooks/stripe/receiveRefundUpdate', ['id' => 'evt_forged'], ['Stripe-Signature' => 't=1,v1=forged'])
            ->assertForbidden();
    }
}
