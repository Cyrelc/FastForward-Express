<?php

namespace Database\Factories;

use App\Employee;
use App\User;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\AppEmployee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array {
        return [
            'employee_number' => $this->faker->unique->randomNumber,
            'start_date' => $this->faker->dateTimeBetween($startDate = '-20 years', $endDate = 'now'),
            'contact_id' => function() {
                return Contact::factory()->create()->contact_id;
            },
            'user_id' => function() {
                return User::factory()->create()->user_id;
            },
            'sin' => $this->faker->numerify('### ### ###'),
            'dob' => $this->faker->dateTimeBetween($startDate = '-60 years', $endDate = '-20 years'),
            'is_driver' => false,
        ];
    }

    public function driver() {
        $this->faker->addProvider(new \Faker\Provider\ms_MY\Miscellaneous($this->faker));
        $selectionsRepo = new \App\Http\Repos\SelectionsRepo();
        $vehicleTypes = $selectionsRepo->GetSelectionsListByType('vehicle_type');

        return $this->state(function (array $attributes) use ($vehicleTypes) {
            return [
                'drivers_license_number' => $this->faker->jpjNumberPlate,
                'drivers_license_expiration_date' => $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = '+3 years'),
                'license_plate_number' => $this->faker->jpjNumberPlate,
                'license_plate_expiration_date' => $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = '+3 years'),
                'insurance_number' => $this->faker->jpjNumberPlate,
                'insurance_expiration_date' => $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = '+3 years'),
                'is_driver' => true,
                'pickup_commission' => 34.0,
                'delivery_commission' => 34.0,
                'company_name' => $this->faker->company,
                // TODO - this should be based on the database seeded values, not random
                'vehicle_type_id' => $vehicleTypes[$this->faker->numberBetween(0, count($vehicleTypes) - 1)]->value
            ];
        });
    }
}
