<?php

namespace Tests\Unit\Payments;

use Tests\TestCase;
use Stripe\Charge;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

use App\Services\PaymentIntentProcessor;

/**
 * PaymentIntent payloads change shape at Stripe API 2022-11-15 (`charges` list -> `latest_charge`).
 * Webhook payloads follow the endpoint's API version, so the processor has to read both.
 */
class PaymentIntentProcessorChargeTest extends TestCase {
    private const CHARGE = [
        'id' => 'ch_123',
        'object' => 'charge',
        'receipt_url' => 'https://pay.stripe.com/receipts/ch_123',
        'payment_method_details' => ['type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']],
    ];

    public function testReadsTheChargesListFromOldApiVersions() {
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_123',
            'object' => 'payment_intent',
            'charges' => ['object' => 'list', 'data' => [self::CHARGE]],
        ]);

        $charge = (new PaymentIntentProcessor())->getCharge($paymentIntent);

        $this->assertSame('ch_123', $charge->id);
        $this->assertSame('4242', $charge->payment_method_details->card->last4);
    }

    public function testReadsAnExpandedLatestCharge() {
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_123',
            'object' => 'payment_intent',
            'latest_charge' => self::CHARGE,
        ]);

        $charge = (new PaymentIntentProcessor())->getCharge($paymentIntent);

        $this->assertSame('https://pay.stripe.com/receipts/ch_123', $charge->receipt_url);
    }

    public function testFetchesTheLatestChargeWhenOnlyItsIdIsSent() {
        $charges = \Mockery::mock();
        $charges->shouldReceive('retrieve')->once()->with('ch_123')->andReturn(Charge::constructFrom(self::CHARGE));
        $this->app->instance(StripeClient::class, (object)['charges' => $charges]);

        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_123',
            'object' => 'payment_intent',
            'latest_charge' => 'ch_123',
        ]);

        $charge = (new PaymentIntentProcessor())->getCharge($paymentIntent);

        $this->assertSame('visa', $charge->payment_method_details->card->brand);
    }

    public function testNoChargeYet() {
        // e.g. payment_intent.created: nothing has been charged, and no API call should be made
        $this->app->instance(StripeClient::class, null);
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_123',
            'object' => 'payment_intent',
            'latest_charge' => null,
        ]);

        $this->assertNull((new PaymentIntentProcessor())->getCharge($paymentIntent));
    }
}
