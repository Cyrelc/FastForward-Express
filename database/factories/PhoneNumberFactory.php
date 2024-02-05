<?php

namespace Database\Factories;

use App\Models\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppModelsPhoneNumber>
 */
class PhoneNumberFactory extends Factory
{
    protected $model = PhoneNumber::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phoneNumber = explode('x', $this->faker->phoneNumber);
        return [
            'contact_id' => function() {
                return Contact::factory()->create()->contact_id;
            },
            'phone_number' => $phoneNumber[0],
            'extension_number' => $phoneNumber[1] ?? null,
            'is_primary' => false,
            'type' => $this->faker->randomElement($array = array('cell', 'fax', 'home', 'work')),
        ];
    }
}
