<?php
$factory->define(App\Driver::class, function (Faker\Generator $faker) {
    $c = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
    $n = '0123456789';
    $plate = $c[rand(0, strlen($c) - 1)] . "-" . $n[rand(0, strlen($n) - 1)] . $n[rand(0, strlen($n) - 1)] . $n[rand(0, strlen($n) - 1)] . $n[rand(0, strlen($n) - 1)] . $n[rand(0, strlen($n) - 1)];
    //TODO: FIX -- can't start with A, E, I, O, U, Y
    return [
        "license_plate_number" => $plate,
        "drivers_license_number" => rand(100000, 999999) . '-' . rand(100, 500),
        "insurance_number" => str_random(2) . '-' . str_random(5) . '-' . str_random(3),
        "license_plate_expiration" => $faker->dateTimeThisYear(),
        "insurance_expiration" => $faker->dateTimeThisYear(),
        "license_expiration" => $faker->dateTimeThisYear(),
        "pickup_commission" => 0.34,
        "delivery_commission" => 0.34
    ];
});
