<?php

$factory->define(App\Payment::class, function (Faker\Generator $faker) {
    return [
        'payment_method_id' => rand(1,3),
        'date' => $faker->dateTimeThisMonth,
    ];
});
