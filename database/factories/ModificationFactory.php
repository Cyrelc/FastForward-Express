<?php

$factory->define(App\Modification::class, function (Faker\Generator $faker) {
    return [
        'user_id' => rand(1,2),
        'date' => $faker->dateTimeThisMonth,
    ];
});

