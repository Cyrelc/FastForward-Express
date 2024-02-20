<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\AppEmployeeEmergencyContact>
 */
class EmployeeEmergencyContactFactory extends Factory
{
    protected $model = EmployeeEmergencyContact::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => function () {
                return Contact::factory()->create()->contact_id;
            },
            'employee_id' => function() {
                return Employee::factory()->create()->employee_id;
            },
        ];
    }
}
