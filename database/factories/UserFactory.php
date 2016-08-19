<?php

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'username' => $faker->userName,
        'email' => $faker->safeEmail,
        'password' => Hash::make('ffe'),
        'is_locked' => false,
        'login_attempts' => 0
    ];
});

