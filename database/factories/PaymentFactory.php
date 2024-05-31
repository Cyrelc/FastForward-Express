<?php

namespace Database\Factories;


use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory {
    protected $model = Payment::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'account_id' => null,
            'amount' => $this->faker->numerify('###.##'),
            'comment' => $this->faker->lorem.lines(2),
            'date' => $this->faker->date->recent(),
            'invoice_id' => function() {
                return Invoice::factory()->create()->invoice_id;
            },
            'stripe_object_type' => 'payment_intent',
            'stripe_payment_intent_id' => uniqid('pi_'),
            'stripe_refund_id' => null,
            'stripe_status' => 'requires_payment_method',
            // TODO - replace hardcoded values with database call for prepaids
            'payment_type_id' => $this->faker->randomElement($array = array(3, 4, 5, 7, 8)),
            'reference_value' => $this->faker->lorem->word(),
        ];
    }
}
