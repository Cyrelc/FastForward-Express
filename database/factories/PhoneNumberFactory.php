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

    $pn = '7804' . $faker->randomDigit . $faker->randomDigit . $faker->randomDigit . $faker->randomDigit . $faker->randomDigit . $faker->randomDigit;

    $returnVal = [
        'type' => $t,
        'phone_number' => $pn,
    ];

    if (rand(0,1) == 1) {
        $returnVal["extension_number"] = $faker->randomDigit . $faker->randomDigit . $faker->randomDigit;
    }
    return $returnVal;
});
