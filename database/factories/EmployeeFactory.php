<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\AppEmployee>
 */
class EmployeeFactory extends Factory {
    protected $model = Employee::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array {
        return [
            'employee_number' => $this->faker->unique->randomNumber,
            'start_date' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
            'contact_id' => function() {
                return Contact::factory()->create()->contact_id;
            },
            'user_id' => function() {
                return User::factory()->create()->id;
            },
            'sin' => $this->faker->numerify('### ### ###'),
            'dob' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
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
                'drivers_license_expiration_date' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
                'license_plate_number' => $this->faker->jpjNumberPlate,
                'license_plate_expiration_date' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
                'insurance_number' => $this->faker->jpjNumberPlate,
                'insurance_expiration_date' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
                'is_driver' => true,
                'pickup_commission' => 34.0,
                'delivery_commission' => 34.0,
                'company_name' => $this->faker->company,
                'vehicle_type_id' => $vehicleTypes[$this->faker->numberBetween(0, count($vehicleTypes) - 1)]->value
            ];
        });
    }

    public function fakePermissions() {
        $permissions = [];
        foreach(Employee::$permissionsMap as $key => $value) {
            $permissions[$value] = $this->faker->boolean;
        }
        return $permissions;
    }
}
