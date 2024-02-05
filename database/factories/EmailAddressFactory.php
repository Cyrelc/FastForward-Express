<?php

namespace Database\Factories;

use App\Models\EmailAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\EmailAddress>
 */
class EmailAddressFactory extends Factory
{
    protected $model = EmailAddress::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => function() {
                return Contact::factory()->create()->contact_id;
            },
            'email' => $this->faker->unique()->safeEmail,
            'is_primary' => false,
            'type' => json_encode($this->faker->randomElement($array = array(
                ["label" => "Support","value" => "30"],
                ["label" => "Personal","value" => "90"],
                ["label" => "Business","value" => "31"],
                ["label" => "Accounting","value" => "32"]
            )))
        ];
    }
}
