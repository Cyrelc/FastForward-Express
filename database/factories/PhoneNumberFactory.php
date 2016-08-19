<?php

$factory->define(App\PhoneNumber::class, function (Faker\Generator $faker) {
    $r = $faker->randomDigit;
    $t = '';

    if ($r < 4)
        $t = "Home";
    else if ($r < 7)
        $t = "Cell";
    else
        $t = "Fax";

    return [
        'type' => $t,
        'phone_number' => $faker->phoneNumber,
    ];
});
