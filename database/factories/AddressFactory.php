<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => null,
            'is_mall' => false,
            'name' => $this->faker->company,
            'is_primary' => false,
            'lat' => number_format($this->faker->latitude, 6),
            'lng' => number_format($this->faker->longitude, 6),
            'formatted' => $this->faker->address,
            'place_id' => $this->faker->numberBetween(0, 500000000)
        ];
    }
}
