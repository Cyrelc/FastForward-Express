<?php
$factory->define(App\Employee::class, function (Faker\Generator $faker) {
    return [
        "start_date" => $faker->dateTimeThisDecade(),
        "sin" => rand(100, 999) . ' ' . rand(100, 999) . ' ' . rand(100, 999),
        "dob" => $faker->dateTimeBetween('-60 years', '-18 years'),
        "active" => true,
        "employee_number" => round(microtime(true) * 1000)
    ];
});
