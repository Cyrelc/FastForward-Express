<?php

$factory->define(App\Contact::class, function (Faker\Generator $faker) {
    return [
        'first_name' => $faker->firstname,
        'last_name' => $faker->lastName,
        'enabled' => true
    ];
});

