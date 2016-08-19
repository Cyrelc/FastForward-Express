<?php

$factory->define(App\EmailAddress::class, function (Faker\Generator $faker) {
    $r = $faker->randomDigit;
    $t = '';

    if ($r < 5)
        $t = "Personal";
    else
        $t = "Work";

    return [
        'type' => $t,
        'address' => $faker->safeEmail,
    ];
});
