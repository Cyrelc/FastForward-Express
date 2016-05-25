<?php

use App\Driver;

use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder {
    public function run() {
        $faker = Faker\Factory::create('en_CA');

        for($i = 0; $i < 10; ++$i) {

            Driver::create(array(
                'number' => $faker->unique()->randomNumber(),
                'name' => $faker->name,
                'sin' => $faker->numerify('### ### ###'),
                'pager' => $faker->phoneNumber,
                'active' => $faker->boolean(75),
                'licence' => $faker->bothify('???-####'),
                'address' => $faker->address,
                'postal' => $faker->postcode,
                'phone' => $faker->phoneNumber,
                'email' => 'Driver-' . $i . '@mailinator.com',
                'start' => $faker->dateTimeBetween($startDate = '-10 years')
            ));
        }
    }
}
