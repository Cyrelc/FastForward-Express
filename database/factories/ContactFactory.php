<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory {

    protected $model = Contact::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'first_name' => $this->faker->firstname,
            'last_name' => $this->faker->lastName,
            'position' => $this->faker->word,
            'preferred_name' => $this->faker->optional()->name,
            'pronouns' => null
        ];
    }
}
