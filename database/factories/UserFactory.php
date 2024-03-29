<?php

namespace Database\Factories;

use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory {

    protected $model = User::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'username' => $this->faker->unique()->safeEmail,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'is_enabled' => true,
            'login_attempts' => 0,
            'remember_token' => Str::random(10),
        ];
    }
}
