<?php

$factory->define(App\EmailAddress::class, function (Faker\Generator $faker) {
    return [
        'email' => $faker->safeEmail,
    ];
});
