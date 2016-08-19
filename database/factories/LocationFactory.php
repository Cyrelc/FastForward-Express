<?php

$factory->define(App\Location::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->company,
    ];
});
