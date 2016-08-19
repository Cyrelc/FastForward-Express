<?php
$factory->define(App\Driver::class, function (Faker\Generator $faker) {
    $c = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
    $n = '0123456789';
    $plate = $c[rand(0, strlen($c) - 1)] . "-" . $n[rand(0, strlen($n) - 1)] . $n[rand(0, strlen($n) - 1)] . $n[rand(0, strlen($n) - 1)] . $n[rand(0, strlen($n) - 1)] . $n[rand(0, strlen($n) - 1)];
    
    return [
        "start_date" => $faker->dateTimeThisDecade,
        "license_plate_number" => $plate,
        "active" => true,
        "pickup_commission" => 0.34,
        "delivery_commission" => 0.34
    ];
});
